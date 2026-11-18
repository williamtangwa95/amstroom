<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('symbol', 20);
                $table->string('description')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });

            // Seed default units
            $now = now();
            DB::table('units')->insert([
                ['name' => 'Piece',     'symbol' => 'pc',   'description' => 'Single item/unit',        'is_default' => true,  'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Meter',     'symbol' => 'm',    'description' => 'Length in meters (cables, wires)', 'is_default' => false, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Box',       'symbol' => 'box',  'description' => 'Packaged box of items',   'is_default' => false, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Roll',      'symbol' => 'roll', 'description' => 'Roll of cable/tape',       'is_default' => false, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Kilogram',  'symbol' => 'kg',   'description' => 'Weight in kilograms',      'is_default' => false, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Set',       'symbol' => 'set',  'description' => 'Set of items',            'is_default' => false, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Pair',      'symbol' => 'pair', 'description' => 'Pair of items',           'is_default' => false, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Pack',      'symbol' => 'pack', 'description' => 'Package / packet',         'is_default' => false, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Liter',     'symbol' => 'L',    'description' => 'Volume in liters',         'is_default' => false, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
