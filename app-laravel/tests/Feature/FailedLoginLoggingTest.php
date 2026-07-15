<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Regression test for F-08. A failed login must leave a trace — the legacy
 * login left none. This asserts the log record and its fields; it does not
 * exercise any attack, and no real credential is used.
 */
final class FailedLoginLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_failed_login_is_recorded_with_the_email_and_ip(): void
    {
        /** @var list<MessageLogged> $records */
        $records = [];
        Log::listen(function (MessageLogged $event) use (&$records): void {
            $records[] = $event;
        });

        $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'not-the-password',
        ]);

        $warnings = array_values(array_filter(
            $records,
            static fn (MessageLogged $event): bool => $event->level === 'warning',
        ));

        $this->assertCount(1, $warnings, 'exactly one warning is logged for a failed login');
        $this->assertSame('nobody@example.com', $warnings[0]->context['email']);
        $this->assertNotNull($warnings[0]->context['ip']);
    }
}
