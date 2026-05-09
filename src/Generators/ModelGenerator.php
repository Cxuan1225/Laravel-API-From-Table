<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Generators;

use Cxuan1225\LaravelApiFromTable\Inferrers\CastInferrer;
use Cxuan1225\LaravelApiFromTable\Inferrers\FillableInferrer;
use Cxuan1225\LaravelApiFromTable\Inferrers\ModelNameInferrer;
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
        protected CastInferrer $castInferrer,
        protected StubRenderer $renderer,
        protected Filesystem $files,
    ) {}

    public function generate(TableSchema $schema): string
    {
        $modelName = $this->modelNameInferrer->infer($schema->name);
        $fillable = $this->fillableInferrer->infer($schema);
        $casts = $this->castInferrer->infer($schema);

        return $this->renderer->render($this->loadStub(), [
            'namespace' => (string) config('api-from-table.namespace.models', 'App\\Models'),
            'class' => $modelName,
            'imports' => '',
            'table_property' => $this->buildTableProperty($schema, $modelName),
            'fillable' => $this->buildFillable($fillable),
            'casts' => $this->buildCasts($casts),
            'relationships' => '',
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
}
