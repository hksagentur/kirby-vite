<?php

namespace Hks\Vite;

use Kirby\Cms\Html;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;
use Kirby\Http\Url;
use Kirby\Toolkit\A;

/**
 * @see {@link https://vite.dev/guide/backend-integration Backend Integration}
 * @see {@link https://spiriitlabs.github.io/vite-plugin-svg-spritemap/guide/backend-integration.html Backend Integration}
 */
class Generator
{
    public function __construct(
        protected ?string $nonce = null,
        protected ?string $crossorigin = null,
    ) {
    }

    public function allowCrossoriginRequests(string $stategy = 'anonymous'): static
    {
        $this->crossorigin = $stategy;

        return $this;
    }

    public function useCspNonce(?string $nonce = null): static
    {
        $this->nonce = $nonce ?? Str::random(40);

        return $this;
    }

    public function generateTagsForViteClient(ChunkCollection $entryPoints, array $plugins = [], string $host = 'localhost', int|string $port = 5173): array
    {
        $elements = [];

        foreach (array_merge(['@vite/client', ...$plugins], $entryPoints->pluck('id')) as $file) {
            $elements[] = match (F::extension($file)) {
                'css' => Html::css(Url::scheme() . "://{$host}:{$port}/{$file}", [
                    'nonce' => $this->nonce,
                ]),
                default => Html::js(Url::scheme() . "://{$host}:{$port}/{$file}", [
                    'type' => 'module',
                    'nonce' => $this->nonce,
                ]),
            };
        }

        return $elements;
    }

    public function generateTagsForEntryPoints(ChunkCollection $entryPoints): array
    {
        $tags = [
            'styles' => [],
            'scripts' => [],
            'modulepreloads' => [],
        ];

        foreach ($entryPoints as $entryPoint) {
            foreach ($entryPoint as $chunk) {
                if ($chunk->isEntry() && $chunk->type() === ChunkType::Style) {
                    // 1. Generate a style tag for CSS entry points.
                    $tags = A::merge($tags, [
                        'styles' => [
                            $this->generateStyleTagForChunk($chunk),
                        ],
                    ]);
                } elseif ($chunk->isEntry() && $chunk->type() === ChunkType::Script) {
                    // 2: Generate style tags for each CSS file of the JavaScript module.
                    // 3. Generate a script tag for the JavaScript module.
                    $tags = A::merge($tags, [
                        'styles' => [
                            ...$entryPoint->styles()->map($this->generateStyleTagForChunk(...)),
                        ],
                        'scripts' => [
                            $this->generateScriptTagForChunk($entryPoint),
                        ],
                    ]);
                } elseif ($chunk->type() === ChunkType::Script) {
                    // 4. Generate style tags for all CSS files of the imported chunk.
                    // 5. Generate a modulepreload tag for each imported chunk.
                    $tags = A::merge($tags, [
                        'styles' => [
                            ...$chunk->styles()->map($this->generateStyleTagForChunk(...))
                        ],
                        'modulepreloads' => [
                            $this->generateModulePreloadTagForChunk($chunk),
                        ],
                    ]);
                }
            }
        }

        return array_reduce($tags, array_merge(...), []);
    }

    public function generateStyleTagForChunk(Chunk $chunk): string
    {
        return Html::css($chunk->url(), [
            'integrity' => $chunk->get('integrity'),
            'nonce' => $this->nonce,
        ]);
    }

    public function generateScriptTagForChunk(Chunk $chunk): string
    {
        return Html::js($chunk->url(), [
            'type' => 'module',
            'integrity' => $chunk->get('integrity'),
            'nonce' => $this->nonce,
        ]);
    }

    public function generateModulePreloadTagForChunk(Chunk $chunk): string
    {
        return Html::tag('link', attr: [
            'rel' => 'modulepreload',
            'href' => $chunk->url(),
            'integrity' => $chunk->get('integrity'),
            'nonce' => $this->nonce,
            'crossorigin' => $this->crossorigin,
        ]);
    }

    public function generatePreloadTagForChunk(Chunk $chunk): string
    {
        return Html::tag('link', attr: [
            'rel' => 'preload',
            'href' => $chunk->url(),
            'integrity' => $chunk->get('integrity'),
            'nonce' => $this->nonce,
            'crossorigin' => $this->crossorigin,
            ...match ($chunk->extension()) {
                'json' => [
                    'as' => 'fetch',
                    'crossorigin' => $this->crossorigin ?? 'anonymous',
                ],
                'woff', 'woff2' => [
                    'as' => 'font',
                    'type' => $chunk->mime(),
                    'crossorigin' => $this->crossorigin ?? 'anonymous',
                ],
                'avif', 'gif', 'jpg', 'jpeg', 'png', 'svg', 'webp' => [
                    'as' => 'image',
                    'type' => $chunk->mime(),
                ],
                'css' => [
                    'as' => 'style',
                ],
                default => [
                    'as' => 'script',
                ],
            },
        ]);
    }
}
