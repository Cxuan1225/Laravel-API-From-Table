<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Schema;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DatabaseSchemaReader
{
    public function __construct(
        protected ?ConnectionResolverInterface $resolver = null,
    ) {}

    public function read(string $tableName, ?string $connection = null): TableSchema
    {
        $builder = $connection
            ? Schema::connection($connection)
            : Schema::getFacadeRoot();

        if (! $builder->hasTable($tableName)) {
            throw new RuntimeException("Table [{$tableName}] does not exist.");
        }

        $rawColumns = $builder->getColumns($tableName);
        $primaryKeyColumns = $this->resolvePrimaryKey($builder, $tableName);

        $columns = [];
        foreach ($rawColumns as $col) {
            $columns[] = $this->buildColumn($col, $primaryKeyColumns);
        }

        return new TableSchema($tableName, $columns);
    }

    /**
     * @param  array<string, mixed>  $col
     * @param  list<string>  $primaryKeyColumns
     */
    protected function buildColumn(array $col, array $primaryKeyColumns): ColumnSchema
    {
        $rawType = strtolower((string) ($col['type'] ?? ''));
        $rawTypeName = strtolower((string) ($col['type_name'] ?? $rawType));

        $baseType = $this->extractBaseType($rawTypeName !== '' ? $rawTypeName : $rawType);
        [$precision, $scale] = $this->extractPrecisionScale($rawType);
        $length = $precision === null ? $this->extractLength($rawType) : null;

        return new ColumnSchema(
            name: (string) $col['name'],
            type: $baseType,
            nullable: (bool) ($col['nullable'] ?? false),
            default: $col['default'] ?? null,
            length: $length,
            precision: $precision,
            scale: $scale,
            autoIncrement: (bool) ($col['auto_increment'] ?? false),
            primaryKey: in_array((string) $col['name'], $primaryKeyColumns, true),
        );
    }

    /**
     * @return list<string>
     */
    protected function resolvePrimaryKey(mixed $builder, string $tableName): array
    {
        try {
            $indexes = $builder->getIndexes($tableName);
        } catch (\Throwable) {
            return [];
        }

        foreach ($indexes as $index) {
            if (($index['primary'] ?? false) === true) {
                $columns = $index['columns'] ?? [];

                return array_values(array_map('strval', $columns));
            }
        }

        return [];
    }

    protected function extractBaseType(string $type): string
    {
        if (preg_match('/^([a-z_]+)/', $type, $m) === 1) {
            return $m[1];
        }

        return $type;
    }

    protected function extractLength(string $type): ?int
    {
        if (preg_match('/\((\d+)\)/', $type, $m) === 1) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    protected function extractPrecisionScale(string $type): array
    {
        if (preg_match('/\((\d+)\s*,\s*(\d+)\)/', $type, $m) === 1) {
            return [(int) $m[1], (int) $m[2]];
        }

        return [null, null];
    }
}
