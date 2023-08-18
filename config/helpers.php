<?php

use Hks\Vite\Chunk;
use Hks\Vite\Vite;

if (! function_exists('vite')) {
    /**
     * Access the vite component.
     *
     * @param array $entryPoints A collection of entry points.
     * @return Vite\Vite
     */
    function vite(array $entryPoints = []): Vite
    {
        return new Vite($entryPoints);
    }
}

if (! function_exists('chunk')) {
    /**
     * Lookup a chunk from an asset manifest.
     *
     * @param string $file The name of the chunk.
     * @param string|null $directory The build directory.
     * @return \Hks\Vite\Chunk|null
     */
    function chunk(string $file, ?string $directory = null): ?Chunk
    {
        $vite = vite();

        if (isset($directory)) {
            $vite->useBuildDirectory($directory);
        }

        return $vite->manifest()->chunk($file);
    }
}
