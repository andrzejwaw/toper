<?php

namespace Toper;

use Guzzle\Http\ClientInterface as GuzzleClientInterface;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\Message\Request as GuzzleRequest;
use Toper\Exception\ConnectionErrorException;
use Toper\Exception\EmptyHostPoolException;
use Toper\Exception\ServerErrorException;

class Request
{
    const GET = "get";
    const POST = "post";
    const PATCH = "patch";
    const PUT = "put";
    const DELETE = "delete";

    /**
     * @var HostPoolInterface
     */
    private $hostPool;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $method;

    /**
     * @var GuzzleClientInterface
     */
    private $guzzleClient;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $queryParams = array();

    /**
     * @var array
     */
    private $binds;

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var MetricsInterface
     */
    private $metrics = array();

    /**
     * @var bool
     */
    private $proxy;

    /**
     * @param string $method
     * @param string $url
     * @param array $binds
     * @param HostPoolInterface $hostPool
     * @param GuzzleClientInterface $guzzleClient
     * @param MetricsInterface $metrics
     * @param bool $proxy
     */
    public function __construct(
        $method,
        $url,
        array $binds,
        HostPoolInterface $hostPool,
        GuzzleClientInterface $guzzleClient,
        MetricsInterface $metrics = null,
        $proxy = false

    ) {
        $this->method = $method;
        $this->url = $url;
        $this->binds = $binds;
        $this->hostPool = $hostPool;
        $this->guzzleClient = $guzzleClient;
        $this->metrics = $metrics;
        $this->proxy = $proxy;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return HostPoolInterface
     */
    public function getHostPool()
    {
        return $this->hostPool;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $body
     *
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return Response
     * @throws ConnectionErrorException
     * @throws EmptyHostPoolException
     * @throws ServerErrorException
     */
    public function send()
    {
        $exception = null;

        while ($this->hostPool->hasNext()) {
            try {

                $baseUrl = $this->hostPool->getNext();

                $this->guzzleClient->setBaseUrl($baseUrl);

                /** @var GuzzleRequest $guzzleRequest */
                $guzzleRequest = $this->guzzleClient->{$this->method}(
                    array($this->url, $this->binds)
                );

                $this->debug(sprintf("hostPoolName: %s, BaseUrl %s, url: %s, method: %s, this->headers: %s, guzzle->headers: %s",
                        $this->hostPool->getName(),
                        $baseUrl,
                        $this->url,
                        $this->method,
                        var_export($this->headers, true),
                        var_export($guzzleRequest->getHeaders(), true)
                    )
                );

                if (!is_null($this->proxy)) {
                    $this->debug("Proxy is false null hurrey");
                } else {
                    $this->debug("Proxy is true :( will addheaders whatever");
                }

                $guzzleRequest->addHeaders($this->headers);
                if ($this->body && $guzzleRequest instanceof EntityEnclosingRequest) {
                    /** @var EntityEnclosingRequest $guzzleRequest */
                    $guzzleRequest->setBody($this->body);
                }

                $this->updateQueryParams($guzzleRequest);

                $this->measure(sprintf("topper.request.%s.count", $this->method), $this->url);

                return new Response($guzzleRequest->send());
            } catch (ClientErrorResponseException $e) {
                return new Response($e->getResponse());
            } catch (ServerErrorResponseException $e) {
                $exception = new ServerErrorException(
                    new Response($e->getResponse()),
                    $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            } catch (CurlException $e) {
                $exception = new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
            }
        }

        if ($exception == null) {
            throw new EmptyHostPoolException(sprintf("Empty host pool: %s", $this->hostPool->getName()));
        }

        throw $exception;
    }

    /**
     * @return array
     */
    public function getBinds()
    {
        return $this->binds;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function addQueryParam($name, $value)
    {
        $this->queryParams[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name][] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return bool
     */
    public function isProxy()
    {
        return $this->proxy;
    }

    /**
     * @param GuzzleRequest $request
     */
    private function updateQueryParams(GuzzleRequest $request)
    {
        foreach ($this->queryParams as $name => $value) {
            $request->getQuery()->add($name, $value);
        }
    }

    private function measure($metric, $url) {
        $this->debug(" measuring metric $metric for $url");
        if ($this->metrics != null) {
            $this->debug("metrics not null - SENDING $metric");
            $this->metrics->increment($metric);
        } else {
            $this->debug(" metricsClass is null - :( $metric");
        }
    }

    private function debug($message) {
        file_put_contents("/var/log/allegro/statsd.log", "\n".date("Ymd H:i:s")." $message", FILE_APPEND);
    }
}
