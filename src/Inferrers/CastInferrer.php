<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Inferrers;

use Cxuan1225\LaravelApiFromTable\Schema\ColumnSchema;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;

class CastInferrer
{
    /**
     * @return array<string, string>
     */
    public function infer(TableSchema $schema): array
    {
        $excluded = (array) config('api-from-table.exclude_fillable', [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $casts = [];
        foreach ($schema->columns as $column) {
            if ($column->autoIncrement) {
                continue;
            }
            if (in_array($column->name, $excluded, true)) {
                continue;
            }

            $cast = $this->castFor($column);
            if ($cast !== null) {
                $casts[$column->name] = $cast;
            }
        }

        return $casts;
    }

    protected function castFor(ColumnSchema $column): ?string
    {
        $tinyintAsBoolean = (bool) config('api-from-table.tinyint_one_as_boolean', true);

        if ($tinyintAsBoolean && $column->isTinyIntBoolean()) {
            return 'boolean';
        }

        return match ($column->type) {
            'int', 'integer', 'bigint', 'smallint', 'mediumint', 'tinyint' => 'integer',
            'decimal', 'numeric' => $column->scale === 0
                ? 'integer'
                : 'decimal:'.($column->scale ?? 2),
            'float', 'double', 'real' => 'float',
            'boolean', 'bool' => 'boolean',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime',
            'json', 'jsonb' => 'array',
            default => null,
        };
    }
}
