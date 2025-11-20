<?php

namespace Hks\Vite;

class ChunkFactory
{
    public static function create(Manifest $manifest, string $file, array $data): Chunk
    {
        return new Chunk($manifest, $file, $data);
    }

    public static function collection(Manifest $manifest, array $chunks = []): ChunkCollection
    {
        return (new ChunkCollection($chunks))->map(
            fn (array $data, string $id) => static::create($manifest, $id, $data)
        );
    }
}
