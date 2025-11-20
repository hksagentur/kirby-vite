<?php

namespace Hks\Vite;

use Kirby\Cms\Html;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;
use Kirby\Http\Url;

class Generator
{
    protected array $dependencies = [];

    protected ?string $crossorigin = null;

    public function __construct(
        protected ?string $nonce = null
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

    public function generateTagsForViteClient(array $entryPoints, string $host = 'localhost', int|string $port = 5173): array
    {
        $elements = [];

        foreach (array_merge(['@vite/client'], $entryPoints) as $file) {
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

    public function generateTagsForManifest(Manifest $manifest, array $entryPoints = []): array
    {
        $chunks = $manifest->entries();

        if (! empty($entryPoints)) {
            $chunks = $chunks->filter('id', 'in', $entryPoints);
        }

        return [
            ...$chunks->flatMap($this->generateTagsForChunk(...)),
            ...$chunks->flatMap($this->generatePreloadTagsForChunk(...)),
        ];
    }

    public function generateTagsForChunk(Chunk $chunk): array
    {
        return match ($chunk->type()) {
            ChunkType::Style => [
                $this->generateStyleTagForChunk($chunk),
            ],
            default => [
                ...$this->resolveDependencyTree($chunk)->flatMap(fn (Chunk $chunk) => [
                    ...$chunk->styles()->flatMap($this->generateStyleTagForChunk(...)),
                ]),
                $this->generateScriptTagForChunk($chunk),
            ],
        };
    }

    public function generatePreloadTagsForChunk(Chunk $chunk): array
    {
        return match ($chunk->type()) {
            ChunkType::Style => [
                Html::tag('link', attr: [
                    'rel' => 'preload',
                    'as' => 'style',
                    'href' => $chunk->url(),
                    'integrity' => $chunk->get('integrity'),
                    'nonce' => $this->nonce,
                    'crossorigin' => $this->crossorigin,
                ]),
            ],
            default => [
                ...$this->resolveDependencyTree($chunk)->flatMap(fn (Chunk $chunk) => [
                    ...$chunk->styles()->flatMap($this->generatePreloadTagsForChunk(...)),
                    ...$chunk->imports()->flatMap($this->generatePreloadTagsForChunk(...)),
                ]),
                Html::tag('link', attr: [
                    'rel' => 'modulepreload',
                    'href' => $chunk->url(),
                    'integrity' => $chunk->get('integrity'),
                    'nonce' => $this->nonce,
                    'crossorigin' => $this->crossorigin,
                ]),
            ],
        };
    }

    protected function generateStyleTagForChunk(Chunk $chunk): string
    {
        return Html::css($chunk->url(), [
            'integrity' => $chunk->get('integrity'),
            'nonce' => $this->nonce,
        ]);
    }

    protected function generateScriptTagForChunk(Chunk $chunk): string
    {
        return Html::js($chunk->url(), [
            'type' => 'module',
            'integrity' => $chunk->get('integrity'),
            'nonce' => $this->nonce,
        ]);
    }

    protected function resolveDependencyTree(Chunk $entryPoint): ChunkCollection
    {
        return $this->dependencies[$entryPoint->id()] ??= $entryPoint->dependencies();
    }
}
