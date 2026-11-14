<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /mail/send/
 */

if (empty($_GET['r'])) {
    $_GET['r'] = '/mail/send';
}
require_once dirname(__DIR__, 2) . '/public/index.php';

