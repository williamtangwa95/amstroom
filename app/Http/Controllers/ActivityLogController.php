<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = $this->getFilteredQuery($request);

        // Retrieve last 1000 matching logs
        $logs = $query->orderBy('created_at', 'desc')->take(1000)->get();

        // Populate users select options
        if ($user->isShopAdmin()) {
            $users = User::where('shop_id', $user->shop_id)->orderBy('name')->get();
        } elseif ($user->isSeller()) {
            $users = collect(); // Seller cannot filter by other users
        } else {
            $users = User::orderBy('name')->get();
        }

        return view('activity-logs.index', compact('logs', 'users'));
    }

    public function exportExcel(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        $logs = $query->orderBy('created_at', 'desc')->take(1000)->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['Time', 'User', 'Role', 'Action', 'Activity Details', 'IP Address', 'User Agent'];
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($logs as $log) {
            $userName = $log->user ? $log->user->name : 'System';
            $userRole = $log->user ? ucfirst($log->user->role) : 'N/A';
            
            $detailsText = $log->description;
            if ($log->changes && is_array($log->changes)) {
                $detailsText .= "\nChanges:\n";
                foreach ($log->changes as $field => $val) {
                    $detailsText .= "- {$field}: " . json_encode($val['old']) . " -> " . json_encode($val['new']) . "\n";
                }
            }

            $sheet->setCellValue('A' . $row, $log->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('B' . $row, $userName);
            $sheet->setCellValue('C' . $row, $userRole);
            $sheet->setCellValue('D' . $row, $log->action);
            $sheet->setCellValue('E' . $row, $detailsText);
            $sheet->setCellValue('F' . $row, $log->ip_address);
            $sheet->setCellValue('G' . $row, $log->user_agent);

            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
            $row++;
        }

        foreach (range(1, 7) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="user_activity_logs_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    public function exportPdf(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        $logs = $query->orderBy('created_at', 'desc')->take(1000)->get();

        echo '<html><head><title>User Activity Logs</title><style>table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:8px;text-align:left;}th{background:#f4f4f4;}body{font-family:Arial,sans-serif;}</style></head><body onload="window.print()">';
        echo '<h2>User Activity Logs</h2>';
        echo '<table><thead><tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Activity Details</th><th>IP</th></tr></thead><tbody>';
        foreach ($logs as $log) {
            $userName = $log->user ? $log->user->name : 'System';
            $userRole = $log->user ? ucfirst($log->user->role) : 'N/A';
            $detailsText = $log->description;
            if ($log->changes && is_array($log->changes)) {
                $detailsText .= " | Changes: ";
                foreach ($log->changes as $field => $val) {
                    $detailsText .= "[{$field}: " . json_encode($val['old']) . " -> " . json_encode($val['new']) . "] ";
                }
            }
            echo '<tr>';
            echo '<td>' . $log->created_at->format('Y-m-d H:i:s') . '</td>';
            echo '<td>' . htmlspecialchars($userName) . '</td>';
            echo '<td>' . htmlspecialchars($userRole) . '</td>';
            echo '<td>' . htmlspecialchars($log->action) . '</td>';
            echo '<td>' . htmlspecialchars($detailsText) . '</td>';
            echo '<td>' . htmlspecialchars($log->ip_address) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></body></html>';
        exit;
    }

    protected function getFilteredQuery(Request $request)
    {
        $user = auth()->user();
        $query = ActivityLog::with('user');

        // Role-based restrictions
        if ($user->isShopAdmin()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('shop_id', $user->shop_id);
            });
            $allowedUserIds = User::where('shop_id', $user->shop_id)->pluck('id')->toArray();
        } elseif ($user->isSeller()) {
            $query->where('user_id', $user->id);
            $allowedUserIds = [$user->id];
        } else {
            $allowedUserIds = null; // Owner has all
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $targetUserId = $request->user_id;
            if ($allowedUserIds !== null) {
                if (in_array($targetUserId, $allowedUserIds)) {
                    $query->where('user_id', $targetUserId);
                } else {
                    $query->whereRaw('1=0');
                }
            } else {
                $query->where('user_id', $targetUserId);
            }
        }

        // Filter by timeframe
        if ($request->filled('timeframe')) {
            switch ($request->timeframe) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', Carbon::yesterday());
                    break;
                case 'last_7_days':
                    $query->where('created_at', '>=', Carbon::now()->subDays(7));
                    break;
                case 'last_30_days':
                    $query->where('created_at', '>=', Carbon::now()->subDays(30));
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', Carbon::now()->subMonth()->month)
                          ->whereYear('created_at', Carbon::now()->subMonth()->year);
                    break;
            }
        }

        return $query;
    }
}
