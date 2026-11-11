<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /auth/google/callback/
 */

if (empty($_GET['r'])) {
    $_GET['r'] = '/auth/google/callback';
}
require_once dirname(__DIR__, 3) . '/public/index.php';
