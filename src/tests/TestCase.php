<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Feature\Auth\EmailVerificationTest;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function actingAs(AuthenticatableContract $user, $guard = null)
    {
        if (!$this instanceof EmailVerificationTest) {
            $this->verifyUserForFeatureTest($user);
        }

        return parent::actingAs($user, $guard);
    }

    private function verifyUserForFeatureTest(AuthenticatableContract $user): void
    {
        if (!$user instanceof MustVerifyEmail) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();
    }
}