<?php

namespace Tests\Feature;

use App\Models\PromptSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PromptSubmissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_accepts_only_explicitly_submitted_fields_and_returns_a_reference(): void
    {
        $submissionId = (string) Str::uuid();

        $response = $this->postJson(route('promptSubmissions.store'), $this->submission([
            'submission_id' => $submissionId,
            'prompt' => "  Make a different wallpaper for rainy evenings.\n  ",
            'name' => '  @freekmurze  ',
            'email' => '  freek@example.com  ',
        ]))->assertCreated()->assertJsonStructure(['reference']);

        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $response->json('reference'));
        $this->assertDatabaseHas('prompt_submissions', [
            'submission_id' => $submissionId,
            'reference' => $response->json('reference'),
            'prompt' => 'Make a different wallpaper for rainy evenings.',
            'name' => 'freekmurze',
            'email' => 'freek@example.com',
            'app_version' => '0.1.0',
            'app_build' => '3',
        ]);
        $this->assertSame(['reference' => $response->json('reference')], $response->json());
        $this->assertNotContains('ip_address', Schema::getColumnListing('prompt_submissions'));
        $this->assertNotContains('token', Schema::getColumnListing('prompt_submissions'));
    }

    public function test_an_identical_retry_returns_the_original_reference_and_a_changed_body_returns_409(): void
    {
        $payload = $this->submission(['prompt' => '  Add a sunrise preview.  ']);

        $created = $this->postJson(route('promptSubmissions.store'), $payload)->assertCreated();
        $this->postJson(route('promptSubmissions.store'), [
            ...$payload,
            'submission_id' => strtoupper($payload['submission_id']),
            'prompt' => 'Add a sunrise preview.',
            'name' => null,
        ])->assertOk()->assertExactJson(['reference' => $created->json('reference')]);
        $this->postJson(route('promptSubmissions.store'), [
            ...$payload,
            'email' => 'someone@example.com',
        ])->assertStatus(409)->assertJsonPath('message', 'This submission ID already belongs to a different request.');

        $this->assertDatabaseCount('prompt_submissions', 1);
        $this->assertDatabaseHas('prompt_submissions', [
            'submission_id' => $payload['submission_id'],
            'prompt' => 'Add a sunrise preview.',
            'email' => null,
        ]);
    }

    public function test_invalid_and_extra_fields_return_422_without_persistence(): void
    {
        $this->postJson(route('promptSubmissions.store'), $this->submission([
            'submission_id' => '123e4567-e89b-12d3-a456-426614174000',
            'prompt' => '  ',
            'name' => 'freek@example.com',
            'email' => 'not-an-email',
            'app_version' => 'version one',
            'app_build' => 'build 3',
            'picture' => 'private',
        ]))->assertStatus(422)->assertJsonValidationErrors([
            'submission_id', 'prompt', 'name', 'email', 'app_version', 'app_build', 'payload',
        ]);

        $this->postJson(route('promptSubmissions.store'), $this->submission([
            'prompt' => Str::random(5001),
        ]))->assertStatus(422)->assertJsonValidationErrors('prompt');

        $this->postJson(route('promptSubmissions.store'), $this->submission([
            'prompt' => "A feature\0with a null byte",
        ]))->assertStatus(422)->assertJsonValidationErrors('prompt');

        $this->postJson(route('promptSubmissions.store'), $this->submission([
            'name' => str_repeat('n', 61),
            'email' => str_repeat('a', 245).'@example.com',
        ]))->assertStatus(422)->assertJsonValidationErrors(['name', 'email']);

        $this->assertDatabaseCount('prompt_submissions', 0);
    }

    public function test_missing_required_fields_return_422_and_blank_optional_fields_become_null(): void
    {
        $this->postJson(route('promptSubmissions.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['submission_id', 'prompt', 'app_version', 'app_build']);

        $this->postJson(route('promptSubmissions.store'), $this->submission([
            'name' => ' @ ',
            'email' => '  ',
        ]))->assertCreated();

        $this->postJson(route('promptSubmissions.store'), $this->submission([
            'prompt' => str_repeat('a', 5000),
            'name' => str_repeat('n', 60),
        ]))->assertCreated();

        $this->assertDatabaseHas('prompt_submissions', ['name' => null, 'email' => null]);
        $this->assertDatabaseHas('prompt_submissions', ['name' => str_repeat('n', 60)]);
    }

    public function test_non_json_and_bodies_over_32_kib_return_415_and_413(): void
    {
        $this->post(route('promptSubmissions.store'), $this->submission())->assertStatus(415);

        $this->call('POST', route('promptSubmissions.store'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([...$this->submission(), 'padding' => str_repeat('x', 32768)]))->assertStatus(413);

        $this->assertDatabaseCount('prompt_submissions', 0);
    }

    public function test_the_source_is_limited_to_20_requests_per_hour(): void
    {
        $payload = $this->submission();

        $this->postJson(route('promptSubmissions.store'), $payload)->assertCreated();

        for ($attempt = 1; $attempt < 20; $attempt++) {
            $this->postJson(route('promptSubmissions.store'), $payload)->assertOk();
        }

        $this->postJson(route('promptSubmissions.store'), $payload)->assertStatus(429);
        $this->assertDatabaseCount('prompt_submissions', 1);
    }

    public function test_only_an_allowlisted_admin_can_read_submissions_and_user_text_is_escaped(): void
    {
        Config::set('services.admin.emails', ['owner@spatie.be']);
        $submission = PromptSubmission::factory()->create([
            'prompt' => '<script>alert(1)</script> Add more colors.',
            'name' => 'A helpful person',
        ]);

        $this->get('/admin/prompt-submissions')->assertRedirect('/admin/login');

        $outsider = User::factory()->create(['email' => 'other@example.com', 'is_admin' => true]);
        $this->actingAs($outsider)->get('/admin/prompt-submissions')->assertForbidden();

        $owner = User::factory()->create(['email' => 'owner@spatie.be', 'is_admin' => true]);
        $this->actingAs($owner)->get('/admin/prompt-submissions')
            ->assertOk()
            ->assertSeeText('Add more colors.')
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($owner)->get('/admin/prompt-submissions/'.$submission->id)
            ->assertOk()
            ->assertSeeText('A helpful person')
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($owner)->get('/admin/prompt-submissions/create')->assertNotFound();
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function submission(array $overrides = []): array
    {
        return [
            'submission_id' => (string) Str::uuid(),
            'prompt' => 'Show me a dawn preview.',
            'app_version' => '0.1.0',
            'app_build' => '3',
            ...$overrides,
        ];
    }
}
