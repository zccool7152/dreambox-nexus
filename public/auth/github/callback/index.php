<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /public/auth/github/callback/
 */

if (empty($_GET['r'])) {
    $_GET['r'] = '/auth/github/callback';
}
require_once dirname(__DIR__, 3) . '/index.php';
