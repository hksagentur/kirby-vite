<?php

namespace Hks\Vite;

use Kirby\Cms\App;
use Kirby\Filesystem\F;
use Kirby\Http\Url;

class Server
{
    /**
     * Create a new instance of the Server class.
     *
     * @param string $host The host of the development server.
     * @param int|string $port The port of the development server.
     */
    public function __construct(
        protected string $host = 'locahost',
        protected int|string $port = 5173
    ) {
    }

    /**
     * Determine whetehr the development server is running.
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return F::exists(App::instance()->roots()->cache() . '/.vite-server');
    }

    /**
     * Get the hostname of the development server.
     *
     * @return string
     */
    public function host(): string
    {
        return $this->host();
    }

    /**
     * Get the port of the development server.
     *
     * @return string
     */
    public function port(): int|string
    {
        return $this->port;
    }

    /**
     * Get the url of the development server.
     *
     * @return string
     */
    public function url(): string
    {
        return Url::scheme() . '://' . $this->host . ':' . $this->port;
    }
}
