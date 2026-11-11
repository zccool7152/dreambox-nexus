<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /auth/oauth/
 */

if (empty($_GET['r'])) {
    $_GET['r'] = '/auth/login';
}
require_once dirname(__DIR__, 2) . '/public/index.php';
