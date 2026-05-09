<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;

class RouteGenerator
{
    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
    ) {}

    public function generate(TableSchema $schema): string
    {
        $controllerNamespace = (string) config('api-from-table.namespace.controllers', 'App\\Http\\Controllers');
        $controllerClass = $this->modelNameInferrer->infer($schema->name).'Controller';

        return "Route::apiResource('{$schema->name}', \\{$controllerNamespace}\\{$controllerClass}::class);";
    }
}
