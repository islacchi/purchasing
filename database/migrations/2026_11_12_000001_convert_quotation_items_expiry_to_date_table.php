<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verify every non-null expiry looks like an ISO date before
        // converting the column. Done in PHP so it works on any driver.
        $expiryValues = DB::table('quotation_items')
            ->whereNotNull('expiry')
            ->pluck('expiry');

        foreach ($expiryValues as $expiry) {
            if (! preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', (string) $expiry)) {
                $offending = DB::table('quotation_items')
                    ->where('expiry', '=', $expiry)
                    ->pluck('id')
                    ->implode(', ');

                throw new RuntimeException(
                    "Cannot convert quotation_items.expiry to date: row(s) {$offending} hold \"{$expiry}\", which must be fixed first."
                );
            }
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite (test driver) has no separate DATE type enforcement; the
            // column stays VARCHAR and Eloquent casts to/from CarbonImmutable
            // with the Y-m-d format, so behaviour matches MariaDB/MySQL.
            return;
        }

        DB::statement('ALTER TABLE quotation_items MODIFY expiry DATE NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // See up() — no physical type change needed on the test driver.
            return;
        }

        DB::statement('ALTER TABLE quotation_items MODIFY expiry VARCHAR(255) NULL');
    }
};
