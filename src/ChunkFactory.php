<?php

namespace Hks\Vite;

class ChunkFactory
{
    /**
     * Create a new chunk.
     *
     * @param \Hks\Vite\Manifest $manifest The manifest the chunk belongs to.
     * @param string
     * @param array $data The raw chunk data.
     * @return \Hks\Vite\Chunk
     */
    public static function create(Manifest $manifest, string $file, array $data): Chunk
    {
        return new Chunk($manifest, $file, $data);
    }

    /**
     * Create a new chunk collection.
     *
     * @param \Hks\Vite\Manifest $manifest The manifest the chunk belongs to.
     * @param array $chunks A collection of chunks.
     * @return \Hks\Vite\ChunkCollection
     */
    public static function collection(Manifest $manifest, array $chunks = []): ChunkCollection
    {
        return (new ChunkCollection($chunks))->map(
            fn (array $data, string $id) => static::create($manifest, $id, $data)
        );
    }
}
