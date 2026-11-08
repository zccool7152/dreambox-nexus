<?php
declare(strict_types=1);

/**
 * Dreambox Nexus: Physical Route Bridge for /health/ready/
 */

$_GET['r'] = '/health/ready';
require_once dirname(__DIR__, 2) . '/public/index.php';
