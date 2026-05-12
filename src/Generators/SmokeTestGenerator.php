<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\FieldExposureResolver;
use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Inferrers\ValidationRuleInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\ColumnSchema;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\RouteResolver;
use Cxuan1225\LaravelApiFromTable\Support\StubRenderer;
use Illuminate\Support\Str;

class SmokeTestGenerator
{
    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
        protected FieldExposureResolver $fieldExposureResolver,
        protected ValidationRuleInferrer $validationRuleInferrer,
        protected RouteResolver $routeResolver,
        protected StubRenderer $renderer,
    ) {}

    public function generate(TableSchema $schema, string $routeTarget = RouteResolver::TARGET_ROUTES): string
    {
        $modelName = $this->modelNameInferrer->infer($schema->name);
        $modelNamespace = (string) config('api-from-table.namespace.models', 'App\\Models');
        $variable = Str::camel($modelName);
        $uri = $this->routeResolver->uri($schema, $routeTarget);

        return $this->renderer->render($this->stub(), [
            'imports' => $this->buildImports($schema),
            'model_namespace' => $modelNamespace,
            'model_class' => $modelName,
            'model_variable' => $variable,
            'uri' => $uri,
            'create_payload' => $this->buildPayload($schema, update: false),
            'update_payload' => $this->buildPayload($schema, update: true),
            'unique_update_payload' => $this->buildUniqueUpdatePayload($schema, $variable),
            'sensitive_assertions' => $this->buildSensitiveAssertions($schema),
            'invalid_payload' => $this->buildInvalidPayload($schema),
            'validation_error_fields' => $this->buildValidationErrorFields($schema),
            'sensitive_test' => $this->sensitiveTest($schema, $variable, $routeTarget),
            'password_test' => $this->passwordTest($schema, $variable),
            'unique_update_test' => $this->uniqueUpdateTest($schema, $variable, $routeTarget),
            'validation_test' => $this->validationTest($schema, $variable, $routeTarget),
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

    protected function buildImports(TableSchema $schema): string
    {
        if (! $this->hasPassword($schema)) {
            return '';
        }

        return 'use Illuminate\Support\Facades\Hash;';
    }

    protected function buildUniqueUpdatePayload(TableSchema $schema, string $modelVariable): string
    {
        $uniqueFields = $this->uniqueWritableFields($schema);
        $fields = $this->fieldExposureResolver->requestRules($schema);
        $lines = [];

        foreach ($fields as $field) {
            $column = $schema->column($field);
            if ($column === null) {
                continue;
            }

            $value = in_array($field, $uniqueFields, true)
                ? "\${$modelVariable}->{$field}"
                : $this->sampleValue($column, update: true);

            $lines[] = "        '{$field}' => {$value},";
        }

        return implode("\n", $lines);
    }

    protected function buildSensitiveAssertions(TableSchema $schema): string
    {
        $lines = [];

        foreach ($this->fieldExposureResolver->modelHidden($schema) as $field) {
            $lines[] = "    \$response->assertJsonMissingPath('{$field}');";
            $lines[] = "    \$response->assertJsonMissingPath('data.{$field}');";
        }

        return implode("\n", $lines);
    }

    protected function buildInvalidPayload(TableSchema $schema): string
    {
        $rules = $this->validationRuleInferrer->infer($schema);
        $invalid = $this->invalidFields($schema, $rules);
        $lines = [];

        foreach ($invalid as $field => $value) {
            $lines[] = "        '{$field}' => {$value},";
        }

        return implode("\n", $lines);
    }

    protected function buildValidationErrorFields(TableSchema $schema): string
    {
        $rules = $this->validationRuleInferrer->infer($schema);
        $fields = array_keys($this->invalidFields($schema, $rules));

        return '['.implode(', ', array_map(
            fn (string $field): string => "'{$field}'",
            $fields,
        )).']';
    }

    protected function sampleValue(ColumnSchema $column, bool $update): string
    {
        $prefix = $update ? 'Updated' : 'Test';

        if ($column->name === 'email' || str_ends_with($column->name, '_email')) {
            return $update
                ? "'updated-'.uniqid().'@example.com'"
                : "'test-'.uniqid().'@example.com'";
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

    protected function sensitiveTest(TableSchema $schema, string $modelVariable, string $routeTarget): string
    {
        if ($this->fieldExposureResolver->modelHidden($schema) === []) {
            return '';
        }

        return $this->renderer->render(<<<'PHP'

it('does not expose sensitive {{ model_variable }} fields', function () {
    ${{ model_variable }} = {{ model_class }}::query()->create([
{{ create_payload }}
    ]);

    $response = $this->getJson('{{ uri }}/'.${{ model_variable }}->getKey())->assertOk();

{{ sensitive_assertions }}
});
PHP, [
            'model_class' => $this->modelNameInferrer->infer($schema->name),
            'model_variable' => $modelVariable,
            'uri' => $this->routeResolver->uri($schema, $routeTarget),
            'create_payload' => $this->buildPayload($schema, update: false),
            'sensitive_assertions' => $this->buildSensitiveAssertions($schema),
        ]);
    }

    protected function passwordTest(TableSchema $schema, string $modelVariable): string
    {
        if (! $this->hasPassword($schema)) {
            return '';
        }

        return $this->renderer->render(<<<'PHP'

it('hashes {{ model_variable }} passwords', function () {
    $password = 'secret-password';

    ${{ model_variable }} = {{ model_class }}::query()->create([
{{ password_payload }}
    ]);

    expect(${{ model_variable }}->password)->not->toBe($password);
    expect(Hash::check($password, ${{ model_variable }}->password))->toBeTrue();
});
PHP, [
            'model_class' => $this->modelNameInferrer->infer($schema->name),
            'model_variable' => $modelVariable,
            'password_payload' => $this->buildPasswordPayload($schema),
        ]);
    }

    protected function uniqueUpdateTest(TableSchema $schema, string $modelVariable, string $routeTarget): string
    {
        if ($this->uniqueWritableFields($schema) === []) {
            return '';
        }

        return $this->renderer->render(<<<'PHP'

it('updates a {{ model_variable }} while keeping unique values unchanged', function () {
    ${{ model_variable }} = {{ model_class }}::query()->create([
{{ create_payload }}
    ]);

    $this->putJson('{{ uri }}/'.${{ model_variable }}->getKey(), [
{{ unique_update_payload }}
    ])->assertSuccessful();
});
PHP, [
            'model_class' => $this->modelNameInferrer->infer($schema->name),
            'model_variable' => $modelVariable,
            'uri' => $this->routeResolver->uri($schema, $routeTarget),
            'create_payload' => $this->buildPayload($schema, update: false),
            'unique_update_payload' => $this->buildUniqueUpdatePayload($schema, $modelVariable),
        ]);
    }

    protected function validationTest(TableSchema $schema, string $modelVariable, string $routeTarget): string
    {
        if ($this->buildInvalidPayload($schema) === '') {
            return '';
        }

        return $this->renderer->render(<<<'PHP'

it('rejects invalid {{ model_variable }} payload', function () {
    $this->postJson('{{ uri }}', array_replace([
{{ create_payload }}
    ], [
{{ invalid_payload }}
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors({{ validation_error_fields }});
});
PHP, [
            'model_variable' => $modelVariable,
            'uri' => $this->routeResolver->uri($schema, $routeTarget),
            'create_payload' => $this->buildPayload($schema, update: false),
            'invalid_payload' => $this->buildInvalidPayload($schema),
            'validation_error_fields' => $this->buildValidationErrorFields($schema),
        ]);
    }

    protected function buildPasswordPayload(TableSchema $schema): string
    {
        $fields = $this->fieldExposureResolver->requestRules($schema);
        $lines = [];

        foreach ($fields as $field) {
            $column = $schema->column($field);
            if ($column === null) {
                continue;
            }

            $value = $field === 'password'
                ? '$password'
                : $this->sampleValue($column, update: false);

            $lines[] = "        '{$field}' => {$value},";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, list<string>>  $rules
     * @return array<string, string>
     */
    protected function invalidFields(TableSchema $schema, array $rules): array
    {
        $invalid = [];

        foreach ($rules as $field => $fieldRules) {
            if ($field === 'password') {
                continue;
            }

            $column = $schema->column($field);
            if ($column === null) {
                continue;
            }

            if (in_array('required', $fieldRules, true) && $this->isStringColumn($column)) {
                $invalid[$field] = 'null';
            }

            if (in_array('email', $fieldRules, true)) {
                $invalid[$field] = "'not-an-email'";
            }

            if (in_array('integer', $fieldRules, true)) {
                $invalid[$field] = "'not-an-integer'";
            } elseif (in_array('numeric', $fieldRules, true)) {
                $invalid[$field] = "'not-a-number'";
            } elseif (in_array('boolean', $fieldRules, true)) {
                $invalid[$field] = "'not-a-boolean'";
            } elseif (in_array('date', $fieldRules, true)) {
                $invalid[$field] = "'not-a-date'";
            } elseif (in_array('array', $fieldRules, true)) {
                $invalid[$field] = "'not-an-array'";
            } elseif (in_array('uuid', $fieldRules, true)) {
                $invalid[$field] = "'not-a-uuid'";
            }
        }

        return $invalid;
    }

    protected function isStringColumn(ColumnSchema $column): bool
    {
        return in_array($column->type, [
            'varchar',
            'char',
            'string',
            'text',
            'tinytext',
            'mediumtext',
            'longtext',
        ], true);
    }

    protected function hasPassword(TableSchema $schema): bool
    {
        return $schema->column('password') !== null;
    }

    /**
     * @return list<string>
     */
    protected function uniqueWritableFields(TableSchema $schema): array
    {
        $fields = $this->fieldExposureResolver->requestRules($schema);
        $uniqueFields = [];

        foreach ($fields as $field) {
            if ($schema->uniqueIndexForColumn($field) !== null) {
                $uniqueFields[] = $field;
            }
        }

        return $uniqueFields;
    }

    protected function stub(): string
    {
        return <<<'PHP'
<?php

use {{ model_namespace }}\{{ model_class }};
{{ imports }}

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
{{ sensitive_test }}
{{ password_test }}
{{ unique_update_test }}
{{ validation_test }}
PHP;
    }
}
