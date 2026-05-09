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
}
