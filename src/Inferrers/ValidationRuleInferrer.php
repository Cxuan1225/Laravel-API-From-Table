<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Inferrers;

use Cxuan1225\LaravelApiFromTable\Schema\ColumnSchema;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;

class ValidationRuleInferrer
{
    /**
     * @return array<string, list<string>>
     */
    public function infer(TableSchema $schema, bool $forUpdate = false): array
    {
        $excluded = (array) config('api-from-table.exclude_fillable', [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $tinyintAsBoolean = (bool) config('api-from-table.tinyint_one_as_boolean', true);

        $rules = [];
        foreach ($schema->columns as $column) {
            if ($column->autoIncrement) {
                continue;
            }
            if (in_array($column->name, $excluded, true)) {
                continue;
            }
            if ($this->isSensitive($column->name)) {
                continue;
            }

            $rules[$column->name] = $this->rulesFor($column, $forUpdate, $tinyintAsBoolean);
        }

        return $rules;
    }

    protected function isSensitive(string $name): bool
    {
        $exact = (array) config('api-from-table.sensitive_columns', []);
        if (in_array($name, $exact, true)) {
            return true;
        }

        $patterns = (array) config('api-from-table.sensitive_patterns', []);
        $lower = strtolower($name);
        foreach ($patterns as $pattern) {
            $pattern = (string) $pattern;
            if ($pattern === '') {
                continue;
            }
            if (str_contains($lower, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function rulesFor(ColumnSchema $column, bool $forUpdate, bool $tinyintAsBoolean): array
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

        return $rules;
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
}
