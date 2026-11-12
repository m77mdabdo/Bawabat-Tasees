<?php

namespace App\Leads;

use Throwable;

/**
 * How a mail failure is written to a lead's `mail_error` and to the log: the exception class and a
 * short message with any email address replaced, so neither keeps a copy of who was being emailed.
 */
final class MailFailure
{
    public static function describe(Throwable $e): string
    {
        $message = preg_replace('/[^\s<>"\'(),;:]+@[^\s<>"\'(),;:]+/u', '[address]', $e->getMessage()) ?? '';
        $message = trim(preg_replace('/\s+/u', ' ', $message) ?? '');

        return class_basename($e).($message !== '' ? ': '.mb_strimwidth($message, 0, 120, '…') : '');
    }
}
