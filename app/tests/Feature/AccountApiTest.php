<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    public function test_a_reset_link_is_emailed_and_points_at_the_web_app(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/forgot-password', ['email' => ' Ada@Example.com '])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, url('/reset-password/'.$notification->token)) && str_contains($url, 'email=ada%40example.com');
        });
    }

    public function test_unknown_emails_get_the_same_answer(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ada@example.com']);

        $known = $this->postJson('/api/forgot-password', ['email' => 'ada@example.com'])->json('message');
        $unknown = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])->assertOk()->json('message');

        $this->assertSame($known, $unknown);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_resetting_the_password_signs_out_every_device(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $user->createToken('phone');
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'ada@example.com',
            'password' => 'a brand new passphrase',
            'password_confirmation' => 'a brand new passphrase',
        ])->assertOk();

        $this->assertTrue(password_verify('a brand new passphrase', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_wrong_or_used_token_is_refused(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $token = Password::createToken($user);
        $reset = fn (string $token) => $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'ada@example.com',
            'password' => 'a brand new passphrase',
            'password_confirmation' => 'a brand new passphrase',
        ]);

        $reset('not-the-token')->assertUnprocessable()->assertJsonValidationErrors('email');
        $reset($token)->assertOk();
        $reset($token)->assertUnprocessable();
    }

    public function test_registering_sends_a_confirmation_email(): void
    {
        Notification::fake();

        $this->postJson('/api/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
            'device_name' => 'phone',
        ])->assertCreated()->assertJsonPath('user.email_verified', false);

        Notification::assertSentTo(User::where('email', 'ada@example.com')->sole(), VerifyEmail::class);
    }

    public function test_the_signed_link_confirms_the_email_without_signing_in(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->get($url)->assertRedirect('/account?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_tampered_or_unsigned_links_are_refused(): void
    {
        $user = User::factory()->unverified()->create();

        $wrongHash = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('someone@else.com')]);
        $this->get($wrongHash)->assertForbidden();
        $this->get("/email/verify/{$user->id}/".sha1($user->email))->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_the_confirmation_email_can_be_sent_again(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/email/verification-notification')->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_an_export_holds_the_account_and_its_progress(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $lesson = Lesson::where('slug', 'your-first-queries')->firstOrFail();
        $user->lessonProgress()->create(['lesson_id' => $lesson->id, 'data' => ['read' => true, 'theoryPage' => null, 'sandboxCode' => null, 'quiz' => [], 'challenges' => []]]);
        $user->reviewCards()->create(['item_key' => 'quiz:1', 'due' => 1000, 'interval' => 1, 'ease' => 2.5, 'reps' => 1, 'lapses' => 0]);
        Sanctum::actingAs($user);

        $this->getJson('/api/account/export')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="learnp-data.json"')
            ->assertJsonPath('account.email', 'ada@example.com')
            ->assertJsonPath('progress.0.course', 'sql')
            ->assertJsonPath('progress.0.lesson', 'your-first-queries')
            ->assertJsonPath('progress.0.read', true)
            ->assertJsonPath('review_cards.0.item', 'quiz:1');
    }

    public function test_deleting_the_account_needs_the_password_and_removes_everything(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::firstOrFail();
        $user->lessonProgress()->create(['lesson_id' => $lesson->id, 'data' => ['read' => true, 'theoryPage' => null, 'sandboxCode' => null, 'quiz' => [], 'challenges' => []]]);
        $token = $user->createToken('phone')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/account', ['password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertModelExists($user);

        $this->withToken($token)->deleteJson('/api/account', ['password' => 'password'])->assertNoContent();

        $this->assertModelMissing($user);
        $this->assertDatabaseCount('lesson_progress', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_deleting_from_the_web_app_signs_out_and_stays_deleted(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $spa = ['Referer' => 'http://localhost/'];

        // Signed in with "remember me", as the web app does.
        $this->withHeaders($spa)->postJson('/api/login', ['email' => 'ada@example.com', 'password' => 'password'])->assertOk();
        $this->withHeaders($spa)->deleteJson('/api/account', ['password' => 'password'])->assertNoContent();

        $this->assertGuest('web');
        $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
    }

    public function test_github_accounts_confirm_deletion_with_their_email(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com', 'password' => null, 'github_id' => '42']);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/account', ['confirmation' => 'bob@example.com'])->assertUnprocessable()->assertJsonValidationErrors('confirmation');
        $this->deleteJson('/api/account', ['confirmation' => 'ADA@example.com'])->assertNoContent();

        $this->assertModelMissing($user);
    }

    public function test_github_sign_in_is_hidden_until_configured(): void
    {
        config(['services.github.client_id' => null]);
        $this->getJson('/api/auth/options')->assertExactJson(['github' => false]);
        $this->get('/auth/github')->assertNotFound();

        config(['services.github.client_id' => 'id', 'services.github.client_secret' => 'secret']);
        $this->getJson('/api/auth/options')->assertExactJson(['github' => true]);
    }

    public function test_github_creates_a_confirmed_account_and_signs_in_on_the_web(): void
    {
        $this->fakeGitHub(id: '42', email: 'Ada@Example.com');

        $this->get('/auth/github/callback')->assertRedirect('/');

        $user = User::where('github_id', '42')->sole();
        $this->assertSame('ada@example.com', $user->email);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertFalse($user->hasPassword());
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_github_links_an_existing_account_with_the_same_email(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $this->fakeGitHub(id: '42', email: 'ada@example.com');

        $this->get('/auth/github/callback')->assertRedirect('/');

        $this->assertSame('42', $user->fresh()->github_id);
        $this->assertSame(1, User::count());
    }

    public function test_github_never_relinks_an_account_to_another_github_user(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'github_id' => '7']);
        $this->fakeGitHub(id: '42', email: 'ada@example.com');

        $this->get('/auth/github/callback')->assertRedirect('/sign-in?error=github-linked');
        $this->assertGuest('web');
    }

    public function test_the_mobile_app_gets_a_one_time_code_for_a_token(): void
    {
        $this->fakeGitHub(id: '42', email: 'ada@example.com');

        $this->get('/auth/github?return_to='.urlencode('learnp://auth/github'));
        $location = $this->get('/auth/github/callback')->headers->get('Location');

        $this->assertStringStartsWith('learnp://auth/github?code=', $location);
        $this->assertGuest('web');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $this->postJson('/api/auth/github/exchange', ['code' => $query['code'], 'device_name' => 'phone'])
            ->assertOk()
            ->assertJsonPath('user.email', 'ada@example.com')
            ->assertJsonStructure(['token']);

        // A code works only once.
        $this->postJson('/api/auth/github/exchange', ['code' => $query['code'], 'device_name' => 'phone'])->assertUnprocessable();
    }

    public function test_codes_are_never_sent_to_other_apps(): void
    {
        $this->fakeGitHub(id: '42', email: 'ada@example.com');

        $this->get('/auth/github?return_to='.urlencode('https://evil.example/steal'));

        $this->get('/auth/github/callback')->assertRedirect('/');
    }

    private function fakeGitHub(string $id, string $email): void
    {
        config(['services.github.client_id' => 'id', 'services.github.client_secret' => 'secret']);

        Socialite::fake('github', (new SocialiteUser)->map([
            'id' => $id,
            'nickname' => 'ada',
            'name' => 'Ada Lovelace',
            'email' => $email,
        ]));
    }
}
