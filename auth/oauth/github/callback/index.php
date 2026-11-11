<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /auth/oauth/github/callback/
 */

if (empty($_GET['r'])) {
    $_GET['r'] = '/auth/oauth/github/callback';
}
require_once dirname(__DIR__, 4) . '/public/index.php';
