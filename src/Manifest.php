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

/** @implements IteratorAggregate<string, Chunk> */
class Manifest implements Countable, IteratorAggregate
{
    protected ?ChunkCollection $chunks = null;

    public function __construct(
        protected string $file,
        protected string $buildDirectory
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->chunks()->isEmpty();
    }

    public function exists(): bool
    {
        return F::isReadable($this->path());
    }

    public function count(): int
    {
        return $this->chunks()->count();
    }

    public function base(): string
    {
        return App::instance()->roots()->index() . '/' . $this->buildDirectory;
    }

    public function path(): string
    {
        return $this->base() . '/' . $this->file;
    }

    public function extension(): string
    {
        return F::extension($this->path());
    }

    public function filename(): string
    {
        return F::filename($this->path());
    }

    public function dirname(): string
    {
        return F::dirname($this->path());
    }

    public function uri(): string
    {
        return F::relativepath(
            file: $this->path(),
            in: App::instance()->roots()->index()
        );
    }

    public function url(): string
    {
        return Url::makeAbsolute(
            path: $this->uri(),
            home: App::instance()->urls()->index()
        );
    }

    public function contents(): array
    {
        return Data::read($this->path(), 'json');
    }

    public function chunks(): ChunkCollection
    {
        return $this->chunks ??= ChunkFactory::collection($this, $this->contents());
    }

    public function chunk(string $id): ?Chunk
    {
        return $this->chunks()->findBy('id', Str::ltrim($id, '/'));
    }

    public function entries(): ChunkCollection
    {
        return $this->chunks()->filter(fn (Chunk $chunk) => $chunk->isEntry());
    }

    public function entry(string $id): ?Chunk
    {
        return $this->entries()->findBy('id', Str::ltrim($id, '/'));
    }

    public function toArray(): array
    {
        return $this->chunks()->toArray();
    }

    public function toJson(): string
    {
        return json_encode($this->contents());
    }

    public function toString(): string
    {
        return $this->toJson();
    }

    public function getIterator(): Iterator
    {
        return $this->chunks()->getIterator();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
