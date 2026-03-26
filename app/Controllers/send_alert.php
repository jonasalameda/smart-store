<?php

declare(strict_types=1);

/**
 * Legacy entrypoint — same behaviour as /public/send-email.php.
 * Prefer requesting /send-email.php from the dashboard.
 */
require dirname(__DIR__, 2) . '/public/send-email.php';
