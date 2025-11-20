<?php

namespace Hks\Vite;

use Kirby\Cms\App;
use Kirby\Toolkit\Str;

class Vite
{
    protected static array $manifests = [];

    protected ?Generator $generator = null;
    protected ?Server $server = null;

    protected ?bool $hmr = null;

    protected ?string $manifest = '.vite/manifest.json';
    protected ?string $buildDirectory = 'assets/build';

    public function __construct(
        protected array $entryPoints = []
    ) {
        $this->withEntryPoints($entryPoints);
    }

    public static function make(array $entryPoints = []): static
    {
        return new static($entryPoints);
    }

    public function shouldUseManifest(): bool
    {
        if (is_null($this->manifest)) {
            return false;
        }

        return ! $this->shouldUseHotReloading();
    }

    public function shouldUseHotReloading(): bool
    {
        return $this->hmr ??= $this->server()->isRunning();
    }

    public function useManifestFilename(?string $manifest = 'manifest.json'): static
    {
        $this->manifest = $manifest;

        return $this;
    }

    public function useBuildDirectory(string $path): static
    {
        $this->buildDirectory = Str::trim($path, '/');

        return $this;
    }

    public function useHotReloading(bool $hmr = true): static
    {
        $this->hmr = $hmr;

        return $this;
    }

    public function useTagGenerator(Generator $generator): static
    {
        $this->generator = $generator;

        return $this;
    }

    public function withEntryPoints(array $entryPoints): static
    {
        $this->entryPoints = $entryPoints;

        return $this;
    }

    public function manifest(): Manifest
    {
        $key = $this->buildDirectory . '/' . $this->manifest;

        if (! isset(static::$manifests[$key])) {
            static::$manifests[$key] = new Manifest(
                file: $this->manifest,
                buildDirectory: $this->buildDirectory
            );
        }

        return static::$manifests[$key];
    }

    public function generator(): Generator
    {
        return $this->generator ??= new Generator(
            nonce: $this->option('nonce')
        );
    }

    public function server(): Server
    {
        return $this->server ??= new Server(
            host: $this->option('server.host', 'localhost'),
            port: $this->option('server.port', 5173),
        );
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return App::instance()->option("hksagentur.vite.{$key}", $default);
    }

    public function render(string $glue = ''): string
    {
        if ($this->shouldUseManifest()) {
            $elements = $this->generator()->generateTagsForManifest(
                manifest: $this->manifest(),
                entryPoints: $this->entryPoints
            );
        } else {
            $elements = $this->generator()->generateTagsForViteClient(
                entryPoints: $this->entryPoints,
                host: $this->option('client.host', 'localhost'),
                port: $this->option('client.port', 5173)
            );
        }

        return implode($glue, $elements);
    }

    public function toString(): string
    {
        return $this->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function __call($method, array $parameters = []): mixed
    {
        return $this->manifest()->{$method}(...$parameters);
    }
}
