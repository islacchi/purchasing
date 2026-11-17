<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->shiftTimestamps(8);
    }

    public function down(): void
    {
        $this->shiftTimestamps(-8);
    }

    private function shiftTimestamps(int $hours): void
    {
        $driver = DB::connection()->getDriverName();
        $sign = $hours < 0 ? '-' : '+';
        $magnitude = abs($hours);

        foreach (Schema::getTables() as $table) {
            $tableName = $table['name'];

            foreach (Schema::getColumns($tableName) as $column) {
                $type = strtolower((string) ($column['type_name'] ?? $column['type'] ?? ''));

                if (! in_array($type, ['timestamp', 'datetime'], true)) {
                    continue;
                }

                $columnName = $column['name'];

                if ($driver === 'sqlite') {
                    DB::statement(
                        "UPDATE `{$tableName}` SET `{$columnName}` = datetime(`{$columnName}`, '{$sign}{$magnitude} hours') WHERE `{$columnName}` IS NOT NULL"
                    );
                } else {
                    DB::statement(
                        "UPDATE `{$tableName}` SET `{$columnName}` = DATE_ADD(`{$columnName}`, INTERVAL {$sign}{$magnitude} HOUR) WHERE `{$columnName}` IS NOT NULL"
                    );
                }
            }
        }
    }
};
