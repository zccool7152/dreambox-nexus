<?php
declare(strict_types=1);

namespace Database\Seeders;

use Nexus\Database\Database;
use Nexus\Database\Seeder;

/**
 * Primary Database Seeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(Database $db): void
    {
        // Baseline initial seed logic (e.g. initial consent purposes, default settings)
        $db->execute("INSERT IGNORE INTO cache_store (cache_key, cache_value, expiration) VALUES ('nexus:system:initialized', '1', ?)", [
            time() + (365 * 86400)
        ]);
    }
}

