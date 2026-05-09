<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Inferrers;

use Illuminate\Support\Str;

class ModelNameInferrer
{
    public function infer(string $tableName): string
    {
        return Str::studly(Str::singular($tableName));
    }
}
