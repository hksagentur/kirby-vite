<?php

namespace Hks\Vite;

use ArgumentCountError;
use Closure;
use Kirby\Toolkit\Collection;

class ChunkCollection extends Collection
{
    public function __construct(array $data = [])
    {
        parent::__construct(data: $data, caseSensitive: true);
    }

    public function implode(string|callable|null $value, ?string $glue = null): string
    {
        if (is_callable($value)) {
            return implode($glue ?? '', $this->values($value));
        }

        $item = $this->first();

        if (is_array($item) || is_object($item)) {
            return implode($glue ?? '', $this->pluck($value));
        }

        return implode($value ?? '', $this->data);
    }

    public function join(?string $glue = null, ?Closure $as = null): string
    {
        return $this->implode($as, $glue);
    }

    public function collapse(): static
    {
        $results = [];

        foreach ($this->data as $values) {
            $values = $values instanceof Collection ? $values->data() : $values;

            if (! is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return new static(array_merge([], ...$results));
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

    public function flatMap(callable $callback): static
    {
        return $this
            ->clone()
            ->map($callback)
            ->collapse();
    }

    public function toArray(?Closure $callback = null): array
    {
        return parent::toArray($callback ?? function (mixed $item): mixed {
            return $item instanceof Chunk ? $item->toArray() : $item;
        });
    }
}
