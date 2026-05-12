<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\FieldExposureResolver;
use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Inferrers\RelationshipInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\StubRenderer;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class ResourceGenerator
{
    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
        protected FieldExposureResolver $fieldExposureResolver,
        protected RelationshipInferrer $relationshipInferrer,
        protected StubRenderer $renderer,
        protected Filesystem $files,
    ) {}

    public function generate(TableSchema $schema): string
    {
        $modelName = $this->modelNameInferrer->infer($schema->name);

        return $this->renderer->render($this->loadStub(), [
            'namespace' => (string) config('api-from-table.namespace.resources', 'App\\Http\\Resources'),
            'class' => $this->className($schema),
            'model_namespace' => (string) config('api-from-table.namespace.models', 'App\\Models'),
            'model_class' => $modelName,
            'fields' => $this->buildFields($this->fields($schema), $this->relationshipFields($schema)),
        ]);
    }

    public function className(TableSchema $schema): string
    {
        return $this->modelNameInferrer->infer($schema->name).'Resource';
    }

    /**
     * @return list<string>
     */
    protected function fields(TableSchema $schema): array
    {
        return $this->fieldExposureResolver->resourceVisible($schema);
    }

    /**
     * @param  list<string>  $fields
     * @param  list<string>  $relationshipFields
     */
    protected function buildFields(array $fields, array $relationshipFields): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $lines[] = "            '{$field}' => \$this->{$field},";
        }

        foreach ($relationshipFields as $field) {
            $lines[] = $field;
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    protected function relationshipFields(TableSchema $schema): array
    {
        if (! (bool) config('api-from-table.generate.relationships', false)) {
            return [];
        }

        $fields = [];

        foreach ($this->relationshipInferrer->belongsTo($schema) as $relationship) {
            $resourceClass = $relationship['class'].'Resource';
            $fields[] = "            '{$relationship['name']}' => new {$resourceClass}(\$this->whenLoaded('{$relationship['name']}')),";
        }

        foreach ($this->relationshipInferrer->hasMany($schema) as $relationship) {
            $resourceClass = $relationship['class'].'Resource';
            $fields[] = "            '{$relationship['name']}' => {$resourceClass}::collection(\$this->whenLoaded('{$relationship['name']}')),";
        }

        return $fields;
    }

    protected function loadStub(): string
    {
        $custom = base_path('stubs/vendor/api-from-table/resource.stub');
        if ($this->files->exists($custom)) {
            return $this->files->get($custom);
        }

        $package = __DIR__.'/../../stubs/resource.stub';
        if (! $this->files->exists($package)) {
            throw new RuntimeException("Resource stub not found at [{$package}].");
        }

        return $this->files->get($package);
    }
}
