<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /auth/logout/
 */

if (empty($_GET['r'])) {
    $_GET['r'] = '/auth/logout';
}
require_once dirname(__DIR__, 2) . '/public/index.php';

