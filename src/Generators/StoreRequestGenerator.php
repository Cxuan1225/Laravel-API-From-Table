<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Inferrers\ValidationRuleInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\StubRenderer;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class StoreRequestGenerator
{
    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
        protected ValidationRuleInferrer $validationInferrer,
        protected StubRenderer $renderer,
        protected Filesystem $files,
    ) {}

    public function generate(TableSchema $schema): string
    {
        $modelName = $this->modelNameInferrer->infer($schema->name);
        $rules = $this->validationInferrer->infer($schema, forUpdate: false);

        return $this->renderer->render($this->loadStub(), [
            'namespace' => (string) config('api-from-table.namespace.requests', 'App\\Http\\Requests'),
            'class' => 'Store'.$modelName.'Request',
            'rules' => $this->buildRules($rules),
        ]);
    }

    public function className(TableSchema $schema): string
    {
        return 'Store'.$this->modelNameInferrer->infer($schema->name).'Request';
    }

    protected function loadStub(): string
    {
        $custom = base_path('stubs/vendor/api-from-table/request.store.stub');
        if ($this->files->exists($custom)) {
            return $this->files->get($custom);
        }

        $package = __DIR__.'/../../stubs/request.store.stub';
        if (! $this->files->exists($package)) {
            throw new RuntimeException("Store request stub not found at [{$package}].");
        }

        return $this->files->get($package);
    }

    /**
     * @param  array<string, list<string>>  $rules
     */
    protected function buildRules(array $rules): string
    {
        $lines = [];
        foreach ($rules as $name => $columnRules) {
            $quoted = array_map(fn (string $r): string => "'{$r}'", $columnRules);
            $lines[] = "            '{$name}' => [".implode(', ', $quoted).'],';
        }

        return implode("\n", $lines);
    }
}
