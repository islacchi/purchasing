<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Replays a .sql back-up (written by App\Support\DatabaseDumper) into the
 * configured MySQL database through the application's own connection.
 *
 * Execution order is computed in {@see self::plan()} — a pure function so it
 * can be unit-tested without a MySQL server.
 */
class DatabaseRestorer
{
    /**
     * Tables whose data is deliberately not restored — the running session
     * and any queued jobs must survive the restore.
     *
     * @var list<string>
     */
    private const EPHEMERAL_TABLES = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'sessions',
    ];

    /**
     * Restore $absolutePath into the configured database.
     *
     * @throws RuntimeException when the back-up file is missing, unreadable,
     *                          incomplete, or an error occurs mid-restore
     *                          (the safety back-up taken beforehand remains).
     */
    public function restore(string $absolutePath): int
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException('The back-up file could not be found.');
        }

        $sql = file_get_contents($absolutePath);

        if ($sql === false) {
            throw new RuntimeException('The back-up file could not be read.');
        }

        // The writer only appends the completion marker after a successful
        // dump, so its absence proves the file was truncated.
        if (! str_contains($sql, '-- Dump completed:')) {
            throw new RuntimeException('The back-up file appears to be incomplete (the writer did not append its completion marker).');
        }

        $executed = 0;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->plan($sql) as $statement) {
                DB::statement($statement);
                $executed++;
            }
        } catch (Throwable $exception) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (Throwable) {
                // Ignored — the connection may already be unusable.
            }

            throw new RuntimeException(
                "The back-up could not be replayed: {$exception->getMessage()}",
                0,
                $exception,
            );
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return $executed;
    }

    /**
     * Compute the statements to execute, in order. Pure and side-effect
     * free, so it is unit-testable without a database.
     *
     * @return list<string>
     */
    public function plan(string $sql): array
    {
        return array_values(array_filter(
            $this->statements($sql),
            fn (string $statement) => ! $this->shouldSkip($statement),
        ));
    }

    /**
     * Split the dump into raw statements, honouring quoted strings
     * (single-quoted values may legally contain semicolons), backtick-quoted
     * identifiers, and line comments.
     *
     * @return list<string>
     */
    private function statements(string $sql): array
    {
        // Whole-line comments are not executable anyway; removing them up
        // front keeps the matcher simple and exact.
        $sql = preg_replace('/^\s*--[^\n]*\n/m', '', $sql) ?? $sql;

        $statements = [];
        $buffer = '';
        $inString = false;
        $inBacktick = false;
        $escaping = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $ch = $sql[$i];

            if ($escaping) {
                $buffer .= $ch;
                $escaping = false;

                continue;
            }

            if ($inString) {
                $buffer .= $ch;
                if ($ch === '\\') {
                    $escaping = true;

                    continue;
                }
                if ($ch === "'") {
                    $inString = false;
                }

                continue;
            }

            if ($inBacktick) {
                $buffer .= $ch;
                if ($ch === '`') {
                    $inBacktick = false;
                }

                continue;
            }

            if ($ch === "'") {
                $buffer .= $ch;
                $inString = true;

                continue;
            }

            if ($ch === '`') {
                $buffer .= $ch;
                $inBacktick = true;

                continue;
            }

            if ($ch === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';

                continue;
            }

            $buffer .= $ch;
        }

        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }

    /**
     * Statements we deliberately do not replay.
     */
    private function shouldSkip(string $statement): bool
    {
        $statement = ltrim($statement);

        if ($statement === '') {
            return true;
        }

        // The current database is dropped and recreated by the writer's
        // preamble; we must not honour that from within a live connection.
        if (preg_match('/^(DROP\s+DATABASE|CREATE\s+DATABASE|USE)\b/i', $statement)) {
            return true;
        }

        if (preg_match('/^(DROP\s+TABLE(\s+IF\s+EXISTS)?|CREATE\s+TABLE|INSERT\s+INTO)\s+`?([A-Za-z0-9_]+)`?/i', $statement, $matches)) {
            return in_array($matches[3], self::EPHEMERAL_TABLES, true);
        }

        return false;
    }
}
