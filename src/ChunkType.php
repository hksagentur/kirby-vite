<?php

namespace Hks\Vite;

enum ChunkType
{
    case Style;
    case Script;
    case Asset;

    public static function from(string $extension): static
    {
        return match ($extension) {
            'css' => self::Style,
            'js', 'mjs' => self::Script,
            default => self::Asset,
        };
    }
}
