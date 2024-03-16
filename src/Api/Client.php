<?php

namespace STS\HubSpot\Api;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\ForwardsCalls;
use STS\HubSpot\Exceptions\InvalidRequestException;
use STS\HubSpot\Exceptions\MethodNotAllowedException;
use STS\HubSpot\Exceptions\NotFoundException;
use STS\HubSpot\Exceptions\RateLimitException;

/**
 * @mixin PendingRequest
 */
class Client
{
    use ForwardsCalls;

    public function __construct(
        public readonly string $baseUrl,
        private readonly string $accessToken
    ) {
        //
    }

    public function prefix($uri): string
    {
        return $this->baseUrl.$uri;
    }

    public function http(): PendingRequest
    {
        return Http::timeout(config('hubspot.http.timeout', 10))
            ->connectTimeout(config('hubspot.http.connect_timeout', 10))
            ->withToken($this->accessToken)
            ->throw(function (Response $response, RequestException $exception) {
                if ($response->json('category') === 'RATE_LIMITS') {
                    throw new RateLimitException($response, $exception);
                }

                match ($response->status()) {
                    400 => throw new InvalidRequestException($response->json('message'), 400),
                    404 => throw new NotFoundException($response, $exception),
                    409 => throw new InvalidRequestException($response->json('message'), 409),
                    405 => throw new MethodNotAllowedException($response),
                    default => $this->handleDefaultException($response, $exception),
                };
            });
    }

    public function get(string $uri, $query = []): Response
    {
        return $this->http()->get(
            $this->prefix($uri),
            array_filter($query)
        );
    }

    public function post(string $uri, array $data = []): Response
    {
        return $this->http()->post(
            $this->prefix($uri),
            array_filter($data)
        );
    }

    public function patch(string $uri, array $data = []): Response
    {
        return $this->http()->patch(
            $this->prefix($uri),
            $data
        );
    }

    public function put(string $uri, array $data = []): Response
    {
        return $this->http()->put(
            $this->prefix($uri),
            $data
        );
    }

    public function delete(string $uri, array $data = []): Response
    {
        return $this->http()->delete(
            $this->prefix($uri),
            $data
        );
    }

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->http(), $method, $parameters);
    }

    /**
     * @throws Exception
     */
    private function handleDefaultException(Response $response, RequestException $exception)
    {
        Log::error(
            'Unhandled exception from HubSpot API',
            [
                'status' => $response->status(),
                'response' => $response->body(),
                'exception' => $exception,
            ]
        );

        throw new Exception(
            $response->body(),
            $response->status(),
            $exception
        );
    }
}
