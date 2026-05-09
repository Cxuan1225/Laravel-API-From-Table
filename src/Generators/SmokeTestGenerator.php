<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\FieldExposureResolver;
use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\ColumnSchema;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\StubRenderer;
use Illuminate\Support\Str;

class SmokeTestGenerator
{
    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
        protected FieldExposureResolver $fieldExposureResolver,
        protected StubRenderer $renderer,
    ) {}

    public function generate(TableSchema $schema): string
    {
        $modelName = $this->modelNameInferrer->infer($schema->name);
        $modelNamespace = (string) config('api-from-table.namespace.models', 'App\\Models');
        $variable = Str::camel($modelName);
        $uri = '/'.$schema->name;

        return $this->renderer->render($this->stub(), [
            'model_namespace' => $modelNamespace,
            'model_class' => $modelName,
            'model_variable' => $variable,
            'uri' => $uri,
            'create_payload' => $this->buildPayload($schema, update: false),
            'update_payload' => $this->buildPayload($schema, update: true),
        ]);
    }

    public function className(TableSchema $schema): string
    {
        return $this->modelNameInferrer->infer($schema->name).'EndpointTest';
    }

    protected function buildPayload(TableSchema $schema, bool $update): string
    {
        $fields = $this->fieldExposureResolver->requestRules($schema);
        $lines = [];

        foreach ($fields as $field) {
            $column = $schema->column($field);
            if ($column === null) {
                continue;
            }

            $lines[] = "        '{$field}' => ".$this->sampleValue($column, $update).',';
        }

        return implode("\n", $lines);
    }

    protected function sampleValue(ColumnSchema $column, bool $update): string
    {
        $prefix = $update ? 'Updated' : 'Test';

        if ($column->name === 'email' || str_ends_with($column->name, '_email')) {
            return $update ? "'updated@example.com'" : "'test@example.com'";
        }

        if ($column->name === 'password') {
            return "'password'";
        }

        if ($column->type === 'uuid' || $column->name === 'uuid' || str_ends_with($column->name, '_uuid')) {
            return "'550e8400-e29b-41d4-a716-446655440000'";
        }

        if ($column->isTinyIntBoolean() || in_array($column->type, ['boolean', 'bool'], true)) {
            return $update ? 'false' : 'true';
        }

        return match ($column->type) {
            'int', 'integer', 'bigint', 'smallint', 'mediumint', 'tinyint' => '1',
            'decimal', 'numeric', 'float', 'double', 'real' => $update ? '99.50' : '10.50',
            'date' => $update ? "'2026-02-01'" : "'2026-01-01'",
            'datetime', 'timestamp' => $update ? "'2026-02-01 00:00:00'" : "'2026-01-01 00:00:00'",
            'json', 'jsonb' => "['key' => 'value']",
            default => "'{$prefix} ".Str::headline($column->name)."'",
        };
    }

    protected function stub(): string
    {
        return <<<'PHP'
<?php

use {{ model_namespace }}\{{ model_class }};

it('lists {{ model_variable }}s', function () {
    {{ model_class }}::query()->create([
{{ create_payload }}
    ]);

    $this->getJson('{{ uri }}')->assertOk();
});

it('creates a {{ model_variable }}', function () {
    $this->postJson('{{ uri }}', [
{{ create_payload }}
    ])->assertSuccessful();
});

it('shows a {{ model_variable }}', function () {
    ${{ model_variable }} = {{ model_class }}::query()->create([
{{ create_payload }}
    ]);

    $this->getJson('{{ uri }}/'.${{ model_variable }}->getKey())->assertOk();
});

it('updates a {{ model_variable }}', function () {
    ${{ model_variable }} = {{ model_class }}::query()->create([
{{ create_payload }}
    ]);

    $this->putJson('{{ uri }}/'.${{ model_variable }}->getKey(), [
{{ update_payload }}
    ])->assertSuccessful();
});

it('deletes a {{ model_variable }}', function () {
    ${{ model_variable }} = {{ model_class }}::query()->create([
{{ create_payload }}
    ]);

    $this->deleteJson('{{ uri }}/'.${{ model_variable }}->getKey())->assertNoContent();
});
PHP;
    }
}
