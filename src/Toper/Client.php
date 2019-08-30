<?php

namespace Toper;

class Client implements ClientInterface
{
    /**
     * @var HostPoolProviderInterface
     */
    private $hostPoolProvider;

    /**
     * @var GuzzleClientFactoryInterface
     */
    private $guzzleClientFactory;

    /**
     * @var MetricsInterface
     */
    private $metrics;

    /**
     * @var bool
     */
    private $proxy;

    /**
     * @param HostPoolProviderInterface     $hostPoolProvider
     * @param GuzzleClientFactoryInterface  $guzzleClientFactory
     * @param MetricsInterface|null         $metrics
     * @param bool                          $proxy
     */
    public function __construct(
        HostPoolProviderInterface $hostPoolProvider,
        GuzzleClientFactoryInterface $guzzleClientFactory,
        MetricsInterface $metrics = null,
        $proxy = false
    ) {
        $this->hostPoolProvider = $hostPoolProvider;
        $this->guzzleClientFactory = $guzzleClientFactory;
        $this->metrics = $metrics;
        $this->proxy = $proxy;
    }

    /**
     * @param string $url
     * @param array  $binds
     *
     * @return Request
     */
    public function get($url, array $binds = array())
    {
        return new Request(
            Request::GET,
            $url,
            $binds,
            $this->hostPoolProvider->get(),
            $this->guzzleClientFactory->create(),
            $this->metrics,
            $this->proxy
        );
    }

    /**
     * @param string $url
     * @param array  $binds
     *
     * @return Request
     */
    public function post($url, array $binds = array())
    {
        return new Request(
            Request::POST,
            $url,
            $binds,
            $this->hostPoolProvider->get(),
            $this->guzzleClientFactory->create(),
            $this->metrics,
            $this->proxy
        );
    }

    /**
     * @param string $url
     * @param array  $binds
     *
     * @return Request
     */
    public function patch($url, array $binds = array())
    {
        return new Request(
            Request::PATCH,
            $url,
            $binds,
            $this->hostPoolProvider->get(),
            $this->guzzleClientFactory->create(),
            $this->metrics,
            $this->proxy
        );
    }

    /**
     * @param string $url
     * @param array  $binds
     *
     * @return Request
     */
    public function put($url, array $binds = array())
    {
        return new Request(
            Request::PUT,
            $url,
            $binds,
            $this->hostPoolProvider->get(),
            $this->guzzleClientFactory->create(),
            $this->metrics,
            $this->proxy
        );
    }

    /**
     * @param string $url
     * @param array  $binds
     *
     * @return Request
     */
    public function delete($url, array $binds = array())
    {
        return new Request(
            Request::DELETE,
            $url,
            $binds,
            $this->hostPoolProvider->get(),
            $this->guzzleClientFactory->create(),
            $this->metrics,
            $this->proxy
        );
    }
}
