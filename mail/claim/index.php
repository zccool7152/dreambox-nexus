<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /mail/claim/
 */

if (empty($_GET['r'])) {
    $_GET['r'] = '/mail/claim';
}
require_once dirname(__DIR__, 2) . '/public/index.php';

