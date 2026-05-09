<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Schema;

final readonly class CheckConstraintSchema
{
    /**
     * @param  list<string>  $values
     */
    public function __construct(
        public ?string $name,
        public ?string $column = null,
        public array $values = [],
        public ?string $expression = null,
    ) {}
}
