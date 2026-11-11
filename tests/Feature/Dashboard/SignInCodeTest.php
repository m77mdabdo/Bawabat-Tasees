<?php

namespace Tests\Feature\Dashboard;

use App\Auth\EmailSignInCode;
use App\Mail\SignInCodeMail;
use App\Notifications\SignInCode;
use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/** Every dashboard sign-in finishes with a one-time code emailed to the person signing in. */
class SignInCodeTest extends DashboardTestCase
{
    public function test_signing_in_needs_the_code_sent_to_the_email(): void
    {
        Notification::fake();
        $user = $this->makeAdmin();
        $user->forceFill(['password' => 'Long-Password-123456'])->save();

        $login = Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'Long-Password-123456'])
            ->call('authenticate');
        $this->assertGuest();

        $code = null;
        Notification::assertSentTo($user, SignInCode::class, function (SignInCode $n) use (&$code) {
            $code = $n->code;

            return $n->codeExpiryMinutes === EmailSignInCode::EXPIRY_MINUTES;
        });
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $login->set('data.multiFactor.email_code.code', $code === '000000' ? '111111' : '000000')->call('authenticate')->assertHasErrors();
        $this->assertGuest();

        $login->set('data.multiFactor.email_code.code', $code)->call('authenticate')->assertHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_a_wrong_password_sends_no_code(): void
    {
        Notification::fake();
        $user = $this->makeAdmin();

        Livewire::test(Login::class)->fillForm(['email' => $user->email, 'password' => 'wrong-password'])->call('authenticate')->assertHasFormErrors();

        Notification::assertNothingSent();
    }

    public function test_the_code_email_goes_out_at_once_in_the_dashboard_language(): void
    {
        $user = $this->makeAdmin();
        $mail = (new SignInCode('482913', 10))->toMail($user);

        $this->assertInstanceOf(SignInCodeMail::class, $mail);
        $this->assertNotInstanceOf(ShouldQueue::class, new SignInCode('1', 1));
        $html = $mail->render();
        $this->assertStringContainsString('482913', $html);
        $this->assertStringContainsString('رمز الدخول', $mail->envelope()->subject);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertTrue($mail->hasTo($user->email));
    }
}
