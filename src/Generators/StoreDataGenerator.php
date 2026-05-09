<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\FillableInferrer;
use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\StubRenderer;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class StoreDataGenerator
{
    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
        protected FillableInferrer $fillableInferrer,
        protected StubRenderer $renderer,
        protected Filesystem $files,
    ) {}

    public function generate(TableSchema $schema): string
    {
        return $this->renderer->render($this->loadStub(), [
            'namespace' => (string) config('api-from-table.namespace.data', 'App\\Data'),
            'class' => $this->className($schema),
            'allowed_fields' => $this->buildAllowedFields($this->fillableInferrer->infer($schema)),
        ]);
    }

    public function className(TableSchema $schema): string
    {
        return 'Store'.$this->modelNameInferrer->infer($schema->name).'Data';
    }

    protected function loadStub(): string
    {
        $custom = base_path('stubs/vendor/api-from-table/data.store.stub');
        if ($this->files->exists($custom)) {
            return $this->files->get($custom);
        }

        $package = __DIR__.'/../../stubs/data.store.stub';
        if (! $this->files->exists($package)) {
            throw new RuntimeException("Store data stub not found at [{$package}].");
        }

        return $this->files->get($package);
    }

    /**
     * @param  list<string>  $fields
     */
    protected function buildAllowedFields(array $fields): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $lines[] = "            '{$field}',";
        }

        return implode("\n", $lines);
    }
}
