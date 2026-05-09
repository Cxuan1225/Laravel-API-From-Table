<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\StubRenderer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class ControllerGenerator
{
    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
        protected StoreRequestGenerator $storeRequestGenerator,
        protected UpdateRequestGenerator $updateRequestGenerator,
        protected StoreDataGenerator $storeDataGenerator,
        protected UpdateDataGenerator $updateDataGenerator,
        protected StoreActionGenerator $storeActionGenerator,
        protected UpdateActionGenerator $updateActionGenerator,
        protected ResourceGenerator $resourceGenerator,
        protected StubRenderer $renderer,
        protected Filesystem $files,
    ) {}

    public function generate(TableSchema $schema): string
    {
        $modelName = $this->modelName($schema);

        return $this->renderer->render($this->loadStub(), [
            'namespace' => (string) config('api-from-table.namespace.controllers', 'App\\Http\\Controllers'),
            'class' => $this->className($schema),
            'action_namespace' => $this->storeActionGenerator->namespace($schema),
            'store_action_class' => $this->storeActionGenerator->className($schema),
            'update_action_class' => $this->updateActionGenerator->className($schema),
            'store_action_variable' => Str::camel($this->storeActionGenerator->className($schema)),
            'update_action_variable' => Str::camel($this->updateActionGenerator->className($schema)),
            'data_namespace' => (string) config('api-from-table.namespace.data', 'App\\Data'),
            'store_data_class' => $this->storeDataGenerator->className($schema),
            'update_data_class' => $this->updateDataGenerator->className($schema),
            'model_namespace' => (string) config('api-from-table.namespace.models', 'App\\Models'),
            'model_class' => $modelName,
            'model_variable' => Str::camel($modelName),
            'request_namespace' => (string) config('api-from-table.namespace.requests', 'App\\Http\\Requests'),
            'store_request_class' => $this->storeRequestGenerator->className($schema),
            'update_request_class' => $this->updateRequestGenerator->className($schema),
            'resource_namespace' => (string) config('api-from-table.namespace.resources', 'App\\Http\\Resources'),
            'resource_class' => $this->resourceGenerator->className($schema),
        ]);
    }

    public function className(TableSchema $schema): string
    {
        return $this->modelName($schema).'Controller';
    }

    protected function modelName(TableSchema $schema): string
    {
        return $this->modelNameInferrer->infer($schema->name);
    }

    protected function loadStub(): string
    {
        $custom = base_path('stubs/vendor/api-from-table/controller.api.stub');
        if ($this->files->exists($custom)) {
            return $this->files->get($custom);
        }

        $package = __DIR__.'/../../stubs/controller.api.stub';
        if (! $this->files->exists($package)) {
            throw new RuntimeException("API controller stub not found at [{$package}].");
        }

        return $this->files->get($package);
    }
}
