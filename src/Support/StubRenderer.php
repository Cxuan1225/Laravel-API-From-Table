<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Support;

class StubRenderer
{
    /**
     * @param  array<string, string>  $replacements
     */
    public function render(string $stub, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{ '.$key.' }}', $value, $stub);
            $stub = str_replace('{{'.$key.'}}', $value, $stub);
        }

        return $this->cleanup($stub);
    }

    protected function cleanup(string $code): string
    {
        $code = preg_replace("/\n{3,}/", "\n\n", $code) ?? $code;
        $code = preg_replace("/(\{)\n(\s*\n)+/", "$1\n", $code) ?? $code;
        $code = preg_replace("/(\n\s*\n)+(\s*\})/", "\n$2", $code) ?? $code;

        if (! str_ends_with($code, "\n")) {
            $code .= "\n";
        }

        return $code;
    }
}
