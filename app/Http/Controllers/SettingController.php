<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Sale;
use App\Models\ShopStock;
use App\Models\MainStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $systemName   = '';
        $slogan       = '';
        $logo         = '';
        $printerEnabled = Setting::get('printer_enabled_user_' . $user->id, '1');
        $notificationRingtone = Setting::get('notification_sound_user_' . $user->id);
        $storePricingMode = Setting::get('store_pricing_mode', 'DEPENDENT');

        $companyTin         = '';
        $companyAddress     = '';
        $companyBankName    = '';
        $companyBankAccount = '';
        $summaryEmails      = '';
        $summaryTime        = '';

        if ($user->isOwner()) {
            $systemName         = Setting::get('system_name', 'AMSTROOM');
            $slogan             = Setting::get('slogan', 'Technology Innovations');
            $logo               = Setting::get('logo');
            $companyTin         = Setting::get('company_tin', '');
            $companyAddress     = Setting::get('company_address', '');
            $companyBankName    = Setting::get('company_bank_name', '');
            $companyBankAccount = Setting::get('company_bank_account', '');
            $summaryEmails      = Setting::get('summary_report_emails', $user->email);
            $summaryTime        = Setting::get('summary_report_time', '22:00');
        } elseif ($user->isShopAdmin()) {
            $shop = $user->shop;
            $systemName = $shop->shop_name;
            $slogan     = $shop->slogan;
            $logo       = $shop->logo;
            $companyTin         = $shop->tin_number;
            $companyAddress     = $shop->address;
            $companyBankName    = $shop->bank_name;
            $companyBankAccount = $shop->bank_account;
            $summaryEmails      = $shop->summary_emails ?: $user->email;
            $summaryTime        = $shop->summary_time ?: '22:00';
        }

        return view('settings.index', compact(
            'systemName', 'slogan', 'logo', 'printerEnabled', 'notificationRingtone',
            'storePricingMode', 'companyTin', 'companyAddress', 'companyBankName', 'companyBankAccount', 'summaryEmails', 'summaryTime'
        ));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Process ringtone upload for any user role
        if ($request->hasFile('notification_ringtone')) {
            $request->validate([
                'notification_ringtone' => 'required|file|mimes:mp3,wav,ogg,oga|max:5120',
            ]);

            $settingKey = 'notification_sound_user_' . $user->id;
            $oldSound = Setting::get($settingKey);
            if ($oldSound && Storage::disk('public')->exists($oldSound)) {
                Storage::disk('public')->delete($oldSound);
            }

            $path = $request->file('notification_ringtone')->store('ringtones', 'public');
            Setting::set($settingKey, $path);
        }

        if ($user->isOwner()) {
            $request->validate([
                'system_name'        => 'required|string|max:150',
                'slogan'             => 'nullable|string|max:255',
                'logo'               => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'printer_enabled'    => 'required|in:0,1',
                'store_pricing_mode' => 'required|in:DEPENDENT,INDEPENDENT',
                'company_tin'        => 'nullable|string|max:100',
                'company_address'    => 'nullable|string|max:255',
                'company_bank_name'  => 'nullable|string|max:150',
                'company_bank_account' => 'nullable|string|max:100',
                'summary_emails'     => 'nullable|string',
                'summary_time'       => 'nullable|date_format:H:i',
            ]);

            $emailsStr = $request->input('summary_emails', Setting::get('summary_report_emails', $user->email));
            if ($emailsStr === null || trim($emailsStr) === '') {
                $emailsStr = $user->email;
            }

            $emailsArray = array_map('trim', explode(',', $emailsStr));
            foreach ($emailsArray as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return back()->withErrors(['summary_emails' => "The email '{$email}' is not a valid email address."])->withInput();
                }
            }
            if (!in_array($user->email, $emailsArray)) {
                return back()->withErrors(['summary_emails' => "Your own email ({$user->email}) must be included in the list."])->withInput();
            }

            Setting::set('system_name', $request->system_name);
            Setting::set('slogan', $request->slogan);
            Setting::set('printer_enabled_user_' . $user->id, $request->printer_enabled);
            Setting::set('store_pricing_mode', $request->store_pricing_mode);
            Setting::set('company_tin', $request->company_tin);
            Setting::set('company_address', $request->company_address);
            Setting::set('company_bank_name', $request->company_bank_name);
            Setting::set('company_bank_account', $request->company_bank_account);
            Setting::set('summary_report_emails', implode(', ', $emailsArray));
            Setting::set('summary_report_time', $request->input('summary_time', '22:00') ?: '22:00');

            if ($request->hasFile('logo')) {
                $oldLogo = Setting::get('logo');
                if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }

                $path = $request->file('logo')->store('logos', 'public');
                Setting::set('logo', $path);
            }
            
            $msg = 'System branding settings updated successfully.';

        } elseif ($user->isShopAdmin()) {
            $request->validate([
                'system_name' => 'required|string|max:150',
                'slogan'      => 'nullable|string|max:255',
                'logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'printer_enabled' => 'required|in:0,1',
                'company_tin'        => 'nullable|string|max:100',
                'company_address'    => 'nullable|string|max:255',
                'company_bank_name'  => 'nullable|string|max:150',
                'company_bank_account' => 'nullable|string|max:100',
                'summary_emails'     => 'nullable|string',
                'summary_time'       => 'nullable|date_format:H:i',
            ]);

            $emailsStr = $request->input('summary_emails', $user->shop->summary_emails ?: $user->email);
            if ($emailsStr === null || trim($emailsStr) === '') {
                $emailsStr = $user->email;
            }

            $emailsArray = array_map('trim', explode(',', $emailsStr));
            foreach ($emailsArray as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return back()->withErrors(['summary_emails' => "The email '{$email}' is not a valid email address."])->withInput();
                }
            }
            if (!in_array($user->email, $emailsArray)) {
                return back()->withErrors(['summary_emails' => "Your own email ({$user->email}) must be included in the list."])->withInput();
            }

            $shop = $user->shop;
            $shop->shop_name = $request->system_name;
            $shop->slogan = $request->slogan;
            $shop->tin_number = $request->company_tin;
            $shop->address = $request->company_address;
            $shop->bank_name = $request->company_bank_name;
            $shop->bank_account = $request->company_bank_account;
            $shop->summary_emails = implode(', ', $emailsArray);
            $shop->summary_time = $request->input('summary_time', '22:00') ?: '22:00';

            if ($request->hasFile('logo')) {
                if ($shop->logo && Storage::disk('public')->exists($shop->logo)) {
                    Storage::disk('public')->delete($shop->logo);
                }

                $path = $request->file('logo')->store('logos', 'public');
                $shop->logo = $path;
            }
            $shop->save();

            Setting::set('printer_enabled_user_' . $user->id, $request->printer_enabled);
            $msg = 'Shop branding settings updated successfully.';

        } else { // Seller
            $request->validate([
                'printer_enabled' => 'required|in:0,1',
            ]);

            Setting::set('printer_enabled_user_' . $user->id, $request->printer_enabled);
            $msg = 'Printer settings updated successfully.';
        }

        return back()->with('success', $msg);
    }

    public function removeLogo()
    {
        $user = Auth::user();

        if ($user->isOwner()) {
            $oldLogo = Setting::get('logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::set('logo', null);
            $msg = 'Global logo removed. Default icon will be displayed.';
        } elseif ($user->isShopAdmin()) {
            $shop = $user->shop;
            if ($shop->logo && Storage::disk('public')->exists($shop->logo)) {
                Storage::disk('public')->delete($shop->logo);
            }
            $shop->logo = null;
            $shop->save();
            $msg = 'Shop logo removed. Global or default branding will be displayed.';
        } else {
            abort(403);
        }

        return back()->with('success', $msg);
    }

    public function removeRingtone()
    {
        $user = Auth::user();
        $settingKey = 'notification_sound_user_' . $user->id;
        $oldSound = Setting::get($settingKey);
        if ($oldSound && Storage::disk('public')->exists($oldSound)) {
            Storage::disk('public')->delete($oldSound);
        }
        Setting::set($settingKey, null);

        return back()->with('success', 'Custom ringtone removed. Default sound will be used.');
    }

    public function sendSummaryEmail(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user->isOwner() && !$user->isShopAdmin()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            // Get recipients
            $emailsStr = '';
            $scope = '';
            if ($user->isOwner()) {
                $emailsStr = Setting::get('summary_report_emails', $user->email);
                $scope = 'Global System';
            } else {
                $emailsStr = $user->shop->summary_emails ?: $user->email;
                $scope = $user->shop->shop_name;
            }

            $emailsArray = array_map('trim', explode(',', $emailsStr));
            $emailsArray = array_filter($emailsArray, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

            if (empty($emailsArray)) {
                return response()->json(['message' => 'No valid email recipients configured.'], 422);
            }

            // Compile report data
            $reportData = $this->compileReportData($user, $scope);

            \Illuminate\Support\Facades\Mail::to($emailsArray)->send(new \App\Mail\SummaryReportMail($reportData));
            return response()->json(['message' => 'Email sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    public function compileReportData($user, $scope)
    {
        $salesCount = 0;
        $salesTotal = 0;
        $profit = 0;
        $expensesTotal = 0;
        $expensesCategories = [];
        $stockTotalRemaining = 0;
        $lowStockAlertsCount = 0;
        $lowStockItems = [];

        if ($user->isOwner()) {
            $todaySales = Sale::completed()->whereDate('sale_date', today())->where('is_admin_stock', false)->get();
            $salesCount = $todaySales->count();
            $salesTotal = $todaySales->sum(fn($s) => $s->report_revenue);
            $profit     = $todaySales->sum(fn($s) => $s->report_profit);
            
            $expensesTotal = \App\Models\Expense::whereDate('activity_date', today())->sum('amount');
            $expensesCategories = \App\Models\Expense::whereDate('activity_date', today())
                ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
                ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total'))
                ->groupBy('expense_categories.name')
                ->get()
                ->map(fn($c) => ['name' => $c->name, 'total' => $c->total])
                ->toArray();

            $stockTotalRemaining = ShopStock::where('is_admin_stock', false)->sum('remaining_quantity') 
                + MainStock::sum('remaining_quantity');
            $lowStockAlertsCount = ShopStock::where('is_admin_stock', false)->whereColumn('remaining_quantity', '<=', 'low_stock_alert')->count();
            
            $lowStockItems = ShopStock::where('is_admin_stock', false)
                ->whereColumn('remaining_quantity', '<=', 'low_stock_alert')
                ->with(['item', 'shop'])
                ->take(5)
                ->get()
                ->map(fn($st) => [
                    'name' => ($st->item?->item_name ?? 'Item') . ' (' . ($st->shop?->shop_name ?? 'Shop') . ')',
                    'qty'  => $st->remaining_quantity,
                    'alert' => $st->low_stock_alert
                ])
                ->toArray();
        } else {
            $todaySales = Sale::completed()->whereDate('sale_date', today())->where('shop_id', $user->shop_id)->get();
            $salesCount = $todaySales->count();
            $salesTotal = $todaySales->sum(fn($s) => $s->report_revenue);
            $profit     = $todaySales->sum(fn($s) => $s->report_profit);
            
            $userIds = \App\Models\User::where('shop_id', $user->shop_id)->pluck('id');
            $expensesTotal = \App\Models\Expense::whereDate('activity_date', today())
                ->whereIn('recorded_by', $userIds)
                ->sum('amount');
            $expensesCategories = \App\Models\Expense::whereDate('activity_date', today())
                ->whereIn('recorded_by', $userIds)
                ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
                ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total'))
                ->groupBy('expense_categories.name')
                ->get()
                ->map(fn($c) => ['name' => $c->name, 'total' => $c->total])
                ->toArray();

            $stockTotalRemaining = ShopStock::where('shop_id', $user->shop_id)->sum('remaining_quantity');
            $lowStockAlertsCount = ShopStock::where('shop_id', $user->shop_id)->whereColumn('remaining_quantity', '<=', 'low_stock_alert')->count();
            
            $lowStockItems = ShopStock::where('shop_id', $user->shop_id)
                ->whereColumn('remaining_quantity', '<=', 'low_stock_alert')
                ->with('item')
                ->take(5)
                ->get()
                ->map(fn($st) => [
                    'name' => $st->item?->item_name ?? 'Item',
                    'qty'  => $st->remaining_quantity,
                    'alert' => $st->low_stock_alert
                ])
                ->toArray();
        }

        return [
            'scope' => $scope,
            'generated_at' => now()->format('d M Y H:i:s'),
            'sales_count' => $salesCount,
            'sales_total' => $salesTotal,
            'profit' => $profit,
            'expenses_total' => $expensesTotal,
            'expenses_categories' => $expensesCategories,
            'stock_total_remaining' => $stockTotalRemaining,
            'low_stock_alerts' => $lowStockAlertsCount,
            'low_stock_items' => $lowStockItems
        ];
    }
}
