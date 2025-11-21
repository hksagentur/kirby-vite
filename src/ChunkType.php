<?php

namespace Hks\Vite;

enum ChunkType
{
    case Asset;
    case Script;
    case Style;

    public static function from(string $extension): static
    {
        return match ($extension) {
            'css' => self::Style,
            'js', 'mjs' => self::Script,
            default => self::Asset,
        };
    }
}
