<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    /** Requests from the web app's own origin get Sanctum's session (SPA) handling. */
    private const SPA = ['Referer' => 'http://localhost/'];

    public function test_the_web_app_registers_and_is_signed_in_with_a_session(): void
    {
        $this->withHeaders(self::SPA)->postJson('/api/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
        ])
            ->assertCreated()
            ->assertJsonPath('user.email', 'ada@example.com')
            ->assertJsonMissingPath('token');

        $this->assertAuthenticatedAs(User::where('email', 'ada@example.com')->sole(), 'web');
    }

    public function test_the_web_app_signs_in_and_out_with_a_session(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->withHeaders(self::SPA)->postJson('/api/login', ['email' => 'ada@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
        $this->assertAuthenticatedAs($user, 'web');

        $this->withHeaders(self::SPA)->getJson('/api/user')->assertOk()->assertJsonPath('data.name', $user->name);

        $this->withHeaders(self::SPA)->postJson('/api/logout')->assertNoContent();
        $this->assertGuest('web');
    }

    public function test_the_mobile_app_gets_a_token_by_naming_its_device(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $token = $this->postJson('/api/login', [
            'email' => 'ada@example.com',
            'password' => 'password',
            'device_name' => 'Ada’s phone',
        ])->assertOk()->json('token');

        $this->assertNotEmpty($token);
        $this->assertSame('Ada’s phone', $user->tokens()->sole()->name);

        $this->withToken($token)->getJson('/api/user')->assertOk()->assertJsonPath('data.email', 'ada@example.com');
    }

    public function test_registering_from_the_mobile_app_returns_a_token(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
            'device_name' => 'phone',
        ])
            ->assertCreated()
            ->assertJsonPath('user.name', 'Ada')
            ->assertJsonStructure(['token']);
    }

    public function test_signing_out_on_mobile_revokes_only_that_devices_token(): void
    {
        $user = User::factory()->create();
        $phone = $user->createToken('phone')->plainTextToken;
        $user->createToken('tablet');

        $this->withToken($phone)->postJson('/api/logout')->assertNoContent();

        $this->assertSame(['tablet'], $user->tokens()->pluck('name')->all());
    }

    public function test_a_wrong_password_is_rejected_without_saying_which_part_was_wrong(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/login', ['email' => 'ada@example.com', 'password' => 'nope', 'device_name' => 'phone'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'These credentials do not match our records.']);

        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'nope', 'device_name' => 'phone'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'These credentials do not match our records.']);
    }

    public function test_requests_without_a_session_must_name_a_device(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/login', ['email' => 'ada@example.com', 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device_name']);
    }

    public function test_registration_is_validated(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
            'device_name' => 'phone',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_signing_in_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);
        $attempt = ['email' => 'ada@example.com', 'password' => 'nope', 'device_name' => 'phone'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', $attempt)->assertUnprocessable();
        }

        $this->postJson('/api/login', $attempt)->assertTooManyRequests();
    }

    public function test_the_current_user_needs_a_signed_in_request(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }
}
