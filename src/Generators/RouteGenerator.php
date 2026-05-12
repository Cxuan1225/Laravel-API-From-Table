<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\RouteResolver;

class RouteGenerator
{
    public function __construct(
        protected RouteResolver $routeResolver,
    ) {}

    public function generate(TableSchema $schema): string
    {
        return $this->routeResolver->route($schema);
    }
}
