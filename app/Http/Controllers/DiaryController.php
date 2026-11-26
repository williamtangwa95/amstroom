<?php

namespace App\Http\Controllers;

use App\Mail\DiaryFollowUpReminderMail;
use App\Models\Diary;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DiaryController extends Controller
{
    private static bool $columnsChecked = false;

    /**
     * Ensure follow-up reminder columns exist even before `php artisan migrate` is manually run.
     */
    public static function ensureFollowUpColumnsExist(): void
    {
        if (self::$columnsChecked || !Schema::hasTable('diaries')) {
            return;
        }
        self::$columnsChecked = true;

        $needed = [
            'remind_days_before',
            'reminder_date',
            'reminder_time',
            'notify_email',
            'reminder_notified_at',
            'due_notified_at',
            'reminder_popup_acknowledged_at',
            'due_popup_acknowledged_at',
        ];

        $missing = false;
        foreach ($needed as $col) {
            if (!Schema::hasColumn('diaries', $col)) {
                $missing = true;
                break;
            }
        }

        if (!$missing) {
            return;
        }

        Schema::table('diaries', function (Blueprint $table) {
            if (!Schema::hasColumn('diaries', 'remind_days_before')) {
                $table->unsignedSmallInteger('remind_days_before')->default(0)->after('needs_follow_up');
            }
            if (!Schema::hasColumn('diaries', 'reminder_date')) {
                $table->date('reminder_date')->nullable()->index()->after('remind_days_before');
            }
            if (!Schema::hasColumn('diaries', 'reminder_time')) {
                $table->string('reminder_time', 5)->default('10:00')->after('reminder_date');
            }
            if (!Schema::hasColumn('diaries', 'notify_email')) {
                $table->boolean('notify_email')->default(false)->after('reminder_time');
            }
            if (!Schema::hasColumn('diaries', 'reminder_notified_at')) {
                $table->timestamp('reminder_notified_at')->nullable()->after('notify_email');
            }
            if (!Schema::hasColumn('diaries', 'due_notified_at')) {
                $table->timestamp('due_notified_at')->nullable()->after('reminder_notified_at');
            }
            if (!Schema::hasColumn('diaries', 'reminder_popup_acknowledged_at')) {
                $table->timestamp('reminder_popup_acknowledged_at')->nullable()->after('due_notified_at');
            }
            if (!Schema::hasColumn('diaries', 'due_popup_acknowledged_at')) {
                $table->timestamp('due_popup_acknowledged_at')->nullable()->after('reminder_popup_acknowledged_at');
            }
        });
    }

    /**
     * Determine whether the user's shop has at least one active seller,
     * enabling shared Shop Diary features alongside Personal Diary.
     */
    private function canUseShopDiary($user): bool
    {
        if ($user->isOwner() || !$user->shop_id) {
            return false;
        }

        return User::where('shop_id', $user->shop_id)
            ->where('role', 'seller')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'active');
            })
            ->exists();
    }

    /**
     * Determine if a user can manage (edit/delete/reschedule) a diary entry.
     */
    private function canManageDiary($user, Diary $diary): bool
    {
        if ($diary->user_id === $user->id) {
            return true;
        }

        if ($diary->type === 'shop') {
            if ($user->isShopAdmin() && $user->shop_id && $diary->shop_id === $user->shop_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a user can view a diary entry.
     */
    private function canViewDiary($user, Diary $diary): bool
    {
        if ($diary->type === 'personal') {
            return $diary->user_id === $user->id;
        }

        if ($diary->type === 'shop') {
            return (bool) ($user->shop_id && $diary->shop_id === $user->shop_id);
        }

        return false;
    }

    /**
     * Base query scoped by user role, shop access, and request filters.
     */
    private function scopedDiaryQuery($user, Request $request = null)
    {
        $query = Diary::with(['user', 'shop']);

        if (!$this->canUseShopDiary($user)) {
            $query->where('type', 'personal')->where('user_id', $user->id);
        } else {
            $type       = $request ? $request->input('type') : null;
            $userShopId = $user->shop_id;

            if ($type === 'personal') {
                $query->where('type', 'personal')->where('user_id', $user->id);
            } elseif ($type === 'shop') {
                if ($userShopId) {
                    $query->where('type', 'shop')->where('shop_id', $userShopId);
                } else {
                    $query->whereRaw('0 = 1');
                }
            } else {
                $query->where(function ($q) use ($user, $userShopId) {
                    $q->where(function ($sq) use ($user) {
                        $sq->where('type', 'personal')->where('user_id', $user->id);
                    });
                    if ($userShopId) {
                        $sq->orWhere(function ($sq2) use ($userShopId) {
                            $sq2->where('type', 'shop')->where('shop_id', $userShopId);
                        });
                    }
                });
            }
        }

        return $query;
    }

    /**
     * Validate and resolve reminder_date & remind_days_before:
     * - Minimum remind_days_before = 1 when follow-up & due_date are set
     * - Maximum remind_days_before = days between event_date and due_date (when event_date is set)
     */
    private function resolveFollowUpFields(Request $request, array $validated): array
    {
        $needsFollowUp = $request->boolean('needs_follow_up');
        $validated['needs_follow_up'] = $needsFollowUp;

        if (!$needsFollowUp) {
            $validated['remind_days_before']             = 1;
            $validated['reminder_date']                  = null;
            $validated['reminder_time']                  = '10:00';
            $validated['notify_email']                   = false;
            $validated['reminder_notified_at']           = null;
            $validated['due_notified_at']                = null;
            $validated['reminder_popup_acknowledged_at'] = null;
            $validated['due_popup_acknowledged_at']      = null;

            return $validated;
        }

        $validated['notify_email'] = $request->boolean('notify_email');
        $rawTime = trim((string) $request->input('reminder_time', ''));
        $validated['reminder_time'] = preg_match('/^\d{2}:\d{2}/', $rawTime, $tm) ? $tm[0] : '10:00';

        if (empty($validated['due_date'])) {
            $validated['remind_days_before'] = max(1, (int) ($request->input('remind_days_before', 1)));
            $validated['reminder_date'] = null;
            return $validated;
        }

        $due = Carbon::parse($validated['due_date'])->startOfDay();
        $event = !empty($validated['event_date'])
            ? Carbon::parse($validated['event_date'])->startOfDay()
            : null;

        $maxDays = $event ? (int) $event->diffInDays($due, false) : 365;

        if ($event && $maxDays < 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'due_date' => 'When follow-up is enabled, Due Date must be at least 1 day after Event Date.',
            ]);
        }

        if ($request->filled('reminder_date')) {
            $rem = Carbon::parse($request->input('reminder_date'))->startOfDay();
            $daysBefore = (int) $rem->diffInDays($due, false);

            if ($daysBefore < 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reminder_date' => 'Reminder Date must be at least 1 day before Due Date.',
                ]);
            }
            if ($daysBefore > $maxDays) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reminder_date' => "Reminder Date cannot be earlier than Event Date (maximum {$maxDays} day(s) before Due Date).",
                ]);
            }

            $validated['reminder_date'] = $rem->toDateString();
            $validated['remind_days_before'] = $daysBefore;
        } else {
            $daysBefore = (int) ($request->input('remind_days_before', 1));

            if ($daysBefore < 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'remind_days_before' => 'Minimum reminder days before Due Date is 1.',
                ]);
            }
            if ($daysBefore > $maxDays) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'remind_days_before' => "Maximum reminder days is {$maxDays} (days between Event Date and Due Date).",
                ]);
            }

            $validated['remind_days_before'] = $daysBefore;
            $validated['reminder_date'] = $due->copy()->subDays($daysBefore)->toDateString();
        }

        return $validated;
    }

    /**
     * Determine notification recipients for a diary entry.
     */
    private function getNotificationRecipients(Diary $diary)
    {
        if ($diary->type === 'shop' && $diary->shop_id) {
            $members = User::where('shop_id', $diary->shop_id)
                ->whereIn('role', ['shop_admin', 'seller'])
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'active');
                })
                ->get();

            if ($members->isNotEmpty()) {
                return $members;
            }
        }

        return $diary->user ? collect([$diary->user]) : collect();
    }

    /**
     * Dispatch in-app notification + optional email once for a milestone ('reminder' or 'due').
     */
    private function dispatchFollowUpNotificationOnce(Diary $diary, string $stage): void
    {
        $isDue = $stage === 'due';
        $notifiedColumn = $isDue ? 'due_notified_at' : 'reminder_notified_at';

        if ($diary->{$notifiedColumn} !== null) {
            return;
        }

        // Claim atomically so concurrent polls never double-send
        $claimed = Diary::where('id', $diary->id)
            ->whereNull($notifiedColumn)
            ->update([$notifiedColumn => now()]);

        if ($claimed === 0) {
            return;
        }

        $diary->refresh()->loadMissing(['user', 'shop']);

        $dueStr = $diary->due_date ? $diary->due_date->format('d M Y') : 'N/A';
        $remStr = $diary->effective_reminder_date ? $diary->effective_reminder_date->format('d M Y') : $dueStr;
        $remTimeStr = $diary->formatted_reminder_time;

        if ($isDue) {
            $title = 'Diary Follow-up Due Date Reached';
            $message = "Diary entry \"{$diary->event_name}\" has reached its due date ({$dueStr} · {$remTimeStr}). Please review or reschedule.";
        } else {
            $daysLeft = (int) now()->startOfDay()->diffInDays($diary->due_date->copy()->startOfDay(), false);
            $daysText = $daysLeft === 1 ? '1 day left' : "{$daysLeft} days left";
            $title = 'Diary Follow-up Reminder';
            $message = "Upcoming diary follow-up \"{$diary->event_name}\" — reminder ({$remStr} at {$remTimeStr}) reached. Due on {$dueStr} ({$daysText}).";
        }

        $recipients = $this->getNotificationRecipients($diary);

        foreach ($recipients as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'title'   => $title,
                'message' => $message,
            ]);

            if ($diary->notify_email && !empty($recipient->email)) {
                try {
                    Mail::to($recipient->email)->send(new DiaryFollowUpReminderMail($diary, $stage));
                } catch (\Throwable $e) {
                    Log::warning('Diary follow-up email failed to send: ' . $e->getMessage(), [
                        'diary_id'     => $diary->id,
                        'recipient_id' => $recipient->id,
                    ]);
                }
            }
        }
    }

    // ── Index ───────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        self::ensureFollowUpColumnsExist();
        $user = Auth::user();

        $dateFrom = $request->has('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $dateTo   = $request->has('date_to')   ? $request->date_to   : now()->endOfMonth()->toDateString();

        $query = $this->scopedDiaryQuery($user, $request);

        if (!empty($dateFrom)) {
            $query->where(function ($q) use ($dateFrom) {
                $q->whereDate('event_date', '>=', $dateFrom)
                  ->orWhereNull('event_date');
            });
        }
        if (!empty($dateTo)) {
            $query->where(function ($q) use ($dateTo) {
                $q->whereDate('event_date', '<=', $dateTo)
                  ->orWhereNull('event_date');
            });
        }

        $totalEntries   = (int) (clone $query)->count();
        $personalCount  = (int) (clone $query)->where('type', 'personal')->count();
        $shopCount      = (int) (clone $query)->where('type', 'shop')->count();
        $followUpCount  = (int) (clone $query)->where('needs_follow_up', true)->count();
        $noFollowUp     = (int) (clone $query)->where('needs_follow_up', false)->count();
        $totalCost      = (float) (clone $query)->sum('cost');

        $canUseShopDiary = $this->canUseShopDiary($user);

        return view('diary.index', compact(
            'totalEntries',
            'personalCount',
            'shopCount',
            'followUpCount',
            'noFollowUp',
            'totalCost',
            'dateFrom',
            'dateTo',
            'user',
            'canUseShopDiary'
        ));
    }

    // ── DataTables JSON ─────────────────────────────────────────────────────────
    public function data(Request $request)
    {
        self::ensureFollowUpColumnsExist();
        $user  = Auth::user();
        $query = $this->scopedDiaryQuery($user, $request);

        $dateFrom = $request->has('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $dateTo   = $request->has('date_to')   ? $request->date_to   : now()->endOfMonth()->toDateString();

        if (!empty($dateFrom)) {
            $query->where(function ($q) use ($dateFrom) {
                $q->whereDate('event_date', '>=', $dateFrom)
                  ->orWhereNull('event_date');
            });
        }
        if (!empty($dateTo)) {
            $query->where(function ($q) use ($dateTo) {
                $q->whereDate('event_date', '<=', $dateTo)
                  ->orWhereNull('event_date');
            });
        }

        if ($request->filled('needs_follow_up') && $request->needs_follow_up !== '') {
            $query->where('needs_follow_up', (bool) $request->needs_follow_up);
        }

        $recordsTotal    = (clone $query)->count();
        $personalCount   = (int) (clone $query)->where('type', 'personal')->count();
        $shopCount       = (int) (clone $query)->where('type', 'shop')->count();
        $followUpCount   = (int) (clone $query)->where('needs_follow_up', true)->count();
        $noFollowUp      = (int) (clone $query)->where('needs_follow_up', false)->count();
        $totalCost       = (float) (clone $query)->sum('cost');

        // Search
        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('event_name', 'like', "%{$searchValue}%")
                  ->orWhere('event_description', 'like', "%{$searchValue}%")
                  ->orWhereHas('user', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('shop', function ($sq) use ($searchValue) {
                      $sq->where('shop_name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir         = $request->input('order.0.dir', 'desc');
        $columns          = ['id', 'type', 'event_name', 'event_date', 'due_date', 'cost', 'needs_follow_up', 'created_at'];
        $orderColumn      = $columns[$orderColumnIndex] ?? 'created_at';
        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start   = (int) $request->input('start', 0);
        $length  = (int) $request->input('length', 15);
        $diaries = $length > 0
            ? $query->skip($start)->take($length)->get()
            : $query->get();

        $data = $diaries->map(function ($diary) use ($user) {
            $canManage = $this->canManageDiary($user, $diary);

            // Type badge
            if ($diary->type === 'shop') {
                $shopName = $diary->shop ? e($diary->shop->shop_name) : 'Shop';
                $typeBadge = '<span class="badge" style="background:rgba(56,139,253,0.15);color:#58a6ff;border:1px solid rgba(56,139,253,0.3);font-size:0.72rem;">
                                <i class="bi bi-shop me-1"></i> ' . $shopName . '
                              </span>';
            } else {
                $typeBadge = '<span class="badge" style="background:rgba(163,113,247,0.15);color:#a371f7;border:1px solid rgba(163,113,247,0.3);font-size:0.72rem;">
                                <i class="bi bi-person-fill me-1"></i> Personal
                              </span>';
            }

            // Follow-up status badge
            $emailIcon = $diary->notify_email
                ? ' <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle ms-1" style="font-size:0.65rem;" title="Email notification enabled"><i class="bi bi-envelope-fill"></i></span>'
                : '';

            if ($diary->needs_follow_up) {
                $stage = $diary->currentTriggerStage();
                if ($diary->isOverdue()) {
                    $statusBadge = '<span class="badge" style="background:rgba(233,69,96,0.15);color:#e94560;border:1px solid rgba(233,69,96,0.3);font-size:0.72rem;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue Follow-up
                                   </span>' . $emailIcon;
                } elseif ($stage === 'due') {
                    $statusBadge = '<span class="badge" style="background:rgba(220,38,38,0.14);color:#dc2626;border:1px solid rgba(220,38,38,0.3);font-size:0.72rem;">
                                        <i class="bi bi-alarm-fill me-1"></i>Due Today
                                   </span>' . $emailIcon;
                } elseif ($stage === 'reminder') {
                    $statusBadge = '<span class="badge" style="background:rgba(2,132,199,0.14);color:#0284c7;border:1px solid rgba(2,132,199,0.3);font-size:0.72rem;">
                                        <i class="bi bi-bell-fill me-1"></i>Reminder Active
                                   </span>' . $emailIcon;
                } else {
                    $statusBadge = '<span class="badge" style="background:rgba(227,179,65,0.15);color:#d97706;border:1px solid rgba(227,179,65,0.3);font-size:0.72rem;">
                                        <i class="bi bi-bell-fill me-1"></i>Needs Follow-up
                                   </span>' . $emailIcon;
                }
            } else {
                $statusBadge = '<span class="badge" style="background:rgba(63,185,80,0.15);color:#3fb950;border:1px solid rgba(63,185,80,0.3);font-size:0.72rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i>No Follow-up
                               </span>';
            }

            // Event date
            $eventDate = $diary->event_date
                ? '<span>' . $diary->event_date->format('d M Y') . '</span>'
                : '<span class="text-muted" style="font-size:0.78rem;">—</span>';

            // Due date + Reminder date sub-label
            $effectiveRem = $diary->effective_reminder_date;
            if ($diary->due_date) {
                $dueDateColor = (($diary->due_date->isPast() || $diary->due_date->isToday()) && $diary->needs_follow_up)
                    ? '#e94560'
                    : 'var(--text-secondary)';
                $dueDate = '<div><span class="fw-600" style="color:' . $dueDateColor . ';">' . $diary->due_date->format('d M Y') . '</span>';
                if ($diary->needs_follow_up && $effectiveRem) {
                    $dueDate .= '<div class="small text-muted" style="font-size:0.69rem;"><i class="bi bi-alarm me-1"></i>Rem: ' . $effectiveRem->format('d M Y') . ' &middot; ' . e($diary->formatted_reminder_time) . '</div>';
                }
                $dueDate .= '</div>';
            } else {
                $dueDate = '<span class="text-muted" style="font-size:0.78rem;">—</span>';
            }

            // Cost
            $cost = $diary->cost !== null
                ? '<span>TZS ' . number_format((float) $diary->cost, 0) . '</span>'
                : '<span class="text-muted" style="font-size:0.78rem;">—</span>';

            // Description
            $desc = e($diary->event_description);
            $descDisplay = strlen($diary->event_description) > 75
                ? '<span title="' . $desc . '" data-bs-toggle="tooltip">' . e(substr($diary->event_description, 0, 75)) . '…</span>'
                : '<span>' . $desc . '</span>';

            // Event title with creator info
            $creator = $diary->user ? e($diary->user->name) : 'User';
            $eventTitle = '<div>
                            <span class="fw-600">' . e($diary->event_name) . '</span>
                            <div class="small text-muted" style="font-size:0.70rem;">By: ' . $creator . '</div>
                           </div>';

            $remDisplay = ($diary->needs_follow_up && $effectiveRem) ? $effectiveRem->format('d M Y') : '—';
            $remRaw     = ($diary->needs_follow_up && $effectiveRem) ? $effectiveRem->toDateString() : '';
            $remTimeRaw = $diary->reminder_time_value;
            $remTimeDisp = $diary->formatted_reminder_time;

            // Action buttons
            $viewBtn = '<button type="button" class="btn btn-sm btn-outline-info btn-view-diary me-1"
                            style="font-size:0.72rem;padding:3px 8px;"
                            data-id="' . $diary->id . '"
                            data-type="' . $diary->type . '"
                            data-shop-name="' . ($diary->shop ? e($diary->shop->shop_name) : '') . '"
                            data-user-name="' . $creator . '"
                            data-event-name="' . e($diary->event_name) . '"
                            data-event-description="' . e($diary->event_description) . '"
                            data-event-date="' . ($diary->event_date ? $diary->event_date->format('d M Y') : '—') . '"
                            data-due-date="' . ($diary->due_date ? $diary->due_date->format('d M Y') : '—') . '"
                            data-reminder-date="' . $remDisplay . '"
                            data-reminder-time="' . e($remTimeDisp) . '"
                            data-remind-days-before="' . (int) ($diary->remind_days_before ?? 1) . '"
                            data-notify-email="' . ($diary->notify_email ? '1' : '0') . '"
                            data-cost="' . ($diary->cost !== null ? 'TZS ' . number_format((float) $diary->cost, 0) : '—') . '"
                            data-needs-follow-up="' . ($diary->needs_follow_up ? '1' : '0') . '"
                            data-created-at="' . $diary->created_at->format('d M Y, h:i A') . '"
                            title="View Details">
                            <i class="bi bi-eye-fill"></i>
                        </button>';

            $editBtn = '';
            $deleteBtn = '';

            if ($canManage) {
                $editBtn = '<button type="button" class="btn btn-sm btn-outline-warning btn-edit-diary me-1"
                                style="font-size:0.72rem;padding:3px 8px;"
                                data-id="' . $diary->id . '"
                                data-type="' . $diary->type . '"
                                data-shop-id="' . ($diary->shop_id ?? '') . '"
                                data-event-name="' . e($diary->event_name) . '"
                                data-event-description="' . e($diary->event_description) . '"
                                data-event-date="' . ($diary->event_date ? $diary->event_date->toDateString() : '') . '"
                                data-due-date="' . ($diary->due_date ? $diary->due_date->toDateString() : '') . '"
                                data-reminder-date="' . $remRaw . '"
                                data-reminder-time="' . e($remTimeRaw) . '"
                                data-remind-days-before="' . (int) ($diary->remind_days_before ?? 1) . '"
                                data-notify-email="' . ($diary->notify_email ? '1' : '0') . '"
                                data-cost="' . ($diary->cost ?? '') . '"
                                data-needs-follow-up="' . ($diary->needs_follow_up ? '1' : '0') . '"
                                title="Edit">
                                <i class="bi bi-pencil-fill"></i>
                            </button>';

                $deleteBtn = '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-diary"
                                style="font-size:0.72rem;padding:3px 8px;"
                                data-id="' . $diary->id . '"
                                data-name="' . e($diary->event_name) . '"
                                title="Delete">
                                <i class="bi bi-trash-fill"></i>
                            </button>';
            }

            return [
                'id'                => $diary->id,
                'type'              => $typeBadge,
                'event_name'        => $eventTitle,
                'event_description' => $descDisplay,
                'event_date'        => $eventDate,
                'due_date'          => $dueDate,
                'cost'              => $cost,
                'status'            => $statusBadge,
                'actions'           => $viewBtn . $editBtn . $deleteBtn,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'totals' => [
                'total_entries'  => $recordsTotal,
                'personal_count' => $personalCount,
                'shop_count'     => $shopCount,
                'follow_up'      => $followUpCount,
                'no_follow_up'   => $noFollowUp,
                'total_cost'     => $totalCost,
            ],
        ]);
    }

    // ── Poll Due / Reminder-Reached Diary Follow-ups ────────────────────────────
    public function dueReminders(Request $request)
    {
        self::ensureFollowUpColumnsExist();
        $user = Auth::user();
        if (!$user) {
            return response()->json(['due_items' => []], 401);
        }

        $now = now();
        $today = $now->copy()->startOfDay();
        $todayStr = $today->toDateString();

        $candidates = $this->scopedDiaryQuery($user)
            ->where('needs_follow_up', true)
            ->whereNotNull('due_date')
            ->where(function ($q) use ($todayStr) {
                $q->whereDate('due_date', '<=', $todayStr)
                  ->orWhereDate('reminder_date', '<=', $todayStr);
            })
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $dueItems = [];

        foreach ($candidates as $diary) {
            $stage = $diary->currentTriggerStage($now);
            if (!$stage) {
                continue;
            }

            // Generate in-app notification + optional email ONCE when milestone reached
            $this->dispatchFollowUpNotificationOnce($diary, $stage);

            // Show popup ONCE until user clicks OK or Reschedule
            $ackColumn = $stage === 'due' ? 'due_popup_acknowledged_at' : 'reminder_popup_acknowledged_at';
            if ($diary->{$ackColumn} !== null) {
                continue;
            }

            $effectiveRem = $diary->effective_reminder_date;
            $daysUntilDue = (int) $today->diffInDays($diary->due_date->copy()->startOfDay(), false);

            $dueItems[] = [
                'id'                   => $diary->id,
                'stage'                => $stage, // 'due' or 'reminder'
                'type'                 => $diary->type,
                'shop_name'            => $diary->shop?->shop_name,
                'event_name'           => $diary->event_name,
                'event_description'    => $diary->event_description,
                'event_date'           => $diary->event_date ? $diary->event_date->toDateString() : null,
                'event_date_display'   => $diary->event_date ? $diary->event_date->format('d M Y') : null,
                'due_date'             => $diary->due_date->toDateString(),
                'due_date_display'     => $diary->due_date->format('d M Y'),
                'reminder_date'        => $effectiveRem ? $effectiveRem->toDateString() : $diary->due_date->toDateString(),
                'reminder_date_display'=> $effectiveRem ? $effectiveRem->format('d M Y') : $diary->due_date->format('d M Y'),
                'reminder_time'        => $diary->reminder_time_value,
                'reminder_time_display'=> $diary->formatted_reminder_time,
                'remind_days_before'   => max(1, (int) ($diary->remind_days_before ?? 1)),
                'notify_email'         => (bool) $diary->notify_email,
                'days_until_due'       => $daysUntilDue,
                'is_overdue'           => $daysUntilDue < 0,
                'cost_display'         => $diary->cost !== null ? ('TZS ' . number_format((float) $diary->cost, 0)) : null,
                'can_reschedule'       => $this->canManageDiary($user, $diary),
                'acknowledge_url'      => route('diary.acknowledge', $diary),
                'reschedule_url'       => route('diary.reschedule', $diary),
            ];
        }

        $customRingtone = Setting::get('notification_sound_user_' . $user->id);
        $ringtoneUrl = ($customRingtone && Storage::disk('public')->exists($customRingtone))
            ? asset('media/' . $customRingtone)
            : null;

        return response()->json([
            'due_items'    => $dueItems,
            'count'        => count($dueItems),
            'diary_url'    => route('diary.index'),
            'ringtone_url' => $ringtoneUrl,
        ]);
    }

    // ── Acknowledge Popup Once (OK Button) ──────────────────────────────────────
    public function acknowledgeReminder(Request $request, Diary $diary)
    {
        self::ensureFollowUpColumnsExist();
        $user = Auth::user();

        if (!$this->canViewDiary($user, $diary)) {
            abort(403, 'Unauthorized action.');
        }

        $stage = $request->input('stage') ?: ($diary->currentTriggerStage() ?: 'due');
        $now = now();

        if ($stage === 'due') {
            $diary->update([
                'reminder_popup_acknowledged_at' => $diary->reminder_popup_acknowledged_at ?? $now,
                'due_popup_acknowledged_at'      => $now,
            ]);
        } else {
            $diary->update([
                'reminder_popup_acknowledged_at' => $now,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Follow-up reminder acknowledged.',
        ]);
    }

    // ── Reschedule Follow-up (Update Due Date & Reminder Date) ──────────────────
    public function reschedule(Request $request, Diary $diary)
    {
        self::ensureFollowUpColumnsExist();
        $user = Auth::user();

        if (!$this->canManageDiary($user, $diary)) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'due_date'      => 'required|date',
            'reminder_date' => 'required|date|before:due_date',
            'reminder_time' => 'nullable|date_format:H:i',
            'notify_email'  => 'nullable|boolean',
        ], [
            'due_date.required'      => 'Please enter the new due date.',
            'reminder_date.required' => 'Please enter the new reminder date.',
            'reminder_date.before'   => 'Reminder date must be at least 1 day before the due date.',
        ]);

        $due = Carbon::parse($validated['due_date'])->startOfDay();
        $rem = Carbon::parse($validated['reminder_date'])->startOfDay();
        $daysBefore = (int) $rem->diffInDays($due, false);

        if ($daysBefore < 1) {
            return response()->json([
                'message' => 'Reminder date must be at least 1 day before the due date.',
                'errors'  => ['reminder_date' => ['Reminder date must be at least 1 day before the due date.']],
            ], 422);
        }

        if ($diary->event_date) {
            $event = $diary->event_date->copy()->startOfDay();
            $maxDays = (int) $event->diffInDays($due, false);
            if ($maxDays < 1) {
                return response()->json([
                    'message' => 'New due date must be at least 1 day after the event date.',
                    'errors'  => ['due_date' => ['New due date must be at least 1 day after the event date.']],
                ], 422);
            }
            if ($daysBefore > $maxDays) {
                return response()->json([
                    'message' => "Reminder date cannot be earlier than the event date (max {$maxDays} day(s) before due date).",
                    'errors'  => ['reminder_date' => ["Reminder date cannot be earlier than the event date (max {$maxDays} day(s) before due date)."]],
                ], 422);
            }
        }

        $reminderTime = !empty($validated['reminder_time'])
            ? $validated['reminder_time']
            : $diary->reminder_time_value;

        $updateData = [
            'due_date'                       => $due->toDateString(),
            'reminder_date'                  => $rem->toDateString(),
            'reminder_time'                  => $reminderTime,
            'remind_days_before'             => $daysBefore,
            'needs_follow_up'                => true,
            'reminder_notified_at'           => null,
            'due_notified_at'                => null,
            'reminder_popup_acknowledged_at' => null,
            'due_popup_acknowledged_at'      => null,
        ];

        if ($request->has('notify_email')) {
            $updateData['notify_email'] = $request->boolean('notify_email');
        }

        $diary->update($updateData);
        $diary->refresh();

        return response()->json([
            'success' => true,
            'message' => "Rescheduled \"{$diary->event_name}\" — Reminder: {$rem->format('d M Y')} at {$diary->formatted_reminder_time}, Due: {$due->format('d M Y')}.",
            'diary'   => [
                'id'                 => $diary->id,
                'due_date'           => $due->toDateString(),
                'reminder_date'      => $rem->toDateString(),
                'reminder_time'      => $diary->reminder_time_value,
                'remind_days_before' => $daysBefore,
                'notify_email'       => (bool) $diary->notify_email,
            ],
        ]);
    }

    // ── Store ───────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        self::ensureFollowUpColumnsExist();
        $user = Auth::user();

        if ($request->filled('cost')) {
            $request->merge(['cost' => str_replace(',', '', (string) $request->input('cost'))]);
        }

        $rules = [
            'event_name'         => 'required|string|max:255',
            'event_description'  => 'required|string|max:5000',
            'event_date'         => 'nullable|date',
            'due_date'           => 'nullable|date|after_or_equal:event_date',
            'cost'               => 'nullable|numeric|min:0',
            'needs_follow_up'    => 'nullable|boolean',
            'remind_days_before' => 'nullable|integer|min:0|max:365',
            'reminder_date'      => 'nullable|date|before_or_equal:due_date',
            'reminder_time'      => 'nullable|date_format:H:i',
            'notify_email'       => 'nullable|boolean',
        ];

        $canUseShopDiary = $this->canUseShopDiary($user);

        if (!$canUseShopDiary) {
            $rules['type'] = 'nullable|in:personal';
        } else {
            $rules['type'] = 'required|in:personal,shop';
        }

        $validated = $request->validate($rules);

        if (!$canUseShopDiary) {
            $validated['type']    = 'personal';
            $validated['shop_id'] = null;
        } else {
            if ($validated['type'] === 'shop') {
                if (!$user->shop_id) {
                    return back()->withErrors(['type' => 'You are not assigned to any shop to record a shop diary entry.'])->withInput();
                }
                $validated['shop_id'] = $user->shop_id;
            } else {
                $validated['shop_id'] = null;
            }
        }

        $validated['user_id'] = $user->id;
        $validated = $this->resolveFollowUpFields($request, $validated);

        Diary::create($validated);

        return redirect()->route('diary.index')
            ->with('success', 'Diary entry recorded successfully.');
    }

    // ── Update ──────────────────────────────────────────────────────────────────
    public function update(Request $request, Diary $diary)
    {
        self::ensureFollowUpColumnsExist();
        $user = Auth::user();

        if (!$this->canManageDiary($user, $diary)) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->filled('cost')) {
            $request->merge(['cost' => str_replace(',', '', (string) $request->input('cost'))]);
        }

        $rules = [
            'event_name'         => 'required|string|max:255',
            'event_description'  => 'required|string|max:5000',
            'event_date'         => 'nullable|date',
            'due_date'           => 'nullable|date|after_or_equal:event_date',
            'cost'               => 'nullable|numeric|min:0',
            'needs_follow_up'    => 'nullable|boolean',
            'remind_days_before' => 'nullable|integer|min:0|max:365',
            'reminder_date'      => 'nullable|date|before_or_equal:due_date',
            'reminder_time'      => 'nullable|date_format:H:i',
            'notify_email'       => 'nullable|boolean',
        ];

        $canUseShopDiary = $this->canUseShopDiary($user);

        if (!$canUseShopDiary) {
            $rules['type'] = 'nullable|in:personal';
        } else {
            $rules['type'] = 'required|in:personal,shop';
        }

        $validated = $request->validate($rules);

        if (!$canUseShopDiary) {
            $validated['type']    = 'personal';
            $validated['shop_id'] = null;
        } else {
            if ($validated['type'] === 'shop') {
                if (!$user->shop_id) {
                    return back()->withErrors(['type' => 'You are not assigned to any shop to record a shop diary entry.'])->withInput();
                }
                $validated['shop_id'] = $user->shop_id;
            } else {
                $validated['shop_id'] = null;
            }
        }

        $oldDueDate = $diary->due_date?->toDateString();
        $oldRemDate = $diary->reminder_date?->toDateString();
        $oldRemTime = $diary->reminder_time_value;
        $oldFollowUp = (bool) $diary->needs_follow_up;

        $validated = $this->resolveFollowUpFields($request, $validated);

        $newDueDate = $validated['due_date'] ?? null;
        $newRemDate = $validated['reminder_date'] ?? null;
        $newRemTime = $validated['reminder_time'] ?? '10:00';

        // If schedule changed or follow-up was re-enabled, reset notification/popup flags
        if ($validated['needs_follow_up'] && (!$oldFollowUp || $oldDueDate !== $newDueDate || $oldRemDate !== $newRemDate || $oldRemTime !== $newRemTime)) {
            $validated['reminder_notified_at']           = null;
            $validated['due_notified_at']                = null;
            $validated['reminder_popup_acknowledged_at'] = null;
            $validated['due_popup_acknowledged_at']      = null;
        }

        $diary->update($validated);

        return redirect()->route('diary.index')
            ->with('success', 'Diary entry updated successfully.');
    }

    // ── Destroy ─────────────────────────────────────────────────────────────────
    public function destroy(Diary $diary)
    {
        $user = Auth::user();

        if (!$this->canManageDiary($user, $diary)) {
            abort(403, 'Unauthorized action.');
        }

        $diary->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Diary entry deleted.']);
        }

        return redirect()->route('diary.index')
            ->with('success', 'Diary entry deleted successfully.');
    }
}
