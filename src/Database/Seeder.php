<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * Base Database Seeder
 */
abstract class Seeder
{
    abstract public function run(Database $db): void;

    /**
     * Run a child seeder class
     */
    public function call(string $seederClass, Database $db): void
    {
        if (!class_exists($seederClass)) {
            throw new \InvalidArgumentException("Seeder class '{$seederClass}' not found.");
        }

        /** @var Seeder $instance */
        $instance = new $seederClass();
        $instance->run($db);
    }
}

