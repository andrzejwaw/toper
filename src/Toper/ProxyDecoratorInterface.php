<?php

namespace Toper;

use Guzzle\Http\Message\Request as GuzzleRequest;

interface ProxyDecoratorInterface
{
    /**
     * @param GuzzleRequest $request
     * @return string
     */
    public function decorate(GuzzleRequest $request);
}
