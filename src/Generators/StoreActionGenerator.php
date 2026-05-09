<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\StubRenderer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class StoreActionGenerator
{
    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
        protected StoreDataGenerator $storeDataGenerator,
        protected StubRenderer $renderer,
        protected Filesystem $files,
    ) {}

    public function generate(TableSchema $schema): string
    {
        $modelName = $this->modelName($schema);

        return $this->renderer->render($this->loadStub(), [
            'namespace' => $this->namespace($schema),
            'class' => $this->className($schema),
            'data_namespace' => (string) config('api-from-table.namespace.data', 'App\\Data'),
            'data_class' => $this->storeDataGenerator->className($schema),
            'model_namespace' => (string) config('api-from-table.namespace.models', 'App\\Models'),
            'model_class' => $modelName,
        ]);
    }

    public function className(TableSchema $schema): string
    {
        return 'Store'.$this->modelName($schema).'Action';
    }

    public function directoryName(TableSchema $schema): string
    {
        return Str::pluralStudly($this->modelName($schema));
    }

    public function namespace(TableSchema $schema): string
    {
        return (string) config('api-from-table.namespace.actions', 'App\\Actions')
            .'\\'.$this->directoryName($schema);
    }

    protected function modelName(TableSchema $schema): string
    {
        return $this->modelNameInferrer->infer($schema->name);
    }

    protected function loadStub(): string
    {
        $custom = base_path('stubs/vendor/api-from-table/action.store.stub');
        if ($this->files->exists($custom)) {
            return $this->files->get($custom);
        }

        $package = __DIR__.'/../../stubs/action.store.stub';
        if (! $this->files->exists($package)) {
            throw new RuntimeException("Store action stub not found at [{$package}].");
        }

        return $this->files->get($package);
    }
}
