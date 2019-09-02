<?php

namespace Toper;

interface HostPoolProviderInterface
{
    /**
     * @return HostPoolInterface
     */
    public function get();

    /**
     * @return string
     */
    public function getServiceName();
}
