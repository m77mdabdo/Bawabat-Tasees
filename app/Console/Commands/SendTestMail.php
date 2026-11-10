<?php

namespace App\Console\Commands;

use App\Mail\TestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Proves the mail settings deliver: `php artisan mail:test someone@example.com [--locale=en]`. */
class SendTestMail extends Command
{
    protected $signature = 'mail:test {email : Address to send the test to} {--locale=ar : ar or en}';

    protected $description = 'Send a branded test email to check the mail settings';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Not a valid email address.');

            return self::INVALID;
        }

        $mailer = config('mail.default');
        $this->line("Mailer: {$mailer}");
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("The '{$mailer}' mailer does not deliver real email. Set MAIL_MAILER=smtp and the MAIL_* values in .env.");
        }

        try {
            Mail::to($email)->send(new TestMail($this->option('locale') === 'en' ? 'en' : 'ar'));
        } catch (Throwable $e) {
            $this->error('Sending failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Test email handed to the '{$mailer}' mailer for {$email}.");

        return self::SUCCESS;
    }
}
