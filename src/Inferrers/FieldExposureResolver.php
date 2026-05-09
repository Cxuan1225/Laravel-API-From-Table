<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Inferrers;

use Cxuan1225\LaravelApiFromTable\Schema\ColumnSchema;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;

class FieldExposureResolver
{
    /**
     * @return list<string>
     */
    public function fillable(TableSchema $schema): array
    {
        return $this->filterColumns(
            $schema,
            fn (ColumnSchema $column): bool => $this->isWritable($column, 'fillable'),
        );
    }

    /**
     * @return list<string>
     */
    public function requestRules(TableSchema $schema): array
    {
        return $this->filterColumns(
            $schema,
            fn (ColumnSchema $column): bool => $this->isWritable($column, 'request_rules'),
        );
    }

    /**
     * @return list<string>
     */
    public function resourceVisible(TableSchema $schema): array
    {
        return $this->filterColumns($schema, function (ColumnSchema $column): bool {
            if ($column->primaryKey || $column->name === 'id') {
                return true;
            }

            if ($this->isBaseExcluded($column)) {
                return false;
            }

            if ($this->isSensitive($column->name)) {
                return false;
            }

            return true;
        });
    }

    /**
     * @return list<string>
     */
    public function modelHidden(TableSchema $schema): array
    {
        return $this->filterColumns(
            $schema,
            fn (ColumnSchema $column): bool => $this->isSensitive($column->name)
                || $this->isConfiguredForPolicy($column->name, 'model_hidden'),
        );
    }

    /**
     * @return list<string>
     */
    public function writeOnly(TableSchema $schema): array
    {
        return $this->filterColumns(
            $schema,
            fn (ColumnSchema $column): bool => $this->isWriteOnly($column->name),
        );
    }

    public function isSensitive(string $name): bool
    {
        if ($this->matchesExact($name, $this->sensitiveColumns())) {
            return true;
        }

        return $this->matchesPattern($name, $this->sensitivePatterns());
    }

    public function isWriteOnly(string $name): bool
    {
        return $this->matchesExact($name, $this->policyColumns('write_only'));
    }

    protected function isWritable(ColumnSchema $column, string $policy): bool
    {
        if ($column->autoIncrement || $this->isBaseExcluded($column)) {
            return false;
        }

        if ($this->isConfiguredForPolicy($column->name, $policy)) {
            return true;
        }

        if ($this->isSensitive($column->name) && ! $this->isWriteOnly($column->name)) {
            return false;
        }

        return true;
    }

    protected function isBaseExcluded(ColumnSchema $column): bool
    {
        return $this->matchesExact($column->name, $this->baseExcludedColumns());
    }

    /**
     * @return list<string>
     */
    protected function filterColumns(TableSchema $schema, callable $callback): array
    {
        $fields = [];
        foreach ($schema->columns as $column) {
            if ($callback($column)) {
                $fields[] = $column->name;
            }
        }

        return array_values(array_unique($fields));
    }

    protected function isConfiguredForPolicy(string $name, string $policy): bool
    {
        return $this->matchesExact($name, $this->policyColumns($policy));
    }

    /**
     * @return list<string>
     */
    protected function baseExcludedColumns(): array
    {
        return array_values(array_map('strval', (array) config('api-from-table.exclude_fillable', [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
        ])));
    }

    /**
     * @return list<string>
     */
    protected function sensitiveColumns(): array
    {
        return array_values(array_map('strval', (array) config('api-from-table.sensitive_columns', [
            'password',
            'remember_token',
            'api_token',
            'refresh_token',
            'access_token',
            'token',
            'otp',
            'otp_secret',
            'api_key',
            'client_secret',
            'private_key',
            'recovery_codes',
            'secret',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ])));
    }

    /**
     * @return list<string>
     */
    protected function sensitivePatterns(): array
    {
        return array_values(array_map('strval', (array) config('api-from-table.sensitive_patterns', [
            'password',
            'secret',
        ])));
    }

    /**
     * @return list<string>
     */
    protected function policyColumns(string $policy): array
    {
        $defaults = [
            'fillable' => [],
            'request_rules' => [],
            'resource_visible' => [],
            'model_hidden' => [],
            'write_only' => ['password'],
        ];

        return array_values(array_map(
            'strval',
            (array) config("api-from-table.field_policies.{$policy}", $defaults[$policy] ?? []),
        ));
    }

    /**
     * @param  list<string>  $columns
     */
    protected function matchesExact(string $name, array $columns): bool
    {
        foreach ($columns as $column) {
            if (strtolower($name) === strtolower($column)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $patterns
     */
    protected function matchesPattern(string $name, array $patterns): bool
    {
        $lower = strtolower($name);
        foreach ($patterns as $pattern) {
            $pattern = strtolower($pattern);
            if ($pattern !== '' && str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
