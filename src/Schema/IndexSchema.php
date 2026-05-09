<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Schema;

final readonly class IndexSchema
{
    /**
     * @param  list<string>  $columns
     */
    public function __construct(
        public string $name,
        public array $columns,
        public bool $unique = false,
        public bool $primary = false,
        public ?string $type = null,
    ) {}
}
