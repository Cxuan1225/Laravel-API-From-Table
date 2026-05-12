<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\CastInferrer;
use Cxuan1225\LaravelApiFromTable\Inferrers\FieldExposureResolver;
use Cxuan1225\LaravelApiFromTable\Inferrers\FillableInferrer;
use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
use Cxuan1225\LaravelApiFromTable\Inferrers\RelationshipInferrer;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Cxuan1225\LaravelApiFromTable\Support\StubRenderer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class ModelGenerator
{
    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
        protected FillableInferrer $fillableInferrer,
        protected FieldExposureResolver $fieldExposureResolver,
        protected CastInferrer $castInferrer,
        protected RelationshipInferrer $relationshipInferrer,
        protected StubRenderer $renderer,
        protected Filesystem $files,
    ) {}

    public function generate(TableSchema $schema): string
    {
        $modelName = $this->modelNameInferrer->infer($schema->name);
        $fillable = $this->fillableInferrer->infer($schema);
        $hidden = $this->fieldExposureResolver->modelHidden($schema);
        $casts = $this->castInferrer->infer($schema);
        $relationshipsEnabled = (bool) config('api-from-table.generate.relationships', false);

        return $this->renderer->render($this->loadStub(), [
            'namespace' => (string) config('api-from-table.namespace.models', 'App\\Models'),
            'class' => $modelName,
            'imports' => $this->buildImports($schema, $relationshipsEnabled),
            'table_property' => $this->buildTableProperty($schema, $modelName),
            'fillable' => $this->buildFillable($fillable),
            'hidden' => $this->buildHidden($hidden),
            'casts' => $this->buildCasts($casts),
            'relationships' => $relationshipsEnabled ? $this->buildRelationships($schema) : '',
        ]);
    }

    public function modelName(TableSchema $schema): string
    {
        return $this->modelNameInferrer->infer($schema->name);
    }

    protected function loadStub(): string
    {
        $custom = base_path('stubs/vendor/api-from-table/model.stub');
        if ($this->files->exists($custom)) {
            return $this->files->get($custom);
        }

        $package = __DIR__.'/../../stubs/model.stub';
        if (! $this->files->exists($package)) {
            throw new RuntimeException("Model stub not found at [{$package}].");
        }

        return $this->files->get($package);
    }

    protected function buildTableProperty(TableSchema $schema, string $modelName): string
    {
        $expected = Str::snake(Str::pluralStudly($modelName));
        if ($expected === $schema->name) {
            return '';
        }

        return "    protected \$table = '{$schema->name}';";
    }

    protected function buildImports(TableSchema $schema, bool $relationshipsEnabled): string
    {
        $imports = [
            'Illuminate\\Database\\Eloquent\\Model',
        ];

        if ($relationshipsEnabled) {
            $modelNamespace = (string) config('api-from-table.namespace.models', 'App\\Models');
            $belongsTo = $this->relationshipInferrer->belongsTo($schema);
            $hasMany = $this->relationshipInferrer->hasMany($schema);

            if ($belongsTo !== []) {
                $imports[] = 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo';
            }

            if ($hasMany !== []) {
                $imports[] = 'Illuminate\\Database\\Eloquent\\Relations\\HasMany';
            }

            foreach ([...$belongsTo, ...$hasMany] as $relationship) {
                $imports[] = $modelNamespace.'\\'.$relationship['class'];
            }
        }

        $imports = array_values(array_unique($imports));
        sort($imports);

        return implode("\n", array_map(
            fn (string $import): string => "use {$import};",
            $imports,
        ));
    }

    /**
     * @param  list<string>  $fillable
     */
    protected function buildFillable(array $fillable): string
    {
        $lines = [];
        foreach ($fillable as $name) {
            $lines[] = "        '{$name}',";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $hidden
     */
    protected function buildHidden(array $hidden): string
    {
        if ($hidden === []) {
            return '';
        }

        $lines = [
            '    protected $hidden = [',
        ];
        foreach ($hidden as $name) {
            $lines[] = "        '{$name}',";
        }
        $lines[] = '    ];';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, string>  $casts
     */
    protected function buildCasts(array $casts): string
    {
        if ($casts === []) {
            return '';
        }

        $lines = [
            '    protected function casts(): array',
            '    {',
            '        return [',
        ];
        foreach ($casts as $name => $cast) {
            $lines[] = "            '{$name}' => '{$cast}',";
        }
        $lines[] = '        ];';
        $lines[] = '    }';

        return implode("\n", $lines);
    }

    protected function buildRelationships(TableSchema $schema): string
    {
        $methods = [];

        foreach ($this->relationshipInferrer->belongsTo($schema) as $relationship) {
            $call = $relationship['uses_default_keys']
                ? "belongsTo({$relationship['class']}::class)"
                : "belongsTo({$relationship['class']}::class, '{$relationship['foreign_key']}', '{$relationship['owner_key']}')";

            $methods[] = implode("\n", [
                "    public function {$relationship['name']}(): BelongsTo",
                '    {',
                "        return \$this->{$call};",
                '    }',
            ]);
        }

        foreach ($this->relationshipInferrer->hasMany($schema) as $relationship) {
            $call = $relationship['uses_default_keys']
                ? "hasMany({$relationship['class']}::class)"
                : "hasMany({$relationship['class']}::class, '{$relationship['foreign_key']}', '{$relationship['local_key']}')";

            $methods[] = implode("\n", [
                "    public function {$relationship['name']}(): HasMany",
                '    {',
                "        return \$this->{$call};",
                '    }',
            ]);
        }

        return implode("\n\n", $methods);
    }
}
