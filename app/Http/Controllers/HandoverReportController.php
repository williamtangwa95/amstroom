<?php

namespace App\Http\Controllers;

use App\Models\HandoverReport;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Shop;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HandoverReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = HandoverReport::with(['shop', 'shopAdmin', 'creator']);

        // Filter by role
        if ($user->isOwner()) {
            // Owner sees all
            if ($request->filled('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('start_date')) {
                $query->where('start_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->where('end_date', '<=', $request->end_date);
            }
        } elseif ($user->isShopAdmin()) {
            // Admin only sees their shop
            $query->where('shop_id', $user->shop_id);
        } else {
            // Seller cannot access handovers
            abort(403, 'Unauthorized action.');
        }

        $handovers = $query->latest()->get();
        $shops = $user->isOwner() ? Shop::all() : collect();

        // Dashboard Stats
        $stats = [
            'pending' => HandoverReport::where('status', 'submitted')->count(),
            'under_review' => HandoverReport::where('status', 'under_review')->count(),
            'completed' => HandoverReport::where('status', 'completed')->count(),
            'total_expected' => HandoverReport::where('status', 'completed')->sum('expected_amount'),
            'total_received' => HandoverReport::where('status', 'completed')->sum('amount_received'),
            'total_shortage' => HandoverReport::where('status', 'completed')->where('difference_status', 'shortage')->sum('difference'),
            'total_excess' => HandoverReport::where('status', 'completed')->where('difference_status', 'excess')->sum('difference'),
        ];

        return view('handovers.index', compact('handovers', 'shops', 'stats'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $shopId = $user->isOwner() ? $request->shop_id : $user->shop_id;

        if (!$shopId) {
            if ($user->isOwner()) {
                // If owner didn't select shop, show selection
                $shops = Shop::all();
                return view('handovers.create', compact('shops'));
            }
            abort(400, 'Shop ID is required.');
        }

        $shop = Shop::findOrFail($shopId);

        // Date Logic: Default Start Date = Previous End Date + 1 Day
        $latestHandover = HandoverReport::where('shop_id', $shopId)
            ->whereIn('status', ['submitted', 'approved', 'completed'])
            ->orderBy('end_date', 'desc')
            ->first();

        $defaultStartDate = null;
        if ($latestHandover) {
            $defaultStartDate = $latestHandover->end_date->addDay()->toDateString();
        } else {
            // If no previous handover, default to date of oldest unsettled sale or start of month
            $oldestSale = Sale::where('shop_id', $shopId)
                ->completed()
                ->whereNull('handover_report_id')
                ->orderBy('sale_date', 'asc')
                ->first();
            $defaultStartDate = $oldestSale ? $oldestSale->sale_date->toDateString() : now()->startOfMonth()->toDateString();
        }

        $startDate = $request->input('start_date', $defaultStartDate);
        $endDate = $request->input('end_date', now()->toDateString());

        // Prevent overlapping dates if they query something already covered
        $overlapExists = HandoverReport::where('shop_id', $shopId)
            ->whereIn('status', ['submitted', 'approved', 'completed'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                  ->where('end_date', '>=', $startDate);
            })
            ->exists();

        // Query sales and expenses
        $sales = Sale::with('items')
            ->where('shop_id', $shopId)
            ->completed()
            ->whereDate('sale_date', '>=', $startDate)
            ->whereDate('sale_date', '<=', $endDate)
            ->whereNull('handover_report_id')
            ->get();

        $expenses = Expense::with(['category', 'recorder'])
            ->whereHas('recorder', function ($q) use ($shopId) {
                $q->where('shop_id', $shopId);
            })
            ->where('status', 'approved')
            ->whereDate('activity_date', '>=', $startDate)
            ->whereDate('activity_date', '<=', $endDate)
            ->whereNull('handover_report_id')
            ->get();

        // Calculations
        $totalOwnerSales = 0.0;
        $totalAdminSales = 0.0;
        $adminStockCost = 0.0;
        $adminCostOfGoods = 0.0;
        $adminViewSales = 0.0;

        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT';

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->is_admin_stock) {
                    continue; // Exclude admin stock entirely
                }
                
                // Calculate item value as the owner sees it
                if ($isIndependent && $sale->shop_id !== null) {
                    $itemRevenue = (float) ($item->owner_realized_sp ?? $item->selling_price) * $item->quantity;
                } else {
                    $itemRevenue = (float) ($item->shop_realized_sp ?? $item->selling_price) * $item->quantity;
                }

                $totalOwnerSales += $itemRevenue;
                $adminCostOfGoods += (float) ($item->shop_cost_price ?? $item->owner_realized_sp ?? $item->selling_price ?? 0) * $item->quantity;
                $adminViewSales += (float) ($item->shop_realized_sp ?? $item->selling_price) * $item->quantity;
            }
        }

        $totalExpenses = (float) $expenses->sum('amount');
        $expectedAmount = $totalOwnerSales - $totalExpenses;
        $netProfit = $adminViewSales - $adminCostOfGoods;

        $shops = $user->isOwner() ? Shop::all() : collect();

        return view('handovers.create', compact(
            'shop', 'shops', 'startDate', 'endDate', 'overlapExists',
            'sales', 'expenses', 'totalOwnerSales', 'totalAdminSales',
            'adminStockCost', 'totalExpenses', 'expectedAmount', 'netProfit'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $shopId = $user->isOwner() ? $request->shop_id : $user->shop_id;
        $shop = Shop::findOrFail($shopId);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'actual_amount' => 'required|numeric|min:0',
            'difference_reason' => 'required_if:needs_reason,1',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Overlap validation
        $overlap = HandoverReport::where('shop_id', $shopId)
            ->whereIn('status', ['submitted', 'approved', 'completed'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                  ->where('end_date', '>=', $startDate);
            })
            ->exists();

        if ($overlap) {
            return back()->with('error', 'A handover report already exists for this shop during the selected date range.')->withInput();
        }

        // Fetch sales and expenses to lock and calculate
        $sales = Sale::where('shop_id', $shopId)
            ->completed()
            ->whereDate('sale_date', '>=', $startDate)
            ->whereDate('sale_date', '<=', $endDate)
            ->whereNull('handover_report_id')
            ->get();

        $expenses = Expense::whereHas('recorder', function ($q) use ($shopId) {
                $q->where('shop_id', $shopId);
            })
            ->where('status', 'approved')
            ->whereDate('activity_date', '>=', $startDate)
            ->whereDate('activity_date', '<=', $endDate)
            ->whereNull('handover_report_id')
            ->get();

        $totalOwnerSales = 0.0;
        $totalAdminSales = 0.0;
        $adminStockCost = 0.0;
        $adminCostOfGoods = 0.0;
        $adminViewSales = 0.0;

        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT';

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->is_admin_stock) {
                    continue; // Exclude admin stock entirely
                }
                
                // Calculate item value as the owner sees it
                if ($isIndependent && $sale->shop_id !== null) {
                    $itemRevenue = (float) ($item->owner_realized_sp ?? $item->selling_price) * $item->quantity;
                } else {
                    $itemRevenue = (float) ($item->shop_realized_sp ?? $item->selling_price) * $item->quantity;
                }

                $totalOwnerSales += $itemRevenue;
                $adminCostOfGoods += (float) ($item->shop_cost_price ?? $item->owner_realized_sp ?? $item->selling_price ?? 0) * $item->quantity;
                $adminViewSales += (float) ($item->shop_realized_sp ?? $item->selling_price) * $item->quantity;
            }
        }

        $totalExpenses = (float) $expenses->sum('amount');
        $expectedAmount = $totalOwnerSales - $totalExpenses;
        $netProfit = $adminViewSales - $adminCostOfGoods;

        $actualAmount = (float) $request->actual_amount;
        $difference = $actualAmount - $expectedAmount;

        $differenceStatus = 'exact';
        if ($difference < -0.01) {
            $differenceStatus = 'shortage';
        } elseif ($difference > 0.01) {
            $differenceStatus = 'excess';
        }

        // Handle attachment upload
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('handover_attachments', 'public');
        }

        // Unique Handover ID formatting (guaranteed unique by checking DB existence in a sequence loop, including soft deleted)
        $nextSeq = 1;
        do {
            $handoverNo = 'HO-' . date('Ymd') . '-' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
            $exists = HandoverReport::withTrashed()->where('handover_no', $handoverNo)->exists();
            if ($exists) {
                $nextSeq++;
            }
        } while ($exists);

        DB::beginTransaction();
        try {
            $status = $request->input('submit_action') === 'submit' ? 'submitted' : 'draft';

            $handover = HandoverReport::create([
                'handover_no' => $handoverNo,
                'shop_id' => $shopId,
                'shop_admin_id' => $user->isShopAdmin() ? $user->id : ($shop->employees()->where('role', 'shop_admin')->first()?->id ?? $user->id),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_owner_sales' => $totalOwnerSales,
                'total_admin_sales' => $totalAdminSales,
                'admin_stock_cost' => $adminStockCost,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'expected_amount' => $expectedAmount,
                'actual_amount' => $actualAmount,
                'difference' => $difference,
                'difference_status' => $differenceStatus,
                'difference_reason' => $request->difference_reason,
                'notes' => $request->notes,
                'attachment_path' => $attachmentPath,
                'status' => $status,
                'created_by' => $user->id,
                'submitted_at' => $status === 'submitted' ? now() : null,
            ]);

            // Link transactions
            foreach ($sales as $sale) {
                $sale->update(['handover_report_id' => $handover->id]);
            }
            foreach ($expenses as $expense) {
                $expense->update(['handover_report_id' => $handover->id]);
            }

            DB::commit();

            // Notify Owners if status is submitted
            if ($status === 'submitted') {
                $owners = \App\Models\User::where('role', 'owner')->get();
                foreach ($owners as $owner) {
                    \App\Models\Notification::create([
                        'user_id' => $owner->id,
                        'title' => 'New Handover Submitted',
                        'message' => "Shop Admin {$user->name} submitted a new Handover Report: {$handover->handover_no} for shop {$handover->shop->shop_name}.",
                    ]);
                }
            }

            // Log activity
            ActivityLog::log(
                strtoupper($status),
                ($status === 'submitted' ? 'Submitted' : 'Created draft') . " Handover Report: {$handover->handover_no}",
                $handover
            );

            return redirect()->route('handovers.index')->with('success', 'Handover report saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save handover report: ' . $e->getMessage())->withInput();
        }
    }

    public function show(HandoverReport $handover)
    {
        $user = auth()->user();
        if (!$user->isOwner() && $handover->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized access to this handover report.');
        }

        $sales = Sale::with('items.item')
            ->where('handover_report_id', $handover->id)
            ->get();

        $expenses = Expense::with('category')
            ->where('handover_report_id', $handover->id)
            ->get();

        return view('handovers.show', compact('handover', 'sales', 'expenses'));
    }

    public function submit(HandoverReport $handover)
    {
        $user = auth()->user();
        if ($handover->status !== 'draft' && $handover->status !== 'rejected') {
            return back()->with('error', 'Only drafts or rejected reports can be submitted.');
        }

        $handover->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        ActivityLog::log('SUBMITTED', "Submitted Handover Report: {$handover->handover_no}", $handover);

        // Notify Owners
        $owners = \App\Models\User::where('role', 'owner')->get();
        foreach ($owners as $owner) {
            \App\Models\Notification::create([
                'user_id' => $owner->id,
                'title' => 'New Handover Submitted',
                'message' => "Shop Admin {$handover->shopAdmin->name} submitted Handover Report: {$handover->handover_no} for shop {$handover->shop->shop_name}.",
            ]);
        }

        return back()->with('success', 'Handover report submitted successfully.');
    }

    public function approve(HandoverReport $handover)
    {
        $user = auth()->user();
        if (!$user->isOwner()) {
            abort(403, 'Only owners can approve handover reports.');
        }

        $handover->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        ActivityLog::log('APPROVED', "Approved Handover Report: {$handover->handover_no}", $handover);

        // Notify Shop Admin
        \App\Models\Notification::create([
            'user_id' => $handover->shop_admin_id,
            'title' => 'Handover Report Approved',
            'message' => "Your Handover Report {$handover->handover_no} has been approved by the Owner.",
        ]);

        return back()->with('success', 'Handover report approved.');
    }

    public function reject(HandoverReport $handover, Request $request)
    {
        $user = auth()->user();
        if (!$user->isOwner()) {
            abort(403, 'Only owners can reject handover reports.');
        }

        $request->validate(['remarks' => 'required|string']);

        DB::beginTransaction();
        try {
            $handover->update([
                'status' => 'rejected',
                'received_remarks' => $request->remarks,
            ]);

            // Release transactions back to pool so they can be re-calculated or modified
            Sale::where('handover_report_id', $handover->id)->update(['handover_report_id' => null]);
            Expense::where('handover_report_id', $handover->id)->update(['handover_report_id' => null]);

            DB::commit();

            ActivityLog::log('REJECTED', "Rejected Handover Report: {$handover->handover_no}. Reason: {$request->remarks}", $handover);

            // Notify Shop Admin
            \App\Models\Notification::create([
                'user_id' => $handover->shop_admin_id,
                'title' => 'Handover Report Rejected',
                'message' => "Your Handover Report {$handover->handover_no} was rejected by the Owner with remarks: \"{$request->remarks}\".",
            ]);

            return back()->with('success', 'Handover report rejected and transactions released.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject handover report: ' . $e->getMessage());
        }
    }

    public function confirmReceipt(HandoverReport $handover, Request $request)
    {
        $user = auth()->user();
        if (!$user->isOwner()) {
            abort(403, 'Only owners can confirm cash receipt.');
        }

        $request->validate([
            'amount_received' => 'required|numeric|min:0',
            'received_remarks' => 'nullable|string',
        ]);

        $handover->update([
            'status' => 'completed',
            'received_by' => $user->id,
            'received_at' => now(),
            'amount_received' => $request->amount_received,
            'received_remarks' => $request->received_remarks,
        ]);

        ActivityLog::log(
            'COMPLETED',
            "Confirmed cash received for Handover Report: {$handover->handover_no}. Amount: TZS " . number_format($request->amount_received),
            $handover
        );

        // Notify Shop Admin
        \App\Models\Notification::create([
            'user_id' => $handover->shop_admin_id,
            'title' => 'Handover Report Completed',
            'message' => "Cash receipt has been confirmed for Handover Report {$handover->handover_no}.",
        ]);

        return back()->with('success', 'Cash receipt confirmed and handover marked COMPLETED.');
    }

    public function destroy(HandoverReport $handover)
    {
        $user = auth()->user();
        if (!$user->isOwner() && $handover->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized action.');
        }

        if (in_array($handover->status, ['approved', 'completed'])) {
            return back()->with('error', 'Approved or completed handover reports cannot be deleted.');
        }

        DB::beginTransaction();
        try {
            // Release transactions
            Sale::where('handover_report_id', $handover->id)->update(['handover_report_id' => null]);
            Expense::where('handover_report_id', $handover->id)->update(['handover_report_id' => null]);

            $handover->delete();

            DB::commit();

            ActivityLog::log('DELETED', "Deleted Handover Report: {$handover->handover_no}");

            return redirect()->route('handovers.index')->with('success', 'Handover report deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete report: ' . $e->getMessage());
        }
    }

    public function exportExcel(HandoverReport $handover)
    {
        $user = auth()->user();
        if (!$user->isOwner() && $handover->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized.');
        }

        $sales = Sale::with('items.item')
            ->where('handover_report_id', $handover->id)
            ->get();

        $expenses = Expense::with('category')
            ->where('handover_report_id', $handover->id)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Handover Summary');

        // Styles
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A1', 'SALES CASH HANDOVER / SETTLEMENT REPORT');

        // Header details
        $sheet->setCellValue('A3', 'Business Name:');
        $sheet->setCellValue('B3', \App\Models\Setting::get('site_title', 'Amstroom'));
        $sheet->setCellValue('A4', 'Handover ID:');
        $sheet->setCellValue('B4', $handover->handover_no);
        $sheet->setCellValue('A5', 'Shop:');
        $sheet->setCellValue('B5', $handover->shop->shop_name);
        $sheet->setCellValue('A6', 'Shop Admin:');
        $sheet->setCellValue('B6', $handover->shopAdmin->name);
        $sheet->setCellValue('A7', 'Period:');
        $sheet->setCellValue('B7', $handover->start_date->format('Y-m-d') . ' to ' . $handover->end_date->format('Y-m-d'));
        $sheet->setCellValue('A8', 'Report Date:');
        $sheet->setCellValue('B8', date('Y-m-d H:i:s'));

        // Summary details
        $sheet->getStyle('A10:B10')->getFont()->setBold(true);
        $sheet->setCellValue('A10', 'Financial Summary');
        $sheet->setCellValue('A11', 'Total Owner Sales:');
        $sheet->setCellValue('B11', (float)$handover->total_owner_sales);
        $sheet->setCellValue('A12', 'Total Expenses:');
        $sheet->setCellValue('B12', (float)$handover->total_expenses);

        $rowNum = 13;
        if (!auth()->user()->isShopAdmin()) {
            $sheet->setCellValue('A' . $rowNum, 'Admin Net Profit:');
            $sheet->setCellValue('B' . $rowNum, (float)$handover->net_profit);
            $rowNum++;
        }

        $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getFont()->setBold(true);
        $sheet->setCellValue('A' . $rowNum, 'Expected Amount to Submit:');
        $sheet->setCellValue('B' . $rowNum, (float)$handover->expected_amount);
        $rowNum++;

        $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getFont()->setBold(true);
        $sheet->setCellValue('A' . $rowNum, 'Actual Amount Submitted:');
        $sheet->setCellValue('B' . $rowNum, (float)$handover->actual_amount);
        $rowNum++;

        $sheet->setCellValue('A' . $rowNum, 'Difference:');
        $sheet->setCellValue('B' . $rowNum, (float)$handover->difference);
        $rowNum++;

        $sheet->setCellValue('A' . $rowNum, 'Difference Status:');
        $sheet->setCellValue('B' . $rowNum, strtoupper($handover->difference_status));
        $rowNum++;

        $sheet->setCellValue('A' . $rowNum, 'Difference Reason:');
        $sheet->setCellValue('B' . $rowNum, $handover->difference_reason);
        $rowNum++;

        // Transactions details table
        $tableHeaderRow = $rowNum + 2;
        $sheet->getStyle('A' . $tableHeaderRow . ':I' . $tableHeaderRow)->getFont()->setBold(true);
        $sheet->setCellValue('A' . $tableHeaderRow, 'Date');
        $sheet->setCellValue('B' . $tableHeaderRow, 'Invoice No');
        $sheet->setCellValue('C' . $tableHeaderRow, 'Product');
        $sheet->setCellValue('D' . $tableHeaderRow, 'Ownership');
        $sheet->setCellValue('E' . $tableHeaderRow, 'Quantity');
        $sheet->setCellValue('F' . $tableHeaderRow, 'Selling Price');
        $sheet->setCellValue('G' . $tableHeaderRow, 'Purchase Cost');
        $sheet->setCellValue('H' . $tableHeaderRow, 'Total Revenue');
        $sheet->setCellValue('I' . $tableHeaderRow, 'Attributable Amount');

        $rowNum = $tableHeaderRow + 1;
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->is_admin_stock) {
                    continue; // Exclude admin stock entirely
                }
                $sheet->setCellValue('A' . $rowNum, $sale->sale_date->format('Y-m-d'));
                $sheet->setCellValue('B' . $rowNum, 'Sale #' . $sale->id);
                $sheet->setCellValue('C' . $rowNum, $item->display_name);
                $sheet->setCellValue('D' . $rowNum, 'Owner Stock');
                $sheet->setCellValue('E' . $rowNum, $item->quantity);
                
                $priceVal = (float)($item->owner_realized_sp ?? $item->selling_price);
                $sheet->setCellValue('F' . $rowNum, $priceVal);
                $sheet->setCellValue('G' . $rowNum, (float)$item->owner_cost_price);
                $sheet->setCellValue('H' . $rowNum, (float)($priceVal * $item->quantity));
                
                $attributable = $priceVal * $item->quantity;
                $sheet->setCellValue('I' . $rowNum, (float)$attributable);
                $rowNum++;
            }
        }

        // Expenses table
        $rowNum += 2;
        $sheet->getStyle('A' . $rowNum . ':D' . $rowNum)->getFont()->setBold(true);
        $sheet->setCellValue('A' . $rowNum, 'Expense Date');
        $sheet->setCellValue('B' . $rowNum, 'Category');
        $sheet->setCellValue('C' . $rowNum, 'Description');
        $sheet->setCellValue('D' . $rowNum, 'Amount');

        $expenseHeaderRow = $rowNum;
        $rowNum++;
        foreach ($expenses as $exp) {
            $sheet->setCellValue('A' . $rowNum, $exp->activity_date->format('Y-m-d'));
            $sheet->setCellValue('B' . $rowNum, $exp->category->name);
            $sheet->setCellValue('C' . $rowNum, $exp->description);
            $sheet->setCellValue('D' . $rowNum, (float)$exp->amount);
            $rowNum++;
        }

        // Format column auto sizes
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Handover_Report_' . $handover->handover_no . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    public function exportPdf(HandoverReport $handover)
    {
        $user = auth()->user();
        if (!$user->isOwner() && $handover->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized access to this handover report.');
        }

        $sales = Sale::with('items.item')
            ->where('handover_report_id', $handover->id)
            ->get();

        $expenses = Expense::with('category')
            ->where('handover_report_id', $handover->id)
            ->get();

        $print = true;

        return view('handovers.show', compact('handover', 'sales', 'expenses', 'print'));
    }
}
