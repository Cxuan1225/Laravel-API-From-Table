<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Schema;

final readonly class ForeignKeySchema
{
    /**
     * @param  list<string>  $columns
     * @param  list<string>  $foreignColumns
     */
    public function __construct(
        public ?string $name,
        public array $columns,
        public string $foreignTable,
        public array $foreignColumns,
        public ?string $foreignSchema = null,
        public ?string $onUpdate = null,
        public ?string $onDelete = null,
    ) {}
}
