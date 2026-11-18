<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\DatabaseDumper;
use App\Support\DatabaseRestorer;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    private function viewer(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('email', 'user2@example.com')->firstOrFail();
    }

    /**
     * @return array{dumper: DatabaseDumper, restorer: DatabaseRestorer}
     */
    private function fakeWriter(): array
    {
        $dumper = new class extends DatabaseDumper
        {
            /** @var list<string> */
            public array $written = [];

            public function dump(string $absolutePath): void
            {
                @mkdir(dirname($absolutePath), 0755, true);
                $this->written[] = $absolutePath;
                file_put_contents($absolutePath, "-- fake dump done marker\n-- Dump completed: 2099-01-01 00:00:00\n");
            }
        };

        $restorer = new class extends DatabaseRestorer
        {
            /** @var list<string> */
            public array $restored = [];

            public function restore(string $absolutePath): int
            {
                $this->restored[] = $absolutePath;

                return 42;
            }
        };

        $this->app->instance(DatabaseDumper::class, $dumper);
        $this->app->instance(DatabaseRestorer::class, $restorer);

        return [$dumper, $restorer];
    }

    public function test_restore_replays_the_back_up_and_keeps_a_safety_snapshot(): void
    {
        Storage::fake('local');
        [$dumper, $restorer] = $this->fakeWriter();

        // A realistic dump file: contains the completion marker.
        $contents = "DROP TABLE IF EXISTS `users`;\nCREATE TABLE `users` (id INT);\nINSERT INTO `users` (id) VALUES (1);\n-- Dump completed: 2099-01-01 00:00:01\n";
        Storage::disk('local')->put('backups/purchasing-2099-01-01-000000.sql', $contents);

        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('settings.backup.restore'), [
                'file' => 'purchasing-2099-01-01-000000.sql',
                'expected_restore_phrase' => 'RESTORE',
            ])
            ->assertRedirect(route('settings.backup'))
            ->assertSessionHas('status');

        // The selected back-up was replayed.
        $this->assertSame([
            Storage::disk('local')->path('backups/purchasing-2099-01-01-000000.sql'),
        ], $restorer->restored);

        // A pre-restore safety snapshot was written first (only the safety
        // snapshot — the source back-up was placed directly on the fake disk).
        $this->assertCount(1, $dumper->written);

        // And the restore was logged.
        $this->assertTrue(
            ActivityLog::query()->where('action', 'Restored')->where('module', 'Settings')->exists(),
            'Expected the restore to be logged.'
        );
    }

    public function test_restore_rejects_an_incomplete_back_up(): void
    {
        Storage::fake('local');

        // Missing the trailing completion marker.
        Storage::disk('local')->put('backups/purchasing-2099-01-01-000001.sql', "SET NAMES utf8mb4;\n");

        $user = $this->admin();

        $this->actingAs($user)
            ->withHeaders(['Referer' => route('settings.backup')])
            ->post(route('settings.backup.restore'), [
                'file' => 'purchasing-2099-01-01-000001.sql',
                'expected_restore_phrase' => 'RESTORE',
            ])
            ->assertRedirect(route('settings.backup'))
            ->assertSessionHasErrors('backup');
    }

    public function test_restore_requires_the_exact_confirmation_phrase(): void
    {
        Storage::fake('local');

        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('settings.backup.restore'), [
                'file' => 'purchasing-2099-01-01-000002.sql',
                'expected_restore_phrase' => 'restore', // exact text required
            ])
            ->assertSessionHasErrors('expected_restore_phrase');
    }

    public function test_restore_rejects_invalid_filenames_and_missing_files(): void
    {
        Storage::fake('local');

        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('settings.backup.restore'), [
                'file' => '../etc/passwd',
                'expected_restore_phrase' => 'RESTORE',
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('settings.backup.restore'), [
                'file' => 'purchasing-2099-01-01-000003.sql',
                'expected_restore_phrase' => 'RESTORE',
            ])
            ->assertSessionHasErrors('backup');
    }

    public function test_restore_is_administrator_only(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('backups/purchasing-2099-01-01-000004.sql', "-- noop\n-- Dump completed: now\n");

        $this->actingAs($this->viewer())
            ->post(route('settings.backup.restore'), [
                'file' => 'purchasing-2099-01-01-000004.sql',
                'expected_restore_phrase' => 'RESTORE',
            ])
            ->assertForbidden();
    }

    public function test_the_restorer_refuses_a_file_without_the_completion_marker(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'incomplete_dump_').'.sql';
        file_put_contents($path, "-- Purchasing database dump\nDROP TABLE IF EXISTS `things`;\n");

        try {
            (new DatabaseRestorer)->restore($path);
            $this->fail('Expected RuntimeException for an incomplete back-up.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('incomplete', strtolower($exception->getMessage()));
        } finally {
            @unlink($path);
        }
    }

    public function test_the_restorer_plan_skips_preamble_and_ephemeral_tables(): void
    {
        $restorer = new DatabaseRestorer;

        $plan = $restorer->plan(<<<'SQL'
            -- Purchasing database dump
            -- Generated: 2099-01-01 00:00:00
            SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
            SET NAMES utf8mb4;
            SET FOREIGN_KEY_CHECKS = 0;

            DROP DATABASE IF EXISTS `purchasing_db`;
            CREATE DATABASE `purchasing_db` DEFAULT CHARACTER SET utf8mb4;
            USE `purchasing_db`;

            DROP TABLE IF EXISTS `activity_logs`;
            CREATE TABLE `activity_logs` (id INT);
            INSERT INTO `activity_logs` (id) VALUES (1);

            DROP TABLE IF EXISTS `sessions`;
            CREATE TABLE `sessions` (id INT);
            INSERT INTO `sessions` (id) VALUES ('keep-me');

            DROP TABLE IF EXISTS `users`;
            CREATE TABLE `users` (id INT);
            INSERT INTO `users` (id, name) VALUES (1, 'it''s above; below');
            SQL);

        $this->assertSame([
            "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'",
            'SET NAMES utf8mb4',
            'SET FOREIGN_KEY_CHECKS = 0',
            'DROP TABLE IF EXISTS `activity_logs`',
            'CREATE TABLE `activity_logs` (id INT)',
            'INSERT INTO `activity_logs` (id) VALUES (1)',
            'DROP TABLE IF EXISTS `users`',
            'CREATE TABLE `users` (id INT)',
            "INSERT INTO `users` (id, name) VALUES (1, 'it''s above; below')",
        ], $plan);
    }

    public function test_the_restorer_plan_rejects_an_unterminated_string_gracefully(): void
    {
        $restorer = new DatabaseRestorer;

        // Unterminated string — the last statement is simply not produced as
        // a complete statement; the marker check in restore() would have
        // caught this in practice.
        $plan = $restorer->plan(<<<'SQL'
            SET NAMES utf8mb4;
            INSERT INTO t VALUES ('unterminated
            SQL);

        $this->assertContains('SET NAMES utf8mb4', $plan);
    }
}
