<?php

namespace Hks\Vite;

use ArgumentCountError;
use Closure;
use Kirby\Toolkit\Collection;

class ChunkCollection extends Collection
{
    /**
     * Create a new instance of the ChunkCollection class.
     *
     * @param array $data A collection of chunks.
     */
    public function __construct(array $data = [])
    {
        parent::__construct(data: $data, caseSensitive: true);
	}

    /**
     * Concatenate values of a given key as a string.
     *
     * @param string|callable|null $value The value to concatenate.
     * @param null|string $glue The string to use as glue.
     * @return string
     */
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

    /**
     * Join all items from the collection using a string.
     *
     * @param string|null $glue The string to use as glue.
     * @return string
     */
    public function join(?string $glue = null): string
    {
        return $this->implode($glue);
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return static
     */
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

    /**
	 * Map a function to each element of the collection.
	 *
	 * @return $this
	 */
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

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @param callable $callback The callback function.
     * @return static
     */
    public function flatMap(callable $callback): static
    {
        return $this->map($callback)->collapse();
    }

    /**
     * Convert the collection to an array representation.
     *
     * @param \Closure|null $callback An optional callback function.
     * @return array
     */
    public function toArray(?Closure $callback = null): array
    {
        return parent::toArray($callback ?? function (mixed $item): mixed {
            return $item instanceof Chunk ? $item->toArray() : $item;
        });
    }
}
