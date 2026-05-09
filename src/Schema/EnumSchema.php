<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Schema;

final readonly class EnumSchema
{
    /**
     * @param  list<string>  $values
     */
    public function __construct(
        public string $column,
        public array $values,
    ) {}
}
