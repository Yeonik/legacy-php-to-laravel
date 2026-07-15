<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * F-08: the legacy login logged nothing, so a run of failed attempts —
 * credential stuffing, a targeted guess — left no trace at all. Every failure
 * now records the email that was tried and the source IP, which is what turns
 * "someone is hammering the login" from invisible into a log query.
 *
 * The password is never recorded.
 *
 * Registered automatically by Laravel's event discovery via the `Failed` type
 * hint on handle() — see the note in AppServiceProvider.
 */
final class LogFailedLogin
{
    public function __construct(private readonly Request $request) {}

    public function handle(Failed $event): void
    {
        Log::warning('Failed login attempt', [
            'email' => $event->credentials['email'] ?? null,
            'ip' => $this->request->ip(),
        ]);
    }
}
