<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Inferrers\ValidationRuleInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\StubRenderer;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class UpdateRequestGenerator
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
        $rules = $this->validationInferrer->infer($schema, forUpdate: true);

        return $this->renderer->render($this->loadStub(), [
            'namespace' => (string) config('api-from-table.namespace.requests', 'App\\Http\\Requests'),
            'class' => 'Update'.$modelName.'Request',
            'imports' => $this->buildImports($rules),
            'rules' => $this->buildRules($rules),
        ]);
    }

    public function className(TableSchema $schema): string
    {
        return 'Update'.$this->modelNameInferrer->infer($schema->name).'Request';
    }

    protected function loadStub(): string
    {
        $custom = base_path('stubs/vendor/api-from-table/request.update.stub');
        if ($this->files->exists($custom)) {
            return $this->files->get($custom);
        }

        $package = __DIR__.'/../../stubs/request.update.stub';
        if (! $this->files->exists($package)) {
            throw new RuntimeException("Update request stub not found at [{$package}].");
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
            $quoted = array_map(fn (string $r): string => $this->renderRule($r), $columnRules);
            $lines[] = "            '{$name}' => [".implode(', ', $quoted).'],';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, list<string>>  $rules
     */
    protected function buildImports(array $rules): string
    {
        foreach ($rules as $columnRules) {
            foreach ($columnRules as $rule) {
                if (str_starts_with($rule, 'Rule::')) {
                    return 'use Illuminate\Validation\Rule;';
                }
            }
        }

        return '';
    }

    protected function renderRule(string $rule): string
    {
        if (str_starts_with($rule, 'Rule::')) {
            return $rule;
        }

        return "'{$rule}'";
    }
}
