<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('role_module_access')
            ->where('module', 'priceindex')
            ->update(['module' => 'item-catalog']);
    }

    public function down(): void
    {
        DB::table('role_module_access')
            ->where('module', 'item-catalog')
            ->update(['module' => 'priceindex']);
    }
};
