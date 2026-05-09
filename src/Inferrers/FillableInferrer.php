<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Inferrers;

use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;

class FillableInferrer
{
    public function __construct(
        protected ?FieldExposureResolver $fieldExposureResolver = null,
    ) {
        $this->fieldExposureResolver ??= new FieldExposureResolver();
    }

    /**
     * @return list<string>
     */
    public function infer(TableSchema $schema): array
    {
        return $this->fieldExposureResolver->fillable($schema);
    }
}
