<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Shop;
use App\Models\Setting;
use App\Http\Controllers\SettingController;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('amstroom:send-summaries {--force : Send reports immediately bypassing time checks}', function () {
    $force = $this->option('force');
    $tz = config('app.timezone', 'Africa/Dar_es_Salaam');
    $currentTime = now()->timezone($tz)->format('H:i');

    $this->info("Checking system and shop summary reports schedule... (Time: {$currentTime} {$tz})");

    // 1. Send to Owner(s)
    $owners = User::where('role', 'owner')->get();
    foreach ($owners as $owner) {
        $sendTime = Setting::get('summary_report_time', '22:00');
        if (!$force && $currentTime !== $sendTime) {
            continue;
        }

        $emailsStr = Setting::get('summary_report_emails', $owner->email);
        $emailsArray = array_map('trim', explode(',', $emailsStr));
        $emailsArray = array_filter($emailsArray, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        
        if (!empty($emailsArray)) {
            $controller = new SettingController();
            $reportData = $controller->compileReportData($owner, 'Global System');
            
            try {
                \Illuminate\Support\Facades\Mail::to($emailsArray)->send(new \App\Mail\SummaryReportMail($reportData));
                $this->info("Global system report sent to: " . implode(', ', $emailsArray));
            } catch (\Exception $e) {
                $this->error("Failed to send global report: " . $e->getMessage());
            }
        }
    }

    // 2. Send to Shop Admins
    $shops = Shop::active()->get();
    foreach ($shops as $shop) {
        $sendTime = $shop->summary_time ?: '22:00';
        if (!$force && $currentTime !== $sendTime) {
            continue;
        }

        $emailsStr = $shop->summary_emails;
        if (!$emailsStr) {
            // Find a shop admin for this shop
            $admin = User::where('shop_id', $shop->id)->where('role', 'shop_admin')->first();
            if ($admin) {
                $emailsStr = $admin->email;
            }
        }
        
        if ($emailsStr) {
            $emailsArray = array_map('trim', explode(',', $emailsStr));
            $emailsArray = array_filter($emailsArray, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
            
            if (!empty($emailsArray)) {
                $admin = User::where('shop_id', $shop->id)->where('role', 'shop_admin')->first()
                         ?? User::where('shop_id', $shop->id)->first();
                         
                if ($admin) {
                    $controller = new SettingController();
                    $reportData = $controller->compileReportData($admin, $shop->shop_name);
                    
                    try {
                        \Illuminate\Support\Facades\Mail::to($emailsArray)->send(new \App\Mail\SummaryReportMail($reportData));
                        $this->info("Shop '{$shop->shop_name}' report sent to: " . implode(', ', $emailsArray));
                    } catch (\Exception $e) {
                        $this->error("Failed to send report for shop '{$shop->shop_name}': " . $e->getMessage());
                    }
                }
            }
        }
    }
})->purpose('Send the summary report of sales, expenses, and stock to gmail recipients configured in settings');
