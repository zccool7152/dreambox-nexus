<?php
declare(strict_types=1);

namespace Nexus\Console;

use Nexus\Core\Container;
use Nexus\Database\Database;
use Nexus\Database\MigrationRunner;
use Nexus\Compliance\ComplianceVerifier;
use Nexus\Queue\Worker;
use Nexus\Schedule\Scheduler;
use Nexus\Cache\Cache;
use Nexus\Core\Router;

/**
 * Terminal Command Dispatcher & Scaffolding Engine
 */
class ConsoleKernel
{
    private Container $container;
    private string $basePath;

    public function __construct(Container $container, string $basePath)
    {
        $this->container = $container;
        $this->basePath = $basePath;
    }

    public function handle(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $arg1 = $argv[2] ?? '';

        return match ($command) {
            'migrate' => $this->handleMigrate(),
            'migrate:rollback' => $this->handleRollback(),
            'db:seed' => $this->handleSeed($arg1),
            'compliance:verify' => $this->handleComplianceVerify(),
            'queue:work' => $this->handleQueueWork($argv),
            'schedule:run' => $this->handleScheduleRun(),
            'routes:list' => $this->handleRoutesList(),
            'cache:clear' => $this->handleCacheClear(),
            'down' => $this->handleDown($arg1),
            'up' => $this->handleUp(),
            'make:model' => $this->handleMakeModel($arg1),
            'make:controller' => $this->handleMakeController($arg1),
            'make:migration' => $this->handleMakeMigration($arg1),
            default => $this->showHelp(),
        };
    }

    private function handleMigrate(): int
    {
        echo "\033[1;36m[Nexus]\033[0m Executing pending database migrations...\n";
        /** @var Database $db */
        $db = $this->container->make(Database::class);
        $runner = new MigrationRunner($db);
        $applied = $runner->migrate();

        if (empty($applied)) {
            echo "\033[0;32mNothing to migrate.\033[0m All schemas up to date.\n";
        } else {
            foreach ($applied as $m) {
                echo "  \033[0;32m✔\033[0m Applied: {$m}\n";
            }
        }

        return 0;
    }

    private function handleRollback(): int
    {
        echo "\033[1;33m[Nexus]\033[0m Rolling back last migration batch...\n";
        /** @var Database $db */
        $db = $this->container->make(Database::class);
        $runner = new MigrationRunner($db);
        $rolled = $runner->rollback();

        foreach ($rolled as $m) {
            echo "  \033[0;33m↺\033[0m Rolled back: {$m}\n";
        }

        return 0;
    }

    private function handleSeed(string $seederClass): int
    {
        $class = $seederClass ?: 'Database\Seeders\DatabaseSeeder';
        echo "\033[1;36m[Nexus]\033[0m Running seeder: {$class}...\n";

        /** @var Database $db */
        $db = $this->container->make(Database::class);
        if (class_exists($class)) {
            $instance = new $class();
            $instance->run($db);
            echo "\033[0;32m✔\033[0m Database seeded successfully.\n";
            return 0;
        }

        echo "\033[0;31m✘\033[0m Seeder class '{$class}' not found.\n";
        return 1;
    }

    private function handleComplianceVerify(): int
    {
        echo "\033[1;35m════════════════════════════════════════════════════════════════\033[0m\n";
        echo "\033[1;35m  DREAMBOX NEXUS 10-POINT REGULATORY COMPLIANCE VERIFIER        \033[0m\n";
        echo "\033[1;35m  Standards: PCI-DSS v4.0 | HIPAA | NIST CSF 2.0 | EU GDPR      \033[0m\n";
        echo "\033[1;35m════════════════════════════════════════════════════════════════\033[0m\n\n";

        /** @var Database $db */
        $db = $this->container->make(Database::class);
        $verifier = new ComplianceVerifier($db);
        $results = $verifier->verifyAll();

        $allPassed = true;
        foreach ($results as $check) {
            $badge = $check['passed'] ? "\033[0;32m[PASS]\033[0m" : "\033[0;31m[FAIL]\033[0m";
            if (!$check['passed']) $allPassed = false;
            echo "{$badge} {$check['name']}\n";
            echo "       └─ \033[0;90m{$check['details']}\033[0m\n";
        }

        echo "\n";
        if ($allPassed) {
            echo "\033[1;32m✔ 100% COMPLIANT: All 10 regulatory gates successfully passed.\033[0m\n";
            return 0;
        }

        echo "\033[1;31m✘ COMPLIANCE DEFICIT: Resolve failing items above before audit.\033[0m\n";
        return 1;
    }

    private function handleQueueWork(array $argv): int
    {
        echo "\033[1;36m[Nexus]\033[0m Starting background worker (SKIP LOCKED)...\n";
        /** @var Database $db */
        $db = $this->container->make(Database::class);
        $worker = new Worker($db);
        $worker->run('default', 0, 128, 2);
        return 0;
    }

    private function handleScheduleRun(): int
    {
        echo "\033[1;36m[Nexus]\033[0m Evaluating scheduled crontab tasks...\n";
        /** @var Database $db */
        $db = $this->container->make(Database::class);
        $scheduler = Scheduler::registerDefaultTasks(new Scheduler(null, $db));
        $results = $scheduler->run();

        foreach ($results as $res) {
            $badge = $res['success'] ? "\033[0;32m✔\033[0m" : "\033[0;31m✘\033[0m";
            echo "  {$badge} {$res['name']} ({$res['duration_ms']}ms)\n";
        }
        return 0;
    }

    private function handleRoutesList(): int
    {
        /** @var Router $router */
        $router = $this->container->make(Router::class);
        $routes = $router->getRoutes();

        echo "\n\033[1;37m" . str_pad("METHOD", 8) . str_pad("URI", 35) . "MIDDLEWARE\033[0m\n";
        echo str_repeat("─", 65) . "\n";

        foreach ($routes as $r) {
            $methodCol = "\033[0;36m" . str_pad($r['method'], 8) . "\033[0m";
            $uriCol = str_pad($r['uri'], 35);
            $mwCol = !empty($r['middleware']) ? implode(', ', array_map(fn($m) => is_string($m) ? basename(str_replace('\\', '/', $m)) : 'Closure', $r['middleware'])) : 'None';
            echo "{$methodCol}{$uriCol}{$mwCol}\n";
        }
        echo "\n";
        return 0;
    }

    private function handleCacheClear(): int
    {
        /** @var Cache $cache */
        $cache = $this->container->make(Cache::class);
        $cache->flush();
        echo "\033[0;32m✔\033[0m System cache and temporary session files flushed.\n";
        return 0;
    }

    private function handleDown(string $secret): int
    {
        $downDir = $this->basePath . '/storage/framework';
        if (!is_dir($downDir)) {
            mkdir($downDir, 0755, true);
        }

        $token = $secret ?: bin2hex(random_bytes(16));
        $data = [
            'down_at' => time(),
            'secret' => $token,
            'message' => 'Service Unavailable for Maintenance.',
        ];

        file_put_contents($downDir . '/down', json_encode($data, JSON_PRETTY_PRINT));
        echo "\033[0;33mApplication is now in maintenance mode.\033[0m\n";
        echo "Bypass Secret Cookie: \033[1;37mnexus_maintenance_bypass={$token}\033[0m\n";
        return 0;
    }

    private function handleUp(): int
    {
        $downFile = $this->basePath . '/storage/framework/down';
        if (file_exists($downFile)) {
            unlink($downFile);
        }
        echo "\033[0;32mApplication is now live and accepting traffic.\033[0m\n";
        return 0;
    }

    private function handleMakeModel(string $name): int
    {
        if (trim($name) === '') {
            echo "\033[0;31mPlease provide a model name: php bin/nexus make:model User\033[0m\n";
            return 1;
        }

        $file = $this->basePath . "/src/Models/{$name}.php";
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $table = strtolower($name) . 's';
        $stub = "<?php\ndeclare(strict_types=1);\n\nnamespace Nexus\\Models;\n\nuse Nexus\\Database\\Model;\n\nclass {$name} extends Model\n{\n    protected string \$table = '{$table}';\n    protected array \$fillable = [];\n}\n";

        file_put_contents($file, $stub);
        echo "\033[0;32m✔\033[0m Model created: src/Models/{$name}.php\n";
        return 0;
    }

    private function handleMakeController(string $name): int
    {
        if (trim($name) === '') {
            echo "\033[0;31mPlease provide a controller name: php bin/nexus make:controller UserController\033[0m\n";
            return 1;
        }

        $file = $this->basePath . "/src/Controllers/{$name}.php";
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $stub = "<?php\ndeclare(strict_types=1);\n\nnamespace Nexus\\Controllers;\n\nuse Nexus\\Core\\Request;\nuse Nexus\\Core\\Response;\n\nclass {$name}\n{\n    public function index(Request \$request): Response\n    {\n        return Response::json([], 'Index retrieved.', 200, [], \$request->getTraceId());\n    }\n}\n";

        file_put_contents($file, $stub);
        echo "\033[0;32m✔\033[0m Controller created: src/Controllers/{$name}.php\n";
        return 0;
    }

    private function handleMakeMigration(string $name): int
    {
        if (trim($name) === '') {
            echo "\033[0;31mPlease provide a migration name: php bin/nexus make:migration create_orders_table\033[0m\n";
            return 1;
        }

        $prefix = date('Ymd_His');
        $filename = "{$prefix}_{$name}.sql";
        $file = $this->basePath . "/database/migrations/{$filename}";

        $stub = "-- {$filename}\nCREATE TABLE IF NOT EXISTS {$name} (\n    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";

        file_put_contents($file, $stub);
        echo "\033[0;32m✔\033[0m Migration created: database/migrations/{$filename}\n";
        return 0;
    }

    private function showHelp(): int
    {
        echo "\033[1;36mDreambox Nexus CLI Runner (v0.1.0 Alpha)\033[0m\n\n";
        echo "Available Commands:\n";
        echo "  \033[0;32mmigrate\033[0m                 Run pending database schema migrations\n";
        echo "  \033[0;32mmigrate:rollback\033[0m        Undo the last database migration batch\n";
        echo "  \033[0;32mdb:seed\033[0m                 Run database seeders\n";
        echo "  \033[0;32mcompliance:verify\033[0m       Execute 10-point regulatory audit (PCI/HIPAA/NIST/GDPR)\n";
        echo "  \033[0;32mqueue:work\033[0m              Start background queue worker daemon\n";
        echo "  \033[0;32mschedule:run\033[0m            Run scheduled recurring tasks\n";
        echo "  \033[0;32mroutes:list\033[0m             Display registered route directory and middleware map\n";
        echo "  \033[0;32mcache:clear\033[0m             Flush application cache store\n";
        echo "  \033[0;32mdown / up\033[0m               Toggle zero-downtime maintenance mode\n";
        echo "  \033[0;32mmake:model\033[0m              Generate new mass-assignment safe model\n";
        echo "  \033[0;32mmake:controller\033[0m         Generate new HTTP controller\n";
        echo "  \033[0;32mmake:migration\033[0m          Create timestamped migration file\n\n";
        return 0;
    }
}

