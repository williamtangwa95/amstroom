<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sale_items')
            ->whereNotNull('parent_id')
            ->update([
                'owner_cost_price' => 0.00,
                'shop_cost_price'  => 0.00,
            ]);
    }

    public function down(): void
    {
        // Component cost data reset cannot be automatically reversed to old values.
    }
};
