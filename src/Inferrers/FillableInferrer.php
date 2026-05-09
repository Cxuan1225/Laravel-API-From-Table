<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Inferrers;

use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;

class FillableInferrer
{
    /**
     * @return list<string>
     */
    public function infer(TableSchema $schema): array
    {
        $excluded = (array) config('api-from-table.exclude_fillable', [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $fillable = [];
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

            $fillable[] = $column->name;
        }

        return $fillable;
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
}
