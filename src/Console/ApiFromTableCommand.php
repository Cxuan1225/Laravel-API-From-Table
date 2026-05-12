<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Console;

use Cxuan1225\LaravelApiFromTable\Generators\ControllerGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\ModelGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\ResourceGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\RouteGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\SmokeTestGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\StoreActionGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\StoreDataGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\StoreRequestGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\UpdateActionGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\UpdateDataGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\UpdateRequestGenerator;
use Cxuan1225\LaravelApiFromTable\Schema\DatabaseSchemaReader;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\FileWriter;
use Illuminate\Console\Command;
use Throwable;

class ApiFromTableCommand extends Command
{
    protected $signature = 'api:from-table
                            {table : The database table name}
                            {--connection= : Database connection name}
                            {--dry-run : Preview generated code without writing files}
                            {--json : Emit --dry-run output as JSON}
                            {--force : Overwrite existing files}
                            {--model : Generate only the model}
                            {--requests : Generate only the form requests}
                            {--dto : Generate only the DTO files}
                            {--actions : Generate only the action files}
                            {--resource : Generate only the API resource}
                            {--controller : Generate only the API controller}
                            {--routes : Append a Route::apiResource entry to the configured routes path}
                            {--api-routes : Append a Route::apiResource entry to routes/api.php}
                            {--relationships : Generate opt-in relationships and resource relationship fields from foreign keys}
                            {--tests : Generate a Pest endpoint smoke test}
                            {--all : Generate all supported files}';

    protected $description = 'Generate Laravel API classes from an existing database table.';

    public function handle(
        DatabaseSchemaReader $reader,
        ModelGenerator $modelGenerator,
        StoreRequestGenerator $storeGenerator,
        UpdateRequestGenerator $updateGenerator,
        StoreDataGenerator $storeDataGenerator,
        UpdateDataGenerator $updateDataGenerator,
        StoreActionGenerator $storeActionGenerator,
        UpdateActionGenerator $updateActionGenerator,
        ResourceGenerator $resourceGenerator,
        ControllerGenerator $controllerGenerator,
        RouteGenerator $routeGenerator,
        SmokeTestGenerator $smokeTestGenerator,
        FileWriter $writer,
    ): int {
        $tableName = (string) $this->argument('table');
        $connection = $this->option('connection');

        try {
            $schema = $reader->read($tableName, $connection ? (string) $connection : null);
        } catch (Throwable $e) {
            $this->error("Failed to read table [{$tableName}]: ".$e->getMessage());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $json = $dryRun && (bool) $this->option('json');
        $force = (bool) $this->option('force');
        $all = (bool) $this->option('all');
        $shouldRoutes = (bool) $this->option('routes');
        $shouldApiRoutes = (bool) $this->option('api-routes');
        $shouldTests = (bool) $this->option('tests');
        if ((bool) $this->option('relationships')) {
            config()->set('api-from-table.generate.relationships', true);
        }

        $dryRunReport = $json ? $this->newDryRunReport($schema) : null;
        $selected = [
            'model' => (bool) $this->option('model'),
            'requests' => (bool) $this->option('requests'),
            'dto' => (bool) $this->option('dto'),
            'actions' => (bool) $this->option('actions'),
            'resource' => (bool) $this->option('resource'),
            'controller' => (bool) $this->option('controller'),
        ];
        $hasSelection = $all || in_array(true, $selected, true);

        $shouldModel = $this->shouldGenerate('model', $selected, $hasSelection, $all, 'model');
        $shouldStore = $this->shouldGenerate('requests', $selected, $hasSelection, $all, 'store_request');
        $shouldUpdate = $this->shouldGenerate('requests', $selected, $hasSelection, $all, 'update_request');
        $shouldDto = $this->shouldGenerate('dto', $selected, $hasSelection, $all, 'dto');
        $shouldActions = $this->shouldGenerate('actions', $selected, $hasSelection, $all, 'actions');
        $shouldResource = $this->shouldGenerate('resource', $selected, $hasSelection, $all, 'resource');
        $shouldController = $this->shouldGenerate('controller', $selected, $hasSelection, $all, 'controller');

        $modelsPath = (string) config('api-from-table.paths.models', app_path('Models'));
        $requestsPath = (string) config('api-from-table.paths.requests', app_path('Http/Requests'));
        $dataPath = (string) config('api-from-table.paths.data', app_path('Data'));
        $actionsPath = (string) config('api-from-table.paths.actions', app_path('Actions'));
        $resourcesPath = (string) config('api-from-table.paths.resources', app_path('Http/Resources'));
        $controllersPath = (string) config('api-from-table.paths.controllers', app_path('Http/Controllers'));
        $routesPath = (string) config('api-from-table.paths.routes', base_path('routes/web.php'));
        $apiRoutesPath = (string) config('api-from-table.paths.api_routes', base_path('routes/api.php'));
        $testsPath = (string) config('api-from-table.paths.tests', base_path('tests/Feature'));

        if ($shouldModel) {
            $modelName = $modelGenerator->modelName($schema);
            $this->emitOrWrite(
                'model',
                $modelsPath.DIRECTORY_SEPARATOR.$modelName.'.php',
                $modelGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
                $dryRunReport,
                $json,
            );
        }

        if ($shouldStore) {
            $name = $storeGenerator->className($schema);
            $this->emitOrWrite(
                'store_request',
                $requestsPath.DIRECTORY_SEPARATOR.$name.'.php',
                $storeGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
                $dryRunReport,
                $json,
            );
        }

        if ($shouldUpdate) {
            $name = $updateGenerator->className($schema);
            $this->emitOrWrite(
                'update_request',
                $requestsPath.DIRECTORY_SEPARATOR.$name.'.php',
                $updateGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
                $dryRunReport,
                $json,
            );
        }

        if ($shouldDto) {
            $name = $storeDataGenerator->className($schema);
            $this->emitOrWrite(
                'store_data',
                $dataPath.DIRECTORY_SEPARATOR.$name.'.php',
                $storeDataGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
                $dryRunReport,
                $json,
            );

            $name = $updateDataGenerator->className($schema);
            $this->emitOrWrite(
                'update_data',
                $dataPath.DIRECTORY_SEPARATOR.$name.'.php',
                $updateDataGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
                $dryRunReport,
                $json,
            );
        }

        if ($shouldActions) {
            $directory = $storeActionGenerator->directoryName($schema);

            $name = $storeActionGenerator->className($schema);
            $this->emitOrWrite(
                'store_action',
                $actionsPath.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$name.'.php',
                $storeActionGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
                $dryRunReport,
                $json,
            );

            $name = $updateActionGenerator->className($schema);
            $this->emitOrWrite(
                'update_action',
                $actionsPath.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$name.'.php',
                $updateActionGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
                $dryRunReport,
                $json,
            );
        }

        if ($shouldResource) {
            $name = $resourceGenerator->className($schema);
            $this->emitOrWrite(
                'resource',
                $resourcesPath.DIRECTORY_SEPARATOR.$name.'.php',
                $resourceGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
                $dryRunReport,
                $json,
            );
        }

        if ($shouldController) {
            $name = $controllerGenerator->className($schema);
            $this->emitOrWrite(
                'controller',
                $controllersPath.DIRECTORY_SEPARATOR.$name.'.php',
                $controllerGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
                $dryRunReport,
                $json,
            );
        }

        if ($shouldRoutes) {
            $this->emitOrAppendRoute(
                'routes',
                $routesPath,
                $routeGenerator->generate($schema),
                $dryRun,
                $writer,
                $dryRunReport,
                $json,
            );
        }

        if ($shouldApiRoutes) {
            $this->emitOrAppendRoute(
                'api_routes',
                $apiRoutesPath,
                $routeGenerator->generate($schema),
                $dryRun,
                $writer,
                $dryRunReport,
                $json,
            );
        }

        if ($shouldTests) {
            $name = $smokeTestGenerator->className($schema);
            $this->emitOrWrite(
                'smoke_test',
                $testsPath.DIRECTORY_SEPARATOR.$name.'.php',
                $smokeTestGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
                $dryRunReport,
                $json,
                [
                    'class' => $name,
                ],
            );
        }

        if ($json && $dryRunReport !== null) {
            $this->line(json_encode($dryRunReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, bool>  $selected
     */
    protected function shouldGenerate(
        string $selectionKey,
        array $selected,
        bool $hasSelection,
        bool $all,
        string $configKey,
    ): bool {
        if ($all) {
            return true;
        }

        if ($hasSelection) {
            return $selected[$selectionKey] ?? false;
        }

        return (bool) config("api-from-table.generate.{$configKey}", true);
    }

    /**
     * @param  array<string, mixed>|null  $dryRunReport
     * @param  array<string, string>  $testTarget
     */
    protected function emitOrWrite(
        string $type,
        string $path,
        string $code,
        bool $dryRun,
        bool $force,
        FileWriter $writer,
        ?array &$dryRunReport = null,
        bool $json = false,
        array $testTarget = [],
    ): void {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if ($dryRun) {
            $exists = $writer->exists($path);
            $this->recordDryRunFile($dryRunReport, $type, $path, $exists, $force, $testTarget);

            if (! $json) {
                $this->line('--- '.$path.' ---');
                $this->line($code);
            }

            return;
        }

        if ($writer->write($path, $code, $force)) {
            $this->info('Created: '.$path);
        } else {
            $this->warn('Skipped (exists): '.$path.'. Use --force to overwrite.');
        }
    }

    /**
     * @param  array<string, mixed>|null  $dryRunReport
     */
    protected function emitOrAppendRoute(
        string $type,
        string $path,
        string $route,
        bool $dryRun,
        FileWriter $writer,
        ?array &$dryRunReport = null,
        bool $json = false,
    ): void {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if ($dryRun) {
            $exists = $writer->routeExists($path, $route);
            $this->recordDryRunRoute($dryRunReport, $type, $path, $route, $exists);

            if (! $json) {
                $this->line('--- '.$path.' ---');
                $this->line('use Illuminate\Support\Facades\Route;');
                $this->line('');
                $this->line($route);
            }

            return;
        }

        if ($writer->appendRoute($path, $route)) {
            $this->info('Updated: '.$path);
        } else {
            $this->warn('Skipped (route exists): '.$path.'.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function newDryRunReport(TableSchema $schema): array
    {
        return [
            'table' => $schema->name,
            'dry_run' => true,
            'planned_files' => [],
            'skipped_files' => [],
            'route_targets' => [],
            'test_targets' => [],
            'warnings' => [],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $report
     * @param  array<string, string>  $testTarget
     */
    protected function recordDryRunFile(
        ?array &$report,
        string $type,
        string $path,
        bool $exists,
        bool $force,
        array $testTarget = [],
    ): void {
        if ($report === null) {
            return;
        }

        $status = $exists && ! $force ? 'skipped' : 'planned';
        $entry = [
            'type' => $type,
            'path' => $path,
            'status' => $status,
        ];

        if ($status === 'planned') {
            $report['planned_files'][] = $entry;
        } else {
            $report['skipped_files'][] = [
                'type' => $type,
                'path' => $path,
                'reason' => 'exists',
            ];
            $report['warnings'][] = "Skipped existing file: {$path}. Use --force to overwrite.";
        }

        if ($type === 'smoke_test') {
            $report['test_targets'][] = [
                'type' => 'smoke_test',
                'path' => $path,
                'class' => $testTarget['class'] ?? '',
                'status' => $status,
            ];
        }
    }

    /**
     * @param  array<string, mixed>|null  $report
     */
    protected function recordDryRunRoute(
        ?array &$report,
        string $type,
        string $path,
        string $route,
        bool $exists,
    ): void {
        if ($report === null) {
            return;
        }

        $status = $exists ? 'skipped' : 'planned';

        $report['route_targets'][] = [
            'type' => $type,
            'path' => $path,
            'route' => $route,
            'status' => $status,
        ];

        if ($exists) {
            $report['warnings'][] = "Skipped existing route in {$path}.";
        }
    }
}
