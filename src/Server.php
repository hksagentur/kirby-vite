<?php

namespace Hks\Vite;

use Kirby\Cms\App;
use Kirby\Filesystem\F;
use Kirby\Http\Url;

class Server
{
    public function __construct(
        protected string $host = 'locahost',
        protected int|string $port = 5173
    ) {
    }

    public function isRunning(): bool
    {
        return F::exists(App::instance()->roots()->cache() . '/.vite-server');
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int|string
    {
        return $this->port;
    }

    public function url(): string
    {
        return Url::scheme() . '://' . $this->host . ':' . $this->port;
    }
}
