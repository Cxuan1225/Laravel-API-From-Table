<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Console;

use Cxuan1225\LaravelApiFromTable\Generators\ControllerGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\ModelGenerator;
use Cxuan1225\LaravelApiFromTable\Generators\ResourceGenerator;
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
                            {--force : Overwrite existing files}
                            {--model : Generate only the model}
                            {--requests : Generate only the form requests}
                            {--dto : Generate only the DTO files}
                            {--actions : Generate only the action files}
                            {--resource : Generate only the API resource}
                            {--controller : Generate only the API controller}
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
        $force = (bool) $this->option('force');
        $all = (bool) $this->option('all');
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

        if ($shouldModel) {
            $modelName = $modelGenerator->modelName($schema);
            $this->emitOrWrite(
                $modelsPath.DIRECTORY_SEPARATOR.$modelName.'.php',
                $modelGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
            );
        }

        if ($shouldStore) {
            $name = $storeGenerator->className($schema);
            $this->emitOrWrite(
                $requestsPath.DIRECTORY_SEPARATOR.$name.'.php',
                $storeGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
            );
        }

        if ($shouldUpdate) {
            $name = $updateGenerator->className($schema);
            $this->emitOrWrite(
                $requestsPath.DIRECTORY_SEPARATOR.$name.'.php',
                $updateGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
            );
        }

        if ($shouldDto) {
            $name = $storeDataGenerator->className($schema);
            $this->emitOrWrite(
                $dataPath.DIRECTORY_SEPARATOR.$name.'.php',
                $storeDataGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
            );

            $name = $updateDataGenerator->className($schema);
            $this->emitOrWrite(
                $dataPath.DIRECTORY_SEPARATOR.$name.'.php',
                $updateDataGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
            );
        }

        if ($shouldActions) {
            $directory = $storeActionGenerator->directoryName($schema);

            $name = $storeActionGenerator->className($schema);
            $this->emitOrWrite(
                $actionsPath.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$name.'.php',
                $storeActionGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
            );

            $name = $updateActionGenerator->className($schema);
            $this->emitOrWrite(
                $actionsPath.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$name.'.php',
                $updateActionGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
            );
        }

        if ($shouldResource) {
            $name = $resourceGenerator->className($schema);
            $this->emitOrWrite(
                $resourcesPath.DIRECTORY_SEPARATOR.$name.'.php',
                $resourceGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
            );
        }

        if ($shouldController) {
            $name = $controllerGenerator->className($schema);
            $this->emitOrWrite(
                $controllersPath.DIRECTORY_SEPARATOR.$name.'.php',
                $controllerGenerator->generate($schema),
                $dryRun,
                $force,
                $writer,
            );
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

    protected function emitOrWrite(
        string $path,
        string $code,
        bool $dryRun,
        bool $force,
        FileWriter $writer,
    ): void {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if ($dryRun) {
            $this->line('--- '.$path.' ---');
            $this->line($code);

            return;
        }

        if ($writer->write($path, $code, $force)) {
            $this->info('Created: '.$path);
        } else {
            $this->warn('Skipped (exists): '.$path.'. Use --force to overwrite.');
        }
    }
}
