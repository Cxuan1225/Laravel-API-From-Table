<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Inferrers;

use Cxuan1225\LaravelApiFromTable\Schema\ColumnSchema;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Illuminate\Support\Str;

class ValidationRuleInferrer
{
    public function __construct(
        protected ?FieldExposureResolver $fieldExposureResolver = null,
    ) {
        $this->fieldExposureResolver ??= new FieldExposureResolver();
    }

    /**
     * @return array<string, list<string>>
     */
    public function infer(TableSchema $schema, bool $forUpdate = false): array
    {
        $tinyintAsBoolean = (bool) config('api-from-table.tinyint_one_as_boolean', true);
        $allowedFields = $this->fieldExposureResolver->requestRules($schema);

        $rules = [];
        foreach ($schema->columns as $column) {
            if (! in_array($column->name, $allowedFields, true)) {
                continue;
            }

            $rules[$column->name] = $this->rulesFor($schema, $column, $forUpdate, $tinyintAsBoolean);
        }

        return $rules;
    }

    /**
     * @return list<string>
     */
    protected function rulesFor(TableSchema $schema, ColumnSchema $column, bool $forUpdate, bool $tinyintAsBoolean): array
    {
        $isBoolean = ($tinyintAsBoolean && $column->isTinyIntBoolean())
            || in_array($column->type, ['boolean', 'bool'], true);

        if ($isBoolean) {
            $rules = $this->presenceRules($column, $forUpdate, isBoolean: true);
            $rules[] = 'boolean';

            return $rules;
        }

        $rules = $this->presenceRules($column, $forUpdate);

        $typeRules = $this->typeRules($column);
        foreach ($typeRules as $rule) {
            $rules[] = $rule;
        }

        foreach ($this->metadataRules($schema, $column, $forUpdate) as $rule) {
            $rules[] = $rule;
        }

        return array_values(array_unique($rules));
    }

    /**
     * @return list<string>
     */
    protected function presenceRules(ColumnSchema $column, bool $forUpdate, bool $isBoolean = false): array
    {
        if ($forUpdate) {
            return $column->nullable
                ? ['sometimes', 'nullable']
                : ['sometimes'];
        }

        if ($column->nullable) {
            return ['nullable'];
        }

        if ($column->hasDefault()) {
            return $isBoolean ? [] : ['nullable'];
        }

        return ['required'];
    }

    /**
     * @return list<string>
     */
    protected function typeRules(ColumnSchema $column): array
    {
        switch ($column->type) {
            case 'varchar':
            case 'char':
            case 'string':
                $rules = ['string'];
                if ($this->isEmailColumn($column)) {
                    $rules[] = 'email';
                }
                if ($this->isUuidColumn($column)) {
                    $rules[] = 'uuid';
                }
                if ($column->length !== null) {
                    $rules[] = 'max:'.$column->length;
                }

                return $rules;

            case 'text':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
                return ['string'];

            case 'int':
            case 'integer':
            case 'bigint':
            case 'smallint':
            case 'mediumint':
            case 'tinyint':
                return ['integer'];

            case 'decimal':
            case 'numeric':
                return $column->scale === 0 ? ['integer'] : ['numeric'];

            case 'float':
            case 'double':
            case 'real':
                return ['numeric'];

            case 'date':
            case 'datetime':
            case 'timestamp':
                return ['date'];

            case 'json':
            case 'jsonb':
                return ['array'];

            case 'uuid':
                return ['string', 'uuid'];

            default:
                return [];
        }
    }

    /**
     * @return list<string>
     */
    protected function metadataRules(TableSchema $schema, ColumnSchema $column, bool $forUpdate): array
    {
        $rules = [];

        $foreignKey = $schema->foreignKeyForColumn($column->name);
        if ($foreignKey !== null) {
            $rules[] = 'exists:'.$foreignKey->foreignTable.','.$foreignKey->foreignColumns[0];
        }

        $enum = $schema->enumForColumn($column->name);
        if ($enum !== null && $enum->values !== []) {
            $rules[] = $this->ruleIn($enum->values);
        }

        $check = $schema->checkForColumn($column->name);
        if ($check !== null && $check->values !== []) {
            $rules[] = $this->ruleIn($check->values);
        }

        $unique = $schema->uniqueIndexForColumn($column->name);
        if ($unique !== null) {
            $rules[] = $forUpdate
                ? "Rule::unique('{$schema->name}', '{$column->name}')->ignore(\$this->route('".$this->routeParameter($schema)."'))"
                : "Rule::unique('{$schema->name}', '{$column->name}')";
        }

        return $rules;
    }

    /**
     * @param  list<string>  $values
     */
    protected function ruleIn(array $values): string
    {
        $quoted = array_map(
            fn (string $value): string => "'".str_replace("'", "\\'", $value)."'",
            $values,
        );

        return 'Rule::in(['.implode(', ', $quoted).'])';
    }

    protected function isEmailColumn(ColumnSchema $column): bool
    {
        return $column->name === 'email' || str_ends_with($column->name, '_email');
    }

    protected function isUuidColumn(ColumnSchema $column): bool
    {
        return $column->type === 'uuid'
            || $column->name === 'uuid'
            || str_ends_with($column->name, '_uuid');
    }

    protected function routeParameter(TableSchema $schema): string
    {
        return Str::snake(Str::singular(str_replace('-', '_', $schema->name)));
    }
}
