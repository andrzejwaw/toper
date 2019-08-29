<?php

namespace Toper;

interface MetricsInterface
{
    /**
     * @return string
     */
    public function increment($metric);
}
