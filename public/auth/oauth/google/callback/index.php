<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /public/auth/oauth/google/callback/
 */

if (empty($_GET['r'])) {
    $_GET['r'] = '/auth/oauth/google/callback';
}
require_once dirname(__DIR__, 4) . '/index.php';
