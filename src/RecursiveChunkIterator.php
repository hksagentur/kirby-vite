<?php

namespace Hks\Vite;

use Generator;
use IteratorAggregate;
use WeakMap;

/** @implements IteratorAggregate<string, Chunk> */
class RecursiveChunkIterator implements IteratorAggregate
{
    public const int LEAVES_ONLY = 0;
    public const int SELF_FIRST = 1;
    public const int CHILD_FIRST = 2;

    public function __construct(
        protected Chunk $chunk,
        protected int $mode = self::SELF_FIRST,
        protected ?WeakMap $discoveredDependencies = null
    ) {
        $this->discoveredDependencies ??= new WeakMap();
    }

    /** @return Generator<string, Chunk> */
    public function getIterator(): Generator
    {
        if (isset($this->discoveredDependencies[$this->chunk])) {
            return;
        }

        $this->discoveredDependencies[$this->chunk] = true;

        if ($this->mode === self::SELF_FIRST) {
            yield $this->chunk->id() => $this->chunk;
        }

        $imports = $this->chunk->imports();

        if ($this->mode === self::LEAVES_ONLY && $imports->isEmpty()) {
            yield $this->chunk->id() => $this->chunk;
        } else {
            foreach ($imports as $import) {
                yield from new static($import, $this->mode, $this->discoveredDependencies);
            }
        }

        if ($this->mode === self::CHILD_FIRST) {
            yield $this->chunk->id() => $this->chunk;
        }
    }
}
