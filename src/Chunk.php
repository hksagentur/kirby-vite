<?php

namespace Hks\Vite;

use Exception;
use Kirby\Cms\App;
use Kirby\Cms\Url;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

class Chunk
{
    public function __construct(
        protected Manifest $manifest,
        protected string $id,
        protected array $data
    ) {
        if (empty($data['file'])) {
            throw new Exception('The chunk is missing a valid output path.');
        }

        $this->manifest = $manifest;
        $this->id = $id;
        $this->data = $data;
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
        return $this->findChunkBy('file', $this->get('assets', []));
    }

    public function styles(): ChunkCollection
    {
        return $this->findChunkBy('file', $this->get('css', []));
    }

    public function imports(): ChunkCollection
    {
        return $this->findChunkBy('id', $this->get('imports', []));
    }

    public function dependencies(): ChunkCollection
    {
        $discoveredImports = [];

        $stack = [
            ...$this->imports(),
        ];

        while (! empty($stack)) {
            $chunk = array_pop($stack);

            if (isset($discoveredImports[$chunk->id()])) {
                continue;
            }

            $discoveredImports[$chunk->id()] = $chunk;

            foreach ($chunk->imports() as $import) {
                if (! isset($discoveredImports[$import->id()])) {
                    $stack[] = $import;
                }
            }
        }

        return new ChunkCollection($discoveredImports);
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

    public function __toString(): string
    {
        return $this->toString();
    }

    public function __call(string $name, array $parameters = []): mixed
    {
        return $this->get($name);
    }

    protected function findChunkBy(string $key, array $files): ChunkCollection
    {
        return $this->manifest->chunks()->filter($key, 'in', $files);
    }
}
