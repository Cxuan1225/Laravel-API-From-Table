<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Schema;

final readonly class TableSchema
{
    /**
     * @param  list<ColumnSchema>  $columns
     */
    public function __construct(
        public string $name,
        public array $columns,
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
}
