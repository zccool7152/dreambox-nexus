<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /public/nexus/monitor/
 */

$_GET['r'] = '/nexus/monitor';
require_once dirname(__DIR__, 3) . '/public/index.php';
