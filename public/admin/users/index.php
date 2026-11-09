<?php
declare(strict_types=1);

if (empty($_GET['r'])) {
    $_GET['r'] = '/admin/users';
}
require_once dirname(__DIR__, 2) . '/index.php';

