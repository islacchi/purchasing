<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\BackupStorage;
use App\Support\DatabaseDumper;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BackupTest extends TestCase
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

    private function fakeDumper(): DatabaseDumper
    {
        $dumper = new class extends DatabaseDumper
        {
            /** @var array<int, string> */
            public array $written = [];

            public function dump(string $absolutePath): void
            {
                @mkdir(dirname($absolutePath), 0755, true);
                $this->written[] = $absolutePath;
                file_put_contents($absolutePath, "-- fake dump\nCREATE TABLE fixture (id INT);\n");
            }
        };

        $this->app->instance(DatabaseDumper::class, $dumper);

        return $dumper;
    }

    private function failingDumper(): DatabaseDumper
    {
        $dumper = new class extends DatabaseDumper
        {
            public function dump(string $absolutePath): void
            {
                throw new RuntimeException('mysqldump exited with code 2.');
            }
        };

        $this->app->instance(DatabaseDumper::class, $dumper);

        return $dumper;
    }

    public function test_back_up_page_requires_an_administrator(): void
    {
        $viewer = $this->viewer();

        $this->actingAs($viewer)
            ->get(route('settings.backup'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('settings.backup.store'))
            ->assertForbidden();
    }

    public function test_back_up_page_shows_real_details_and_no_placeholder_copy(): void
    {
        Storage::fake('local');

        $user = $this->admin();

        $response = $this->actingAs($user)->get(route('settings.backup'))->assertOk();

        $response->assertSee('Current Database')
            ->assertSee('Download Database Back-up')
            ->assertSee('No back-ups yet')
            ->assertSee('Restore guide (for non-technical readers)', false)
            ->assertSee('php artisan down')
            ->assertSee('php artisan up')
            ->assertDontSee('Backup functionality is in development')
            ->assertDontSee('dummy backup history');
    }

    public function test_administrator_can_take_download_and_delete_a_back_up(): void
    {
        Storage::fake('local');
        $this->fakeDumper();

        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('settings.backup.store'))
            ->assertRedirect(route('settings.backup'))
            ->assertSessionHas('status', 'Database back-up created.');

        $files = collect(Storage::disk('local')->files('backups'))
            ->values();
        $this->assertCount(1, $files);

        $filename = basename($files[0]);
        $this->assertMatchesRegularExpression(
            '/^purchasing-\d{4}-\d{2}-\d{2}-\d{6}\.sql$/',
            $filename,
        );

        $page = $this->actingAs($user)->get(route('settings.backup'));
        $page->assertOk()->assertSee($filename);

        $download = $this->actingAs($user)
            ->get(route('settings.backup.download', ['file' => $filename]))
            ->assertOk();

        $download->assertDownload($filename);
        $this->assertSame('application/sql', $download->headers->get('content-type'));

        $this->actingAs($user)
            ->delete(route('settings.backup.destroy', ['file' => $filename]))
            ->assertRedirect(route('settings.backup'))
            ->assertSessionHas('status', 'Database back-up deleted.');

        $this->assertFalse(Storage::disk('local')->exists('backups/'.$filename));

        $logs = ActivityLog::query()->pluck('record_label')->toArray();
        $this->assertContains('Database back-up: '.$filename, $logs);
    }

    public function test_failed_dump_leaves_no_file_and_flashes_an_error(): void
    {
        Storage::fake('local');
        $this->failingDumper();

        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('settings.backup.store'))
            ->assertRedirect(route('settings.backup'))
            ->assertSessionHasErrors('backup');

        $this->assertCount(0, Storage::disk('local')->files('backups'));
    }

    public function test_filenames_are_validated_for_download_and_delete(): void
    {
        Storage::fake('local');

        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('settings.backup.download', ['file' => 'not-a-backup.sql']))
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('settings.backup.destroy', ['file' => '../database/database.sql']))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('settings.backup.download', ['file' => 'purchasing-2026-99-99-999999.sql']))
            ->assertNotFound();
    }

    public function test_back_up_storage_prunes_to_keep_the_newest_files(): void
    {
        Storage::fake('local');

        for ($i = 1; $i <= 20; $i++) {
            Storage::disk('local')->put(
                sprintf('backups/purchasing-2026-11-%02d-000000.sql', $i),
                'dump',
            );
        }

        BackupStorage::prune();

        $remaining = collect(Storage::disk('local')->files('backups'))
            ->map(fn (string $path): string => basename($path))
            ->sort()
            ->values();

        $this->assertCount(BackupStorage::KEEP, $remaining);
        $this->assertStringNotContainsString('2026-11-01', $remaining[0]);
        $this->assertStringContainsString('2026-11-20', $remaining->last());
    }
}
