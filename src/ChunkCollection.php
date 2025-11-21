<?php

namespace Hks\Vite;

use ArgumentCountError;
use Closure;
use Kirby\Toolkit\Collection;

/** @extends Collection<Chunk> */
class ChunkCollection extends Collection
{
    public function __construct(array $data = [])
    {
        parent::__construct(data: $data, caseSensitive: true);
    }

    public function map(callable $callback): static
    {
        $keys = array_keys($this->data);

        try {
            $items = array_map($callback, $this->data, $keys);
        } catch (ArgumentCountError) {
            $items = array_map($callback, $this->data);
        }

        $this->data = array_combine($keys, $items);

        return $this;
    }

    public function whereKey(string|array $key): static
    {
        if (is_array($key)) {
            $this->filter('id', 'in', $key);
        }

        return $this->filter('id', '=', $key);
    }

    public function whereExtension(string|array $extension): static
    {
        if (is_array($extension)) {
            return $this->filter('extension', 'in', $extension);
        }

        return $this->filter('extension', '=', $extension);
    }

    public function whereType(ChunkType|array $type): static
    {
        if (is_array($type)) {
            return $this->filter('type', 'in', $type);
        }

        return $this->filter('type', '=', $type);
    }

    public function toArray(?Closure $callback = null): array
    {
        return parent::toArray($callback ?? function (mixed $item): mixed {
            return $item instanceof Chunk ? $item->toArray() : $item;
        });
    }
}
