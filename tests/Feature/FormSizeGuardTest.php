<?php

namespace Tests\Feature;

use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormSizeGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_posts_at_the_input_variable_cap_are_rejected(): void
    {
        // Simulate PHP's max_input_vars truncation point without needing the
        // real SAPI limit (which cannot be changed at runtime).
        config(['app.max_form_vars' => 5]);

        $this->post('/login', [
            'login' => 'admin',
            'password' => 'admin',
            'extra_one' => '1',
            'extra_two' => '2',
            'extra_three' => '3',
            'extra_four' => '4',
        ])->assertSessionHasErrors([
            'form' => 'This form is larger than the server can accept in one piece. Please enable JavaScript and submit again.',
        ]);

        $this->assertGuest();
    }

    public function test_form_posts_below_the_cap_still_reach_the_controller(): void
    {
        config(['app.max_form_vars' => 1000]);

        $this->post('/login')
            ->assertSessionHasErrors(['login', 'password'])
            ->assertSessionMissing('errors.form');
    }

    public function test_posts_with_an_intact_json_payload_are_not_treated_as_truncated(): void
    {
        $this->seed(AdminUserSeeder::class);
        config(['app.max_form_vars' => 5]);

        // Classic fields plus one intact *_json payload: the JSON carries the
        // real rows, so hitting the cap on the fallback fields is harmless.
        $this->post('/login', [
            'login' => 'admin',
            'password' => 'admin',
            'items_json' => json_encode([['description' => 'Row one']]),
            'extra_one' => '1',
            'extra_two' => '2',
            'extra_three' => '3',
        ])->assertRedirect('/projects');

        $this->assertAuthenticated();
    }

    public function test_malformed_json_does_not_exempt_a_truncated_post(): void
    {
        config(['app.max_form_vars' => 5]);

        $this->post('/login', [
            'login' => 'admin',
            'password' => 'admin',
            'items_json' => '{"broken":',
            'extra_one' => '1',
            'extra_two' => '2',
        ])->assertSessionHasErrors('form');

        $this->assertGuest();
    }

    public function test_oversized_posts_render_the_friendly_413_page(): void
    {
        $limit = $this->postMaxSizeBytes();

        if ($limit < 1) {
            $this->markTestSkipped('post_max_size is unlimited in this environment.');
        }

        $this->call('POST', '/login', [], [], [], [
            'CONTENT_LENGTH' => $limit + 1,
        ])->assertStatus(413)
            ->assertSee('Submission too large')
            ->assertSee('nothing was saved');
    }

    private function postMaxSizeBytes(): int
    {
        $value = trim((string) ini_get('post_max_size'));

        if ($value === '' || $value === '0' || $value === '-1') {
            return 0;
        }

        $unit = strtoupper(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'K' => $number * 1024,
            'M' => $number * 1024 * 1024,
            'G' => $number * 1024 * 1024 * 1024,
            default => $number,
        };
    }
}
