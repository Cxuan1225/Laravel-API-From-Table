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
        $indexes = $this->readIndexes($builder, $tableName);
        $foreignKeys = $this->readForeignKeys($builder, $tableName);
        $primaryKeyColumns = $this->resolvePrimaryKey($indexes);

        $columns = [];
        $enums = [];
        foreach ($rawColumns as $col) {
            $columns[] = $this->buildColumn($col, $primaryKeyColumns);

            $enumValues = $this->extractEnumValues((string) ($col['type'] ?? ''));
            if ($enumValues !== []) {
                $enums[] = new EnumSchema((string) $col['name'], $enumValues);
            }
        }

        return new TableSchema(
            name: $tableName,
            columns: $columns,
            indexes: $indexes,
            foreignKeys: $foreignKeys,
            enums: $enums,
        );
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
    protected function resolvePrimaryKey(array $indexes): array
    {
        foreach ($indexes as $index) {
            if ($index->primary) {
                return $index->columns;
            }
        }

        return [];
    }

    /**
     * @return list<IndexSchema>
     */
    protected function readIndexes(mixed $builder, string $tableName): array
    {
        try {
            $indexes = $builder->getIndexes($tableName);
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_map(
            fn (array $index): IndexSchema => new IndexSchema(
                name: (string) ($index['name'] ?? ''),
                columns: array_values(array_map('strval', (array) ($index['columns'] ?? []))),
                unique: (bool) ($index['unique'] ?? false),
                primary: (bool) ($index['primary'] ?? false),
                type: isset($index['type']) ? (string) $index['type'] : null,
            ),
            $indexes,
        ));
    }

    /**
     * @return list<ForeignKeySchema>
     */
    protected function readForeignKeys(mixed $builder, string $tableName): array
    {
        try {
            $foreignKeys = $builder->getForeignKeys($tableName);
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_map(
            fn (array $foreignKey): ForeignKeySchema => new ForeignKeySchema(
                name: isset($foreignKey['name']) ? (string) $foreignKey['name'] : null,
                columns: array_values(array_map('strval', (array) ($foreignKey['columns'] ?? []))),
                foreignTable: (string) ($foreignKey['foreign_table'] ?? ''),
                foreignColumns: array_values(array_map('strval', (array) ($foreignKey['foreign_columns'] ?? []))),
                foreignSchema: isset($foreignKey['foreign_schema']) ? (string) $foreignKey['foreign_schema'] : null,
                onUpdate: isset($foreignKey['on_update']) ? (string) $foreignKey['on_update'] : null,
                onDelete: isset($foreignKey['on_delete']) ? (string) $foreignKey['on_delete'] : null,
            ),
            $foreignKeys,
        ));
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

    /**
     * @return list<string>
     */
    protected function extractEnumValues(string $type): array
    {
        if (preg_match('/^enum\((.*)\)$/i', trim($type), $matches) !== 1) {
            return [];
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $matches[1], $values);

        return array_values(array_map(
            fn (string $value): string => stripcslashes($value),
            $values[1] ?? [],
        ));
    }
}
