<?php

namespace Hks\Vite;

use Kirby\Cms\Html;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;
use Kirby\Http\Url;

class Generator
{
    /**
     * The crossorigin settings to use.
     *
     * @var string|null
     */
    protected ?string $crossorigin = null;

    /**
     * The nonce to apply to the generated html tags.
     *
     * @var string|null
     */
    protected ?string $nonce = null;

    /**
     * Create a new instance of the Renderer class.
     */
    public function __construct(?string $nonce = null)
    {
        $this->nonce = $nonce;
    }

    /**
     * Allow cross-origin requests for generated assets.
     *
     * @param string $strategy The strategy to use.
     * @return static
     */
    public function allowCrossoriginRequests(string $stategy = 'anonymous'): static
    {
        $this->crossorigin = $stategy;

        return $this;
    }

    /**
     * Add a Content Security Policy nonce to generated tags.
     *
     * @param string|null $nonce The nonce to add.
     * @return static
     */
    public function useCspNonce(?string $nonce = null): static
    {
        $this->nonce = $nonce ?? Str::random(40);

        return $this;
    }

    /**
     * Generate the HTML tags for the vite client.
     *
     * @param string[] $entryPoints The entry points of the bundle.
     * @param string $host The host of the development server.
     * @param int|string $port The port of the development server.
     * @return string[]
     */
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

    /**
     * Generate the HTML tags for the given manifest.
     *
     * @param \Hks\Vite\Manifest $manifest The manifest to generate the tags for.
     * @param string[] $entryPoints Limit the collection of entry points to to include.
     * @return string[]
     */
    public function generateTagsForManifest(Manifest $manifest, array $entryPoints = []): array
    {
        $chunks = $manifest->entries();

        if (! empty($entryPoints)) {
            $chunks = $chunks->filter('id', 'in', $entryPoints);
        }

        return $chunks->flatMap(fn (Chunk $chunk) => [
            ...$this->generatePreloadTagsForChunk($chunk),
            ...$this->generateTagsForChunk($chunk),
        ])->toArray();
    }

    /**
     * Generate the HTML tags for the given chunk.
     *
     * @param \Hks\Vite\Chunk $chunk The chunk to generate the tags for.
     * @return string[]
     */
    public function generateTagsForChunk(Chunk $chunk): array
    {
        return match ($chunk->type()) {
            ChunkType::Style => [
                $this->generateStyleTagForChunk($chunk),
            ],
            default => [
                ...$chunk->styles()->map($this->generateStyleTagForChunk(...)),
                $this->generateScriptTagForChunk($chunk),
            ],
        };
    }

    /**
     * Generate the preload tag for a given chunk and all of its assets.
     *
     * @param \Hks\Vite\Chunk $chunk The chunk to generate the tags for.
     * @return string[]
     */
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
                ...$chunk->styles()->flatMap($this->generatePreloadTagsForChunk(...)),
                ...$chunk->imports()->flatMap($this->generatePreloadTagsForChunk(...)),
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

    /**
     * Generate a HTML style tag for a given chunk.
     *
     * @param \Hks\Vite\Chunk $chunk The chunk to generate the tag for.
     * @return string
     */
    protected function generateStyleTagForChunk(Chunk $chunk): string
    {
        return Html::css($chunk->url(), [
            'integrity' => $chunk->get('integrity'),
            'nonce' => $this->nonce,
        ]);
    }

    /**
     * Generate a HTML script tag for a given chunk.
     *
     * @param \Hks\Vite\Chunk $chunk The chunk to generate the tag for.
     * @return string
     */
    protected function generateScriptTagForChunk(Chunk $chunk): string
    {
        return Html::js($chunk->url(), [
            'type' => 'module',
            'integrity' => $chunk->get('integrity'),
            'nonce' => $this->nonce,
        ]);
    }
}
