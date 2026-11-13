<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Dumps the configured MySQL database (schema and data) into a single .sql file.
 * Runs entirely through the application's own Laravel connection — no external
 * mysqldump binary or subprocess is consulted, which keeps it working from any
 * SAPI (CLI, php web worker) where a raw socket from a spawned process fails.
 */
class DatabaseDumper
{
    private const EPHEMERAL_TABLES = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'sessions',
    ];

    private const CHUNK_SIZE = 200;

    /**
     * Write a full database dump (schema and contents) to $absolutePath.
     *
     * @throws RuntimeException when anything fails, leaving a partial
     *                          file removed and the original dump untouched.
     */
    public function dump(string $absolutePath): void
    {
        $connection = (string) config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Database back-up only supports MySQL connections.');
        }

        $database = (string) $config['database'];
        $temporaryPath = $absolutePath.'.tmp';

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        $handle = fopen($temporaryPath, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Unable to write the back-up file to '.$absolutePath);
        }

        try {
            $this->writeHead($handle, $database);

            $tableNames = $this->tableNames();

            foreach ($tableNames as $table) {
                $this->writeTableSchema($handle, $table);

                if (! in_array($table, self::EPHEMERAL_TABLES, true)) {
                    $this->writeTableData($handle, $table);
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fwrite($handle, '-- Dump completed: '.now()->toDateTimeString()."\n");
            fclose($handle);

            rename($temporaryPath, $absolutePath);
        } catch (Throwable $exception) {
            fclose($handle);
            @unlink($temporaryPath);

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('Database dump failed: '.$exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Every base table, in a stable order.
     *
     * @return array<int, string>
     */
    private function tableNames(): array
    {
        $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

        $names = array_map(
            fn (object $row): string => array_values((array) $row)[0],
            $tables,
        );

        sort($names);

        return $names;
    }

    private function writeHead($handle, string $database): void
    {
        fwrite($handle, "-- Purchasing database dump\n");
        fwrite($handle, '-- Generated: '.now()->toDateTimeString()."\n");
        fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        fwrite($handle, "SET NAMES utf8mb4;\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");
        fwrite($handle, "DROP DATABASE IF EXISTS `{$database}`;\n");
        fwrite($handle, "CREATE DATABASE `{$database}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
        fwrite($handle, "USE `{$database}`;\n\n");
    }

    /**
     * @param  resource  $handle
     */
    private function writeTableSchema($handle, string $table): void
    {
        $created = DB::selectOne("SHOW CREATE TABLE `{$table}`");
        $createTable = $created->{'Create Table'};

        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($handle, $createTable.";\n\n");
    }

    /**
     * @param  resource  $handle
     */
    private function writeTableData($handle, string $table): void
    {
        $columns = array_map(
            fn (object $row): string => $row->Field,
            DB::select("SHOW COLUMNS FROM `{$table}`"),
        );

        $columnList = implode(',', array_map(fn (string $column): string => '`'.$column.'`', $columns));
        $pdo = DB::connection()->getPdo();

        DB::table($table)
            ->orderByRaw('1')
            ->chunk(self::CHUNK_SIZE, function ($rows) use ($handle, $table, $columnList, $pdo): void {
                if ($rows->isEmpty()) {
                    return;
                }

                $values = [];

                foreach ($rows as $row) {
                    $rowValues = [];

                    foreach ((array) $row as $value) {
                        $rowValues[] = $this->quote($value, $pdo);
                    }

                    $values[] = '('.implode(',', $rowValues).')';
                }

                fwrite(
                    $handle,
                    "INSERT INTO `{$table}` ({$columnList}) VALUES\n"
                    .implode(",\n", $values).";\n\n",
                );
            });
    }

    /**
     * Quote one value for a MariaDB/MySQL INSERT, NULL-safe.
     */
    private function quote(mixed $value, PDO $pdo): string
    {
        return match (true) {
            is_null($value) => 'NULL',
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value) => (string) $value,
            // Text that contains line breaks or quotes must go through
            // PDO quoting; booleans above keep their 0/1 form.
            default => $pdo->quote((string) $value) ?? "''",
        };
    }
}
