<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /public/auth/oauth/google/
 */

if (empty($_GET['r'])) {
    $_GET['r'] = '/auth/oauth/google';
}
require_once dirname(__DIR__, 3) . '/index.php';
