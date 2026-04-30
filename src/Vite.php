<?php

namespace Hks\Vite;

use Kirby\Cms\App;
use Kirby\Toolkit\Str;
use Stringable;

class Vite implements Stringable
{
    protected static array $manifests = [];

    protected ?Generator $generator = null;
    protected ?Server $server = null;

    protected ?array $entryPoints = null;
    protected ?array $preloadChunks = null;

    protected ?bool $hmr = null;

    protected ?string $manifest = '.vite/manifest.json';

    protected ?string $templateDirectory = 'site/assets';
    protected ?string $buildDirectory = 'assets/build';

    public function __construct(?array $entryPoints)
    {
        $this->entryPoints = $entryPoints;
    }

    public static function make(?array $entryPoints = null): static
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

    public function useTemplateDirectory(string $path): static
    {
        $this->templateDirectory = Str::trim($path, '/');

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

    public function withPreloadTags(array|string $assets): static
    {
        $this->preloadChunks = [
            ...(array) $this->preloadChunks,
            ...(array) $assets,
        ];

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

    public function entryPoints(): ChunkCollection
    {
        $chunks = $this->manifest()->entries();

        if ($this->entryPoints === null) {
            return $chunks;
        }

        return $chunks->whereKey($this->expandEntryPoints());
    }

    public function preloadedChunks(): ChunkCollection
    {
        if ($this->preloadChunks === null) {
            return new ChunkCollection();
        }

        return $this->manifest()->chunks()->whereKey($this->preloadChunks);
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
        $entryPoints = $this->entryPoints();
        $preloadedChunks = $this->preloadedChunks();

        if ($this->shouldUseManifest()) {
            return implode($glue, [
                ...$this->generator()->generatePreloadTagsForChunks($preloadedChunks),
                ...$this->generator()->generateTagsForEntryPoints($entryPoints),
            ]);
        }

        $host = $this->option('client.host', 'localhost');
        $port = $this->option('client.port', 5173);
        $plugins = $this->option('client.plugins', []);

        return implode($glue, $this->generator()->generateTagsForViteClient($entryPoints, $plugins, $host, $port));
    }

    public function toString(): string
    {
        return $this->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function __call(string $method, array $parameters = []): mixed
    {
        return $this->manifest()->{$method}(...$parameters);
    }

    protected function expandEntryPoints(): array
    {
        $entryPoints = $this->entryPoints ?? [];

        if (! in_array('@auto', $entryPoints)) {
            return $entryPoints;
        }

        $template = App::instance()->site()->page()->template();

        $entryPoints = array_diff($entryPoints, ['@auto']);

        $entryPoints[] = "{$this->templateDirectory}/css/templates/{$template}.css";
        $entryPoints[] = "{$this->templateDirectory}/js/templates/{$template}.js";

        return $entryPoints;
    }
}
