<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Schema;

final readonly class ColumnSchema
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable = false,
        public mixed $default = null,
        public ?int $length = null,
        public ?int $precision = null,
        public ?int $scale = null,
        public bool $autoIncrement = false,
        public bool $primaryKey = false,
    ) {}

    public function hasDefault(): bool
    {
        return $this->default !== null;
    }

    public function isTinyIntBoolean(): bool
    {
        return $this->type === 'tinyint' && $this->length === 1;
    }
}
