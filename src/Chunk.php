<?php

namespace Hks\Vite;

use Exception;
use IteratorAggregate;
use Kirby\Cms\App;
use Kirby\Cms\Url;
use Kirby\Filesystem\F;
use Kirby\Filesystem\Mime;
use Kirby\Toolkit\Str;

/** @implements IteratorAggregate<string, Chunk> */
class Chunk implements IteratorAggregate
{
    public function __construct(
        protected Manifest $manifest,
        protected string $id,
        protected array $data
    ) {
        if (empty($data['file'])) {
            throw new Exception('The chunk is missing a valid output path.');
        }
    }

    public function is(ChunkType $type): bool
    {
        return $this->type() === $type;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function isEntry(): bool
    {
        return $this->get('isEntry', false);
    }

    public function isDynamicEntry(): bool
    {
        return $this->get('isDynamicEntry', false);
    }

    public function isAsset(): bool
    {
        return $this->is(ChunkType::Asset);
    }

    public function isScript(): bool
    {
        return $this->is(ChunkType::Script);
    }

    public function isStyle(): bool
    {
        return $this->is(ChunkType::Style);
    }

    public function type(): ChunkType
    {
        return ChunkType::from($this->extension());
    }

    public function id(): string
    {
        return $this->id;
    }

    public function file(): string
    {
        return $this->get('file');
    }

    public function path(): string
    {
        return $this->manifest->base() . '/' . $this->file();
    }

    public function mime(): ?string
    {
        return Mime::type($this->path());
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

    public function source(): ?string
    {
        $path = $this->get('src');

        if (!$path) {
            return null;
        }

        return App::instance()->roots()->base() . '/' . Str::ltrim($path, '/');
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

    public function assets(): ChunkCollection
    {
        return $this->findChunksBy('file', 'in', $this->get('assets', []));
    }

    public function styles(): ChunkCollection
    {
        return $this->findChunksBy('file', 'in', $this->get('css', []));
    }

    public function imports(): ChunkCollection
    {
        return $this->findChunksBy('id', 'in', $this->get('imports', []));
    }

    public function content(): string|false
    {
        return F::read($this->path());
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function toString(): string
    {
        return $this->url();
    }

    public function getIterator(): RecursiveChunkIterator
    {
        return new RecursiveChunkIterator($this);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function __call(string $name, array $parameters = []): mixed
    {
        return $this->get($name);
    }

    protected function findChunksBy(string $key, string $operator, mixed $value): ChunkCollection
    {
        return $this->manifest->chunks()->filter($key, $operator, $value);
    }
}
