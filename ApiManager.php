<?php

namespace App\Helpers\VModel;

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiManager
{
    private $data;
    private $client;

    public function __construct()
    {
        $this->boot();
    }

    private function boot()
    {
        $this->client = new Client();
    }

    public function call(string $method, string $endpoint, ?array $data = [])
    {
        switch($method) {
            case 'GET':
                $this->endpoint = empty($data) 
                    ? $endpoint 
                    : $endpoint . '?' . http_build_query($data);
                $results = $this->get();
                break;
            case 'POST':
                $this->endpoint = $endpoint;
                $this->data = $data;
                $results = $this->post();
                break;
            case 'PUT':
                $this->endpoint = $endpoint;
                $this->data = $data;
                $results = $this->put();
                break;
            case 'DELETE':
                $this->endpoint = $endpoint;
                $results = $this->delete();
                break; 
            default: 
                throw new Exception("Unsupported REST Method");
        }

        return $results;
    }

    protected function buildQueryString()
    {
        return empty($this->data) 
            ? $this->endpoint 
            : $this->endpoint . '?' . http_build_query($this->query);
    }

    private function get()
    {
        try {
            $response = $this->client->get($this->endpoint, $this->getOptions())
                ->getBody()
                ->getContents();

            return json_decode($response, true);
        } catch(GuzzleException $error) {
            return $this->errorHandler($error);
        }
    }

    public function post()
    {
        try {
            $response = $this->client->post($this->endpoint, $this->getOptions())
                ->getBody()
                ->getContents();
            
            return json_decode($response, true);
        } catch(GuzzleException $error) {
            return $this->errorHandler($error);
        }
    }

    public function put() : ReturnInterface
    {
        try {
            $response = $this->client->put($this->endpoint, $this->getOptions())
                ->getBody()
                ->getContents();

            return new ActionSussess();
        } catch(GuzzleException $error) {
            return $this->errorHandler($error);
        }
    }

    public function delete() : ReturnInterface
    {
        try {
            $response = $this->client->delete($this->endpoint, $this->getOptions())
                ->getStatusCode();

            return new ActionSussess();
        } catch(GuzzleException $error) {
            return $this->errorHandler($error);
        }
    }

    public function getOptions() : array
    {
        return [
            'json' => $this->data,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . session('api_token')
            ]
        ];
    }

    private function errorHandler($error) : ?UnprocessableEntity
    {
        $code = $error->getResponse()->getStatusCode();

        switch($code) {
            case 401:
                dd('unauthorized');
                throw new EndpointNotFoundException('Endpoint Not Found Exception', $code);
                break;
            case 404:
                throw new EndpointNotFoundException('Endpoint Not Found Exception', $code);
                break;
            case 422:
                $body = $error->getResponse()->getBody()->getContents();
                return new UnprocessableEntity(json_decode($body, true));
                break;
            default:
                throw new ApiException('API Generic Exception', $code);
        }
    }
}