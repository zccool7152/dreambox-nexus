<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /auth/oauth/github/
 */

if (empty($_GET['r'])) {
    $_GET['r'] = '/auth/oauth/github';
}
require_once dirname(__DIR__, 3) . '/public/index.php';
