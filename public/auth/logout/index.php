<?php
declare(strict_types=1);

if (empty($_GET['r'])) {
    $_GET['r'] = '/auth/logout';
}
require_once dirname(__DIR__, 2) . '/index.php';

