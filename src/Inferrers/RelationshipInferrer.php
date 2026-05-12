<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Inferrers;

use Cxuan1225\LaravelApiFromTable\Schema\ForeignKeySchema;
use Cxuan1225\LaravelApiFromTable\Schema\TableSchema;
use Illuminate\Support\Str;

class RelationshipInferrer
{
    public function __construct(
        protected ModelNameInferrer $modelNameInferrer,
    ) {}

    /**
     * @return list<array{name: string, class: string, foreign_key: string, owner_key: string, uses_default_keys: bool}>
     */
    public function belongsTo(TableSchema $schema): array
    {
        $relationships = [];

        foreach ($schema->foreignKeys as $foreignKey) {
            if (! $this->isSingleColumn($foreignKey)) {
                continue;
            }

            $foreignKeyName = $foreignKey->columns[0];
            $name = $this->belongsToName($foreignKeyName, $foreignKey->foreignTable);
            $class = $this->modelNameInferrer->infer($foreignKey->foreignTable);

            $relationships[$name] = [
                'name' => $name,
                'class' => $class,
                'foreign_key' => $foreignKeyName,
                'owner_key' => $foreignKey->foreignColumns[0],
                'uses_default_keys' => $foreignKeyName === Str::snake($name).'_id'
                    && $foreignKey->foreignColumns[0] === 'id',
            ];
        }

        return array_values($relationships);
    }

    /**
     * @return list<array{name: string, class: string, foreign_key: string, local_key: string, uses_default_keys: bool}>
     */
    public function hasMany(TableSchema $schema): array
    {
        $relationships = [];
        $modelName = $this->modelNameInferrer->infer($schema->name);
        $defaultForeignKey = Str::snake($modelName).'_id';

        foreach ($schema->referencingForeignKeys as $foreignKey) {
            if (! $this->isSingleColumn($foreignKey) || $foreignKey->tableName === null) {
                continue;
            }

            $name = Str::camel($foreignKey->tableName);
            $class = $this->modelNameInferrer->infer($foreignKey->tableName);

            $relationships[$name] = [
                'name' => $name,
                'class' => $class,
                'foreign_key' => $foreignKey->columns[0],
                'local_key' => $foreignKey->foreignColumns[0],
                'uses_default_keys' => $foreignKey->columns[0] === $defaultForeignKey
                    && $foreignKey->foreignColumns[0] === 'id',
            ];
        }

        return array_values($relationships);
    }

    protected function belongsToName(string $foreignKeyName, string $foreignTable): string
    {
        if (str_ends_with($foreignKeyName, '_id')) {
            return Str::camel(substr($foreignKeyName, 0, -3));
        }

        return Str::camel(Str::singular($foreignTable));
    }

    protected function isSingleColumn(ForeignKeySchema $foreignKey): bool
    {
        return count($foreignKey->columns) === 1
            && count($foreignKey->foreignColumns) === 1
            && $foreignKey->foreignTable !== '';
    }
}
