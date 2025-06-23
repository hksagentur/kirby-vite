<?php

namespace Hks\Vite;

use Exception;
use Kirby\Cms\App;
use Kirby\Cms\Url;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

class Chunk
{
    /**
     * The asset manifest.
     *
     * @var \Hks\Vite\Manifest
     */
    protected Manifest $manifest;

    /**
     * The input file.
     *
     * @var string
     */
    protected string $id;

    /**
     * The chunk data.
     *
     * @var array
     */
    protected array $data;

    /**
     * Create a new instance of the Chunk class.
     *
     * @param \Hks\Vite\Manifest $manifest The manifest the chunk belongs to.
     * @param string $id The name of the input file.
     * @param array $data The data source of the chunk.
     */
    public function __construct(Manifest $manifest, string $id, array $data)
    {
        if (empty($data['file'])) {
            throw new Exception('The chunk is missing a valid output path.');
        }

        $this->manifest = $manifest;
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * Determine whether the chunk is of the given type.
     *
     * @param \Hks\Vite\ChunkType $type The type to test for.
     * @return bool
     */
    public function is(ChunkType $type): bool
    {
        return $this->type() === $type;
    }

    /**
     * Get the given value of the given property.
     *
     * @param string $key The name of the property to get.
     * @param mixed $default An optional default value.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Determine whether the current chunk is an entry point.
     *
     * @return bool
     */
    public function isEntry(): bool
    {
        return $this->get('isEntry', false);
    }

    /**
     * Determine whether the chunk is an asset.
     *
     * @return bool
     */
    public function isAsset(): bool
    {
        return $this->is(ChunkType::Asset);
    }

    /**
     * Get the type of the chunk.
     *
     * @return \Hks\Vite\ChunkType
     */
    public function type(): ChunkType
    {
        return ChunkType::from($this->extension());
    }

    /**
     * Get the identifier of the chunk.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get the name of output file.
     *
     * @return string
     */
    public function file(): string
    {
        return $this->get('file');
    }

    /**
     * Get the path to the output file.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->manifest->base() . '/' . $this->file();
    }

    /**
     * Get the extension of output file.
     *
     * @return string
     */
    public function extension(): string
    {
        return F::extension($this->path());
    }

    /**
     * Get the name of output file.
     *
     * @return string
     */
    public function filename(): string
    {
        return F::filename($this->path());
    }

    /**
     * Get the absolute path to the directory holding the output file.
     *
     * @return string
     */
    public function dirname(): string
    {
        return F::dirname($this->path());
    }

    /**
     * Get the absolute path to the source file.
     *
     * @return string|null
     */
    public function source(): ?string
    {
        $path = $this->get('src');

        if (!$path) {
            return null;
        }

        return App::instance()->roots()->base() . '/' . Str::ltrim($path, '/');
    }

    /**
     * Get the URI of the chunk.
     *
     * @return string
     */
    public function uri(): string
    {
        return F::relativepath(
            file: $this->path(),
            in: App::instance()->roots()->index()
        );
    }

    /**
     * Get the absolute URL of the chunk.
     *
     * @return string
     */
    public function url(): string
    {
        return Url::makeAbsolute(
            path: $this->uri(),
            home: App::instance()->urls()->index()
        );
    }

    /**
     * Get the assets imported by the current chunk.
     *
     * @return \Hks\Vite\ChunkCollection
     */
    public function assets(): ChunkCollection
    {
        return $this->findMany($this->get('assets', []));
    }

    /**
     * Get the styles imported by the current chunk.
     *
     * @return \Hks\Vite\ChunkCollection
     */
    public function styles(): ChunkCollection
    {
        return $this->findMany($this->get('css', []));
    }

    /**
     * Get the scripts imported by the current chunk.
     *
     * @return \Hks\Vite\ChunkCollection
     */
    public function imports(): ChunkCollection
    {
        return $this->findMany($this->get('imports', []));
    }

    /**
     * Convert the current chunk to an array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Convert the chunk to a string representation.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->url();
    }

    /**
     * Convert the chunk to a string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Dynamically retrieve chunk properties.
     *
     * @param string $name The name of the property.
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $name, array $parameters = []): mixed
    {
        return $this->get($name);
    }

    /**
     * Find the a specific chunks by file name.
     *
     * @param array $file The file to search for.
     * @return \Hks\Vite\Chunk|null
     */
    protected function find(string $file): ?Chunk
    {
        return $this->manifest->chunks()->find('id', '==', $file);
    }

    /**
     * Find the corresponding chunks.
     *
     * @param array $files The output name of the files to search.
     * @return \Hks\Vite\ChunkCollection
     */
    protected function findMany(array $files): ChunkCollection
    {
        return $this->manifest->chunks()->filter('id', 'in', $files);
    }

}
