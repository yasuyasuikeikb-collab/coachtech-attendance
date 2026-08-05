<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_email_is_sent_after_registration(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'メール認証テストユーザー',
            'email' => 'email-verification@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'email-verification@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);

        Notification::assertSentTo($user, VerifyEmail::class);

        $response->assertRedirect();
    }

    public function test_unverified_user_is_redirected_to_verification_notice(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertRedirect('/email/verify');

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertOk()
            ->assertSeeText('登録していただいたメールアドレスに認証メールを送付しました。')
            ->assertSeeText('メール認証を完了してください。')
            ->assertSeeText('認証はこちらから')
            ->assertSeeText('認証メールを再送する');
    }

    public function test_email_can_be_verified_from_signed_link(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'admin_status' => false,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertNotNull($user->fresh()->email_verified_at);

        $response->assertRedirect();
        $this->assertStringContainsString(
            RouteServiceProvider::HOME,
            $response->headers->get('Location')
        );
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
            'admin_status' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/email/verify')
            ->post(route('verification.send'));

        $response
            ->assertRedirect('/email/verify')
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_can_access_attendance_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'admin_status' => false,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSeeText('勤務外');
    }
}