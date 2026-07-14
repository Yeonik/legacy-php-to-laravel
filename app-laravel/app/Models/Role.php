<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Fixes part of F-18: the legacy schema stored the role as a free-text
 * VARCHAR, so 'admin', 'Admin' and 'adminn' were all equally valid.
 */
enum Role: string
{
    case Admin  = 'admin';
    case Editor = 'editor';
    case Reader = 'reader';
}
