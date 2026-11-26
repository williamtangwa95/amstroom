<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Shop;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DocumentController extends Controller
{
    /**
     * Ensure the documents table exists (allows seamless zero-downtime startup
     * even before `php artisan migrate` is manually executed).
     */
    private function ensureDocumentsTable(): void
    {
        if (Schema::hasTable('documents')) {
            return;
        }

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50)->unique();
            $table->enum('type', ['invoice', 'proforma', 'delivery'])->index();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_id', 100)->nullable();
            $table->string('customer_po_box', 150)->nullable();
            $table->string('deliver_to', 255)->nullable();
            $table->date('issue_date');
            $table->date('delivery_date')->nullable();
            $table->string('delivery_time', 20)->nullable();
            $table->date('validity_date')->nullable();
            $table->string('terms_of_payment', 150)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('issued');
            $table->timestamps();

            $table->index(['shop_id', 'type']);
            $table->index(['sale_id', 'type']);
            $table->index('issue_date');
        });
    }

    /**
     * Base query scoped to the authenticated user's visibility.
     */
    private function scopedDocumentsQuery()
    {
        $user = Auth::user();
        $query = Document::query();

        if ($user->isOwner()) {
            $query->whereHas('sale', function ($q) {
                $q->withTrashed()->where('is_admin_stock', false);
            });
        } else {
            $query->where('documents.shop_id', $user->shop_id);
        }

        return $query;
    }

    /**
     * Base sales query scoped to the authenticated user's visibility.
     */
    private function scopedSalesQuery()
    {
        $user = Auth::user();
        $query = Sale::query();

        if ($user->isOwner()) {
            $query->where('is_admin_stock', false);
        } else {
            $query->where('shop_id', $user->shop_id);
        }

        return $query;
    }

    /**
     * Extract numeric Sale ID from inputs like "9", "#9", "SL-9", "#SL-9".
     */
    private function parseSaleId(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }
        $clean = preg_replace('/[^0-9]/', '', trim($raw));
        if ($clean === '' || (int) $clean <= 0) {
            return null;
        }
        return (int) $clean;
    }

    /**
     * Build company/branch branding metadata for printable documents.
     */
    private function buildCompanyMeta(?Shop $shop): array
    {
        return [
            'name'         => $shop ? ($shop->shop_name ?: Setting::get('system_name', 'AMSTROOM')) : Setting::get('system_name', 'AMSTROOM'),
            'slogan'       => $shop ? ($shop->slogan ?: Setting::get('slogan', '')) : Setting::get('slogan', ''),
            'tin'          => $shop ? ($shop->tin_number ?: Setting::get('company_tin', '')) : Setting::get('company_tin', ''),
            'address'      => $shop ? ($shop->address ?: Setting::get('company_address', '')) : Setting::get('company_address', ''),
            'bank_name'    => $shop ? ($shop->bank_name ?: Setting::get('company_bank_name', '')) : Setting::get('company_bank_name', ''),
            'bank_account' => $shop ? ($shop->bank_account ?: Setting::get('company_bank_account', '')) : Setting::get('company_bank_account', ''),
            'logo'         => $shop ? ($shop->logo ?: Setting::get('logo')) : Setting::get('logo'),
        ];
    }

    /**
     * Unified Documents Hub (Invoices, Proforma Quotes, Delivery Notes).
     */
    public function index(Request $request)
    {
        $this->ensureDocumentsTable();

        $user = Auth::user();
        $activeType = in_array($request->query('type'), ['invoice', 'proforma', 'delivery'], true)
            ? $request->query('type')
            : '';

        $baseQuery = $this->scopedDocumentsQuery();

        $stats = [
            'total'    => (clone $baseQuery)->count(),
            'invoice'  => (clone $baseQuery)->where('type', 'invoice')->count(),
            'proforma' => (clone $baseQuery)->where('type', 'proforma')->count(),
            'delivery' => (clone $baseQuery)->where('type', 'delivery')->count(),
        ];

        $shops = $user->isOwner() ? Shop::orderBy('shop_name')->get() : collect();

        $recentSales = $this->scopedSalesQuery()
            ->with('shop')
            ->latest('id')
            ->take(25)
            ->get();

        return view('documents.index', compact('activeType', 'stats', 'shops', 'recentSales'));
    }

    /**
     * Server-side DataTables feed for Documents.
     */
    public function data(Request $request)
    {
        $this->ensureDocumentsTable();

        $user = Auth::user();
        $canDelete = $user->isOwner() || $user->isShopAdmin();

        $query = $this->scopedDocumentsQuery();

        if ($request->filled('type') && in_array($request->input('type'), ['invoice', 'proforma', 'delivery'], true)) {
            $query->where('documents.type', $request->input('type'));
        }

        if ($user->isOwner() && $request->filled('shop_id')) {
            if ($request->input('shop_id') === 'main') {
                $query->whereNull('documents.shop_id');
            } else {
                $query->where('documents.shop_id', $request->input('shop_id'));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('documents.issue_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('documents.issue_date', '<=', $request->input('date_to'));
        }

        $recordsTotal = (clone $query)->count();

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $cleanId = preg_replace('/[^0-9]/', '', $search);
            $query->where(function ($q) use ($search, $cleanId) {
                $q->where('documents.document_number', 'like', "%{$search}%")
                  ->orWhere('documents.customer_name', 'like', "%{$search}%")
                  ->orWhere('documents.customer_id', 'like', "%{$search}%")
                  ->orWhere('documents.deliver_to', 'like', "%{$search}%")
                  ->orWhere('documents.type', 'like', "%{$search}%")
                  ->orWhereHas('shop', function ($sq) use ($search) {
                      $sq->where('shop_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('creator', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });

                if ($cleanId !== '') {
                    $q->orWhere('documents.sale_id', (int) $cleanId);
                }
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $documents = $query->with(['shop', 'creator', 'sale'])
            ->orderByDesc('documents.issue_date')
            ->orderByDesc('documents.id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($documents as $idx => $doc) {
            $typeBadge = match ($doc->type) {
                'invoice'  => '<span class="badge-soft-primary"><i class="bi bi-file-earmark-text-fill"></i> Invoice</span>',
                'proforma' => '<span class="badge-soft-warning"><i class="bi bi-file-earmark-ruled-fill"></i> Proforma</span>',
                'delivery' => '<span class="badge-soft-success"><i class="bi bi-truck"></i> Delivery Note</span>',
                default    => '<span class="badge-soft-secondary">' . e(ucfirst($doc->type)) . '</span>',
            };

            $docNumColor = match ($doc->type) {
                'invoice'  => '#0284c7',
                'proforma' => '#b45309',
                'delivery' => '#059669',
                default    => '#0f172a',
            };

            $printUrl = route('documents.print', $doc);
            $docNumberHtml = '<a href="' . $printUrl . '" target="_blank" class="fw-700 text-decoration-none" style="color:' . $docNumColor . ';">' . e($doc->document_number) . '</a>';

            $saleRefHtml = '<a href="' . route('sales.show', $doc->sale_id) . '" class="badge-soft-secondary text-decoration-none">#SL-' . $doc->sale_id . '</a>';

            $customerHtml = '<div class="fw-600 text-dark">' . e($doc->customer_name ?: 'Walk-in Customer') . '</div>';
            if ($doc->customer_id || $doc->deliver_to) {
                $subParts = array_filter([
                    $doc->customer_id ? 'ID: ' . $doc->customer_id : null,
                    $doc->deliver_to ? $doc->deliver_to : null,
                ]);
                $customerHtml .= '<div class="text-muted" style="font-size:.71rem;">' . e(implode(' · ', $subParts)) . '</div>';
            }

            $shopHtml = e($doc->shop?->shop_name ?? 'Main Store (Owner)');
            $amountHtml = '<strong class="text-dark">TZS ' . number_format((float) $doc->total_amount, 0) . '</strong>';
            $issueDateHtml = $doc->issue_date ? $doc->issue_date->format('M d, Y') : '—';
            $creatorHtml = e($doc->creator?->name ?? 'System');

            $editPayload = htmlspecialchars(json_encode([
                'id'               => $doc->id,
                'document_number'  => $doc->document_number,
                'type'             => $doc->type,
                'type_label'       => $doc->type_label,
                'sale_id'          => $doc->sale_id,
                'customer_name'    => $doc->customer_name ?? '',
                'customer_id'      => $doc->customer_id ?? '',
                'customer_po_box'  => $doc->customer_po_box ?? '',
                'deliver_to'       => $doc->deliver_to ?? '',
                'issue_date'       => $doc->issue_date ? $doc->issue_date->format('Y-m-d') : '',
                'delivery_date'    => $doc->delivery_date ? $doc->delivery_date->format('Y-m-d') : '',
                'delivery_time'    => $doc->delivery_time ?? '',
                'validity_date'    => $doc->validity_date ? $doc->validity_date->format('Y-m-d') : '',
                'terms_of_payment' => $doc->terms_of_payment ?? '',
                'notes'            => $doc->notes ?? '',
                'update_url'       => route('documents.update', $doc),
            ]), ENT_QUOTES, 'UTF-8');

            $actionsHtml = '<div class="action-btn-group justify-content-end">';
            $actionsHtml .= '<a href="' . $printUrl . '" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" title="View / Print Document"><i class="bi bi-printer-fill"></i></a>';
            $actionsHtml .= '<button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-edit-doc" data-doc="' . $editPayload . '" title="Edit Document Details"><i class="bi bi-pencil-square"></i></button>';
            if ($canDelete) {
                $actionsHtml .= '<form method="POST" action="' . route('documents.destroy', $doc) . '" class="d-inline">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" data-confirm="Delete ' . e($doc->document_number) . '?" data-text="This removes the document record only; the underlying Sale #SL-' . $doc->sale_id . ' will not be affected." data-confirm-btn="Yes, delete"><i class="bi bi-trash"></i></button>'
                    . '</form>';
            }
            $actionsHtml .= '</div>';

            $data[] = [
                'iteration'       => $start + $idx + 1,
                'document_number' => $docNumberHtml,
                'type'            => $typeBadge,
                'sale_ref'        => $saleRefHtml,
                'customer'        => $customerHtml,
                'shop'            => $shopHtml,
                'amount'          => $amountHtml,
                'issue_date'      => $issueDateHtml,
                'creator'         => $creatorHtml,
                'actions'         => $actionsHtml,
            ];
        }

        // Also return live scoped counts so KPI cards stay fresh after create/delete
        $countBase = $this->scopedDocumentsQuery();
        $stats = [
            'total'    => (clone $countBase)->count(),
            'invoice'  => (clone $countBase)->where('type', 'invoice')->count(),
            'proforma' => (clone $countBase)->where('type', 'proforma')->count(),
            'delivery' => (clone $countBase)->where('type', 'delivery')->count(),
        ];

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'stats'           => $stats,
        ]);
    }

    /**
     * AJAX lookup for a Sale by Sale ID (accepts "9", "#9", "SL-9", "#SL-9").
     */
    public function lookupSale(Request $request)
    {
        $this->ensureDocumentsTable();

        $saleId = $this->parseSaleId($request->query('sale_id'));
        if (!$saleId) {
            return response()->json([
                'found'   => false,
                'message' => 'Please enter a valid Sale ID (e.g. 9 or #SL-9).',
            ], 422);
        }

        $sale = $this->scopedSalesQuery()
            ->with(['shop', 'seller', 'items.item', 'items.components.item'])
            ->where('id', $saleId)
            ->first();

        if (!$sale) {
            return response()->json([
                'found'   => false,
                'message' => "Sale #SL-{$saleId} was not found or is not accessible from your branch.",
            ], 404);
        }

        $parentItems = $sale->items->whereNull('parent_id')->values();
        $itemsPreview = $parentItems->map(function ($si) {
            return [
                'name'       => $si->display_name,
                'qty'        => (int) $si->quantity,
                'unit'       => $si->unit_symbol,
                'price'      => (float) $si->selling_price,
                'subtotal'   => (float) ($si->quantity * $si->selling_price),
                'components' => $si->components->map(fn ($c) => [
                    'name' => $c->display_name,
                    'qty'  => (int) $c->quantity,
                    'unit' => $c->unit_symbol,
                ])->values()->all(),
            ];
        })->all();

        $existingDocs = Document::where('sale_id', $sale->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($d) => [
                'id'              => $d->id,
                'type'            => $d->type,
                'type_label'      => $d->type_label,
                'document_number' => $d->document_number,
                'print_url'       => route('documents.print', $d),
            ])->values()->all();

        return response()->json([
            'found' => true,
            'sale'  => [
                'id'               => $sale->id,
                'sale_ref'         => '#SL-' . $sale->id,
                'status'           => $sale->status,
                'status_label'     => $sale->status === 'draft_proforma' ? 'Proforma Draft' : 'Completed',
                'sale_date'        => $sale->sale_date ? $sale->sale_date->format('M d, Y') : '—',
                'shop_name'        => $sale->shop?->shop_name ?? 'Main Store (Owner)',
                'seller_name'      => $sale->seller?->name ?? '—',
                'payment_method'   => ucfirst(str_replace('_', ' ', $sale->payment_method ?? 'cash')),
                'total_amount'     => (float) $sale->total_amount,
                'formatted_total'  => 'TZS ' . number_format((float) $sale->total_amount, 0),
                'items_count'      => $parentItems->count(),
                'items'            => $itemsPreview,
                'customer_name'    => $sale->customer_name ?? '',
                'customer_id'      => $sale->customer_id ?? '',
                'customer_po_box'  => $sale->customer_po_box ?? '',
                'deliver_to'       => $sale->deliver_to ?? '',
                'delivery_date'    => $sale->delivery_date ? $sale->delivery_date->format('Y-m-d') : '',
                'delivery_time'    => $sale->delivery_time ?? '',
                'validity_date'    => $sale->validity_date ? $sale->validity_date->format('Y-m-d') : now()->addDays(14)->format('Y-m-d'),
                'terms_of_payment' => $sale->terms_of_payment ?? '',
                'existing_docs'    => $existingDocs,
            ],
        ]);
    }

    /**
     * Create a new Invoice, Proforma Invoice, or Delivery Note linked to a Sale ID.
     */
    public function store(Request $request)
    {
        $this->ensureDocumentsTable();

        $validated = $request->validate([
            'type'             => 'required|in:invoice,proforma,delivery',
            'sale_id'          => 'required|string|max:40',
            'customer_name'    => 'nullable|string|max:150',
            'customer_id'      => 'nullable|string|max:100',
            'customer_po_box'  => 'nullable|string|max:150',
            'deliver_to'       => 'nullable|string|max:255',
            'issue_date'       => 'nullable|date',
            'delivery_date'    => 'nullable|date',
            'delivery_time'    => 'nullable|string|max:20',
            'validity_date'    => 'nullable|date',
            'terms_of_payment' => 'nullable|string|max:150',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $saleId = $this->parseSaleId($validated['sale_id']);
        if (!$saleId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Sale ID format. Enter a number such as 9 or #SL-9.',
            ], 422);
        }

        $sale = $this->scopedSalesQuery()->where('id', $saleId)->first();
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => "Sale #SL-{$saleId} was not found or does not belong to your branch.",
            ], 404);
        }

        $type = $validated['type'];
        if (in_array($type, ['invoice', 'delivery'], true) && $sale->status !== 'completed') {
            $label = Document::TYPES[$type];
            return response()->json([
                'success' => false,
                'message' => "A {$label} can only be created for a completed sale. Sale #SL-{$sale->id} is currently a Proforma Draft.",
            ], 422);
        }

        $document = Document::create([
            'document_number'  => Document::generateNumber($type),
            'type'             => $type,
            'sale_id'          => $sale->id,
            'shop_id'          => $sale->shop_id,
            'created_by'       => Auth::id(),
            'customer_name'    => $validated['customer_name'] ?: ($sale->customer_name ?: 'Walk-in Customer'),
            'customer_id'      => $validated['customer_id'] ?? $sale->customer_id,
            'customer_po_box'  => $validated['customer_po_box'] ?? $sale->customer_po_box,
            'deliver_to'       => $validated['deliver_to'] ?? $sale->deliver_to,
            'issue_date'       => $validated['issue_date'] ?: now()->toDateString(),
            'delivery_date'    => $validated['delivery_date'] ?? ($sale->delivery_date ? $sale->delivery_date->toDateString() : null),
            'delivery_time'    => $validated['delivery_time'] ?? $sale->delivery_time,
            'validity_date'    => $type === 'proforma'
                ? ($validated['validity_date'] ?: ($sale->validity_date ? $sale->validity_date->toDateString() : now()->addDays(14)->toDateString()))
                : ($validated['validity_date'] ?? null),
            'terms_of_payment' => $validated['terms_of_payment'] ?? $sale->terms_of_payment,
            'notes'            => $validated['notes'] ?? null,
            'total_amount'     => $sale->total_amount,
            'status'           => $type === 'delivery' ? 'delivered' : 'issued',
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'         => true,
                'message'         => "{$document->type_label} {$document->document_number} created successfully for Sale #SL-{$sale->id}.",
                'document_id'     => $document->id,
                'document_number' => $document->document_number,
                'print_url'       => route('documents.print', $document),
            ]);
        }

        return redirect()->route('documents.index', ['type' => $type])
            ->with('success', "{$document->type_label} {$document->document_number} created successfully.");
    }

    /**
     * Render printable A4 Invoice, Proforma Invoice, or Delivery Note.
     */
    public function print(Document $document)
    {
        $this->ensureDocumentsTable();

        $user = Auth::user();
        $document->load(['sale.shop', 'sale.seller', 'sale.items.item.category', 'sale.items.components.item', 'shop', 'creator']);

        if ($user->isOwner()) {
            if ($document->sale && $document->sale->is_admin_stock) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if ((int) $document->shop_id !== (int) $user->shop_id) {
                abort(403, 'Unauthorized action.');
            }
        }

        if (!$document->sale) {
            abort(404, 'Associated sale record not found.');
        }

        $sale = $document->sale;
        $shop = $document->shop ?? $sale->shop;
        $company = $this->buildCompanyMeta($shop);

        return view('documents.print', compact('document', 'sale', 'company'));
    }

    /**
     * Update customizable metadata on an existing document.
     */
    public function update(Request $request, Document $document)
    {
        $this->ensureDocumentsTable();

        $user = Auth::user();
        if (!$user->isOwner() && (int) $document->shop_id !== (int) $user->shop_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'customer_name'    => 'nullable|string|max:150',
            'customer_id'      => 'nullable|string|max:100',
            'customer_po_box'  => 'nullable|string|max:150',
            'deliver_to'       => 'nullable|string|max:255',
            'issue_date'       => 'required|date',
            'delivery_date'    => 'nullable|date',
            'delivery_time'    => 'nullable|string|max:20',
            'validity_date'    => 'nullable|date',
            'terms_of_payment' => 'nullable|string|max:150',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $document->update([
            'customer_name'    => $validated['customer_name'] ?: 'Walk-in Customer',
            'customer_id'      => $validated['customer_id'] ?? null,
            'customer_po_box'  => $validated['customer_po_box'] ?? null,
            'deliver_to'       => $validated['deliver_to'] ?? null,
            'issue_date'       => $validated['issue_date'],
            'delivery_date'    => $validated['delivery_date'] ?? null,
            'delivery_time'    => $validated['delivery_time'] ?? null,
            'validity_date'    => $validated['validity_date'] ?? null,
            'terms_of_payment' => $validated['terms_of_payment'] ?? null,
            'notes'            => $validated['notes'] ?? null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$document->type_label} {$document->document_number} updated successfully.",
            ]);
        }

        return back()->with('success', "{$document->type_label} {$document->document_number} updated successfully.");
    }

    /**
     * Delete a document record (does NOT delete the underlying Sale).
     */
    public function destroy(Document $document)
    {
        $this->ensureDocumentsTable();

        $user = Auth::user();
        if (!$user->isOwner() && (!$user->isShopAdmin() || (int) $document->shop_id !== (int) $user->shop_id)) {
            abort(403, 'Unauthorized action.');
        }

        $num = $document->document_number;
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', "Document {$num} deleted successfully.");
    }
}
