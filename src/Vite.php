<?php

namespace Hks\Vite;

use Kirby\Cms\App;
use Kirby\Toolkit\Str;

class Vite
{
    /**
     * A cache of asset manifests.
     *
     * @var array
     */
    protected static array $manifests = [];

    /**
     * The HTML tag generator.
     *
     * @var \Hks\Vite\Generator|null
     */
    protected ?Generator $generator = null;

    /**
     * The vite development server.
     *
     * @var \Hks\Vite\Server|null
     */
    protected ?Server $server = null;

    /**
     * Whether hot reloading is enabled.
     *
     * @var bool|null
     */
    protected ?bool $hmr = null;

    /**
     * The build directory.
     *
     * @var string|null
     */
    protected ?string $buildDirectory = 'assets/build';

    /**
     * The name of the manifest file.
     *
     * @var string|null
     */
    protected ?string $manifest = 'manifest.json';

    /**
     * A collection of entry points.
     *
     * @var array
     */
    protected array $entryPoints = [];

    /**
     * Create a new instance of the Vite class.
     *
     * @param array $entryPoints A collection of entry points.
     */
    public function __construct(array $entryPoints = [])
    {
        $this->withEntryPoints($entryPoints);
    }

    /**
     * Create a new instance of the Vite class.
     *
     * @param array $entryPoints A collection of entry points.
     * @return static
     */
    public static function make(array $entryPoints = []): static
    {
        return new static($entryPoints);
    }

    /**
     * Determine whether vite should use the asset manifest.
     *
     * @return bool
     */
    public function shouldUseManifest(): bool
    {
        if (is_null($this->manifest)) {
            return false;
        }

        return ! $this->shouldUseHotReloading();
    }

    /**
     * Determine whether vite is running hot module reloading.
     *
     * @return bool
     */
    public function shouldUseHotReloading(): bool
    {
        return $this->hmr ??= $this->server()->isRunning();
    }

    /**
     * Customize the name of the manifest file.
     *
     * @param string|null $manifest The name of the manifest file.
     * @return static
     */
    public function useManifestFilename(?string $manifest = 'manifest.json'): static
    {
        $this->manifest = $manifest;

        return $this;
    }

    /**
     * Customize the build directory used by vite.
     *
     * @param string $path A relative path to the build diectory.
     * @return $this
     */
    public function useBuildDirectory(string $path): static
    {
        $this->buildDirectory = Str::trim($path, '/');

        return $this;
    }

    /**
     * Use hot reloading.
     *
     * @param bool $hot Whether to enable hot reloading.
     * @return $this
     */
    public function useHotReloading(bool $hmr = true): static
    {
        $this->hmr = $hmr;

        return $this;
    }

    /**
     * Use the given HTML tag generator.
     *
     * @param \Hks\Vite\Generator $generator
     * @return static
     */
    public function useTagGenerator(Generator $generator): static
    {
        $this->generator = $generator;

        return $this;
    }

    /**
     * Customize the entry points to use.
     *
     * @param array $entryPoints A collection of entry points.
     * @return $this
     */
    public function withEntryPoints(array $entryPoints): static
    {
        $this->entryPoints = $entryPoints;

        return $this;
    }

    /**
     * Load the asset manifest.
     *
     * @return \Hks\Vite\Manifest
     */
    public function manifest(): Manifest
    {
        $file = App::instance()->root() . '/' . $this->buildDirectory . '/' . $this->manifest;

        if (! isset(static::$manifests[$file])) {
            static::$manifests[$file] = new Manifest($file);
        }

        return static::$manifests[$file];
    }

    /**
     * Get the HTML tag generator.
     *
     * @return \Hks\Vite\Generator
     */
    public function generator(): Generator
    {
        return $this->generator ??= new Generator(
            nonce: $this->option('nonce')
        );
    }

    /**
     * The vite development server.
     *
     * @return \Hks\Vite\Server
     */
    public function server(): Server
    {
        return $this->server ??= new Server(
            host: $this->option('server.host', 'localhost'),
            port: $this->option('server.port', 5173),
        );
    }

    /**
     * Get the value for a given plugin option.
     *
     * @param string $key The name of the option.
     * @param mixed $default An optional fallback value.
     * @return mixed
     */
    public function option(string $key, mixed $default = null): mixed
    {
        return App::instance()->option("hksagentur.vite.{$key}", $default);
    }

    /**
     * Generate the markup for the vite bundle.
     *
     * @param string $glue The glue character to use.
     * @return string
     */
    public function render(string $glue = ''): string
    {
        if ($this->shouldUseManifest()) {
            $elements = $this->generator()->generateTagsForManifest(
                manifest: $this->manifest()
            );
        } else {
            $elements = $this->generator()->generateTagsForViteClient(
                entryPoints: $this->entryPoints,
                host: $this->option('server.host', 'localhost'),
                port: $this->option('server.port', 5173)
            );
        }

        return implode($glue, $elements);
    }

    /**
     * Generate markup for the vite bundle.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->render();
    }

    /**
     * Generate markup for the vite bundle.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Delegate method calls to the manifest.
     *
     * @param string $method The name of the method.
     * @param array $parameters A collection of method parameters.
     * @return mixed
     */
    public function __call($method, array $parameters = []): mixed
    {
        return $this->manifest()->{$method}(...$parameters);
    }
}
