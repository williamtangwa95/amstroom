<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        Setting::set('store_pricing_mode', 'INDEPENDENT');
    }

    public function down(): void
    {
        Setting::set('store_pricing_mode', 'DEPENDENT');
    }
};
