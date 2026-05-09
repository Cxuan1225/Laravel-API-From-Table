<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Schema;

final readonly class TableSchema
{
    /**
     * @param  list<ColumnSchema>  $columns
     * @param  list<IndexSchema>  $indexes
     * @param  list<ForeignKeySchema>  $foreignKeys
     * @param  list<EnumSchema>  $enums
     * @param  list<CheckConstraintSchema>  $checks
     */
    public function __construct(
        public string $name,
        public array $columns,
        public array $indexes = [],
        public array $foreignKeys = [],
        public array $enums = [],
        public array $checks = [],
    ) {}

    public function column(string $name): ?ColumnSchema
    {
        foreach ($this->columns as $column) {
            if ($column->name === $name) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @return list<IndexSchema>
     */
    public function uniqueIndexes(): array
    {
        return array_values(array_filter(
            $this->indexes,
            fn (IndexSchema $index): bool => $index->unique && ! $index->primary,
        ));
    }

    public function uniqueIndexForColumn(string $column): ?IndexSchema
    {
        foreach ($this->uniqueIndexes() as $index) {
            if ($index->columns === [$column]) {
                return $index;
            }
        }

        return null;
    }

    public function foreignKeyForColumn(string $column): ?ForeignKeySchema
    {
        foreach ($this->foreignKeys as $foreignKey) {
            if ($foreignKey->columns === [$column] && count($foreignKey->foreignColumns) === 1) {
                return $foreignKey;
            }
        }

        return null;
    }

    public function enumForColumn(string $column): ?EnumSchema
    {
        foreach ($this->enums as $enum) {
            if ($enum->column === $column) {
                return $enum;
            }
        }

        return null;
    }

    public function checkForColumn(string $column): ?CheckConstraintSchema
    {
        foreach ($this->checks as $check) {
            if ($check->column === $column && $check->values !== []) {
                return $check;
            }
        }

        return null;
    }
}
