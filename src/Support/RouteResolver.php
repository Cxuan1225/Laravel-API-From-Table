<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Support;

use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Illuminate\Support\Str;

class RouteResolver
{
    public const TARGET_ROUTES = 'routes';

    public const TARGET_API_ROUTES = 'api_routes';

    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
    ) {}

    public function resourceName(TableSchema $schema): string
    {
        return match ((string) config('api-from-table.routes.resource_name_style', 'kebab')) {
            'table' => $schema->name,
            default => str_replace('_', '-', Str::kebab($schema->name)),
        };
    }

    public function route(TableSchema $schema): string
    {
        $controllerNamespace = (string) config('api-from-table.namespace.controllers', 'App\\Http\\Controllers');
        $controllerClass = $this->modelNameInferrer->infer($schema->name).'Controller';

        return "Route::apiResource('".$this->resourceName($schema)."', \\{$controllerNamespace}\\{$controllerClass}::class);";
    }

    public function uri(TableSchema $schema, string $target = self::TARGET_ROUTES): string
    {
        $prefix = trim($this->prefix($target), '/');
        $resource = $this->resourceName($schema);

        if ($prefix === '') {
            return '/'.$resource;
        }

        return '/'.$prefix.'/'.$resource;
    }

    public function prefix(string $target): string
    {
        return (string) config("api-from-table.routes.prefixes.{$target}", $target === self::TARGET_API_ROUTES ? 'api' : '');
    }
}
