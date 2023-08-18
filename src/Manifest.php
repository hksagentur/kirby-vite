<?php

namespace Hks\Vite;

use Countable;
use Iterator;
use IteratorAggregate;
use Kirby\Cms\App;
use Kirby\Cms\Url;
use Kirby\Data\Data;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

class Manifest implements Countable, IteratorAggregate
{
    /**
     * The absolute path to the manifest file
     *
     * @var string
     */
    protected string $path;

    /**
     * The data of the asset manifest.
     *
     * @var \Hks\Vite\ChunkCollection
     */
    protected ?ChunkCollection $chunks = null;

    /**
     * Create a new instance of the Manifest class.
     *
     * @param array $data The data of the asset manifest.
     */
    public function __construct(string $file)
    {
        $this->path = $file;
    }

    /**
     * Determine whether the manifest is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->chunks()->isEmpty();
    }

    /**
     * Determine whether the current manifest exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return F::isReadable($this->path());
    }

    /**
     * Get the total number of chunks.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->chunks()->count();
    }

    /**
     * Get the absolute path of the manifest file.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get the file extension of the manifest.
     *
     * @return string
     */
    public function extension(): string
    {
        return F::extension($this->path());
    }

    /**
     * Get the filename of the manifest.
     *
     * @return string
     */
    public function filename(): string
    {
        return F::filename($this->path());
    }

    /**
     * Get the absolute path to the directory holding the manifest file.
     *
     * @return string
     */
    public function dirname(): string
    {
        return F::dirname($this->path());
    }

    /**
     * Get the URI of the manifest.
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
     * Get the absolute URL of the manifest.
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
     * Load the raw content of the current manifest file.
     *
     * @return array
     */
    public function contents(): array
    {
        return Data::read($this->path(), 'json');
    }

    /**
     * The chunks of the current manifest.
     *
     * @return \Hks\Vite\ChunkCollection
     */
    public function chunks(): ChunkCollection
    {
        return $this->chunks ??= ChunkFactory::collection($this, $this->contents());
    }

    /**
     * Find a chunk for a given source file.
     *
     * @param string $file
     * @return \Hks\Vite\Chunk|null
     */
    public function chunk(string $file): ?Chunk
    {
        return $this->chunks()->findBy('id', Str::ltrim($file, '/'));
    }

    /**
     * Get the entry points of the manifest.
     *
     * @return \Hks\Vite\ChunkCollection
     */
    public function entries(): ChunkCollection
    {
        return $this->chunks()->filter(fn (Chunk $chunk) => $chunk->isEntry());
    }

    /**
     * Find a chunk for a given entry file.
     *
     * @param string $file
     * @return \Hks\Vite\Chunk|null
     */
    public function entry(string $file): ?Chunk
    {
        return $this->entries()->findBy('id', Str::ltrim($file, '/'));
    }

    /**
     * Convert the manifest to an array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->chunks()->toArray();
    }

    /**
     * Convert the manifest to a json encoded string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->contents());
    }

    /**
     * Convert the current manifest to a string representation.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->toJson();
    }

    /**
     * Get an iterator for the chunks of the manifest.
     *
     * @return \Iterator
     */
    public function getIterator(): Iterator
    {
        return $this->chunks()->getIterator();
    }

    /**
     * Convert the current manifest to a string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
