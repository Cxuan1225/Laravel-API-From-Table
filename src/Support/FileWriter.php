<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable\Support;

use Illuminate\Filesystem\Filesystem;

class FileWriter
{
    public function __construct(
        protected Filesystem $files,
    ) {}

    public function write(string $path, string $contents, bool $force = false): bool
    {
        if ($this->files->exists($path) && ! $force) {
            return false;
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);

        return true;
    }

    public function exists(string $path): bool
    {
        return $this->files->exists($path);
    }

    public function appendRoute(string $path, string $route): bool
    {
        $this->files->ensureDirectoryExists(dirname($path));

        $contents = $this->files->exists($path)
            ? $this->files->get($path)
            : "<?php\n\n";

        $changed = false;
        $routeImport = 'use Illuminate\Support\Facades\Route;';

        if (! str_contains($contents, $routeImport)) {
            $contents = preg_replace('/^<\?php\s*/', "<?php\n\n{$routeImport}\n\n", $contents, 1) ?? $contents;
            $changed = true;
        }

        if (! str_contains($contents, $route)) {
            $separator = str_ends_with($contents, "\n") ? '' : "\n";
            $contents .= $separator.$route."\n";
            $changed = true;
        }

        if ($changed) {
            $this->files->put($path, $contents);
        }

        return $changed;
    }
}
