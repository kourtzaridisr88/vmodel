<?php

namespace VModel;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Builder
{
    /**
     * The base endpoint to query e.g. api/products
     * 
     * @var string
     */
    private $base_endpoint;

    /**
     * Keep's the model instance to query
     * 
     * @var App\Helpers\VModel
     */
    private $model;

    /**
     * Keep's the api gate instance
     * 
     * @var App\Helpers\ApiManager
     */
    private $api;

    /**
     * The query data
     * 
     * @var array
     */
    private $query;

    /**
     * Holds The reference's to object to map to api results
     * 
     * @var array
     */
    private $reference;

    private $parser;

    /**
     * Create a new Builder instance.
     * 
     * @param App\Helpers\VModel
     * @param App\Helpers\ApiManager
     * @return void
     */
    public function __construct(VModel $model, ApiManager $api)
    {
        $this->model = $model;
        $this->parser = new ResponseParser();
        $this->api = $api;
        $this->base_endpoint = isset($this->model->endpoint) 
            ? env('API_URL') . $this->model->endpoint 
            : env('API_URL');
    }

    public function mapToClass($class) : Builder
    {   
        $this->reference = $class;
        return $this;
    }

    /**
     * Given an array of key value pairs add them to query string
     * 
     * @param array $data An array of key value pairs
     * @return Builder
     */
    public function setQuery(array $data) : Builder
    {   
        // dd($data);
        foreach($data as $key => $value) {
            if(is_array($key)) {
                $this->whereIn($key, $value);
            } else {
              $this->where($key, $value);  
            }
        }

        return $this;
    }

    /**
     * Adds a value to the query string.
     * eg api/endpoint?key=value
     * 
     * @param string $key The key to add to query string
     * @param string|int $value The value to add to query string
     * @return Builder
     */
    public function where(string $key, $value) : Builder
    {   
        $this->query[$key] = $value;

        return $this;
    }

    /**
     * Adds an array key values to the query string.
     * eg api/endpoint?key[]=value&key[]=value
     * 
     * @param string $key The key to add to query string
     * @param string|int $values The values to add to query string
     * @return Builder
     */
    public function whereIn(string $key, array $values) : Builder
    {   
        foreach($values as $value) {
            $this->query[$key][] = $value;
        }

        return $this;
    }

    /**
     * Adds a value to the query string.
     * 
     * @param int $page The page to fetch from the api
     * @param int $perPage How many results per page
     * @return LengthAwarePaginator
     */
    public function paginate(int $page, int $perPage = 15) : LengthAwarePaginator
    {
        $this->where('page', $page)->where('per_page', $perPage);
        
        $results = $this->api->call('GET', $this->base_endpoint, $this->query);

        return $this->parser->parse($results, $this->reference);
    }

    public function get(array $data = []) : Collection
    {
        $this->setQuery($data);

        $results = $this->api->call('GET', $this->base_endpoint, $this->query);

        return $this->parser->parse($results, $this->reference);
    }

    public function raw($callback)
    {
        $results = $callback($this);

        return $this->parser->parse($results, $this->reference);
    }

    public function find(string $id) : VModel
    {
        $this->setParam($id);

        $results = $this->api->call('GET', $this->base_endpoint, $this->query);

        return $this->parser->parse($results, $this->reference);
    }

    /**
     * Add a paramter to endpoint and calls delete on api.
     * 
     * @param string $id The id to as parameter.
     * @return LengthAwarePaginator
     */
    public function destroy(string $id) : ReturnInterface
    {
        $this->setParam($id);

        return $this->api->call('DELETE', $this->base_endpoint);
    }

    public function save(array $data) : ReturnInterface
    {
        $this->setQuery($data);

        $results = $this->api->call('POST', $this->base_endpoint, $this->query);
        
        if($results instanceof UnprocessableEntity) {
            return $results;
        }

        return $this->parser->parse($results, $this->reference);
    }

    public function update(string $id, array $data) : ReturnInterface
    {
        $this->setParam($id);
        $this->setQuery($data);

        return $this->api->call('PUT', $this->base_endpoint, $this->query);
    }

    /**
     * Add's param to base endpoint in order to call ApiManager
     * 
     * @param string|int Value to add as param to endpoint
     * @return void
     */
    public function setParam($param) : void
    {
        $this->base_endpoint .= '/' . $param;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getApi()
    {
        return $this->api;
    }

    public function getBaseEndpoint()
    {
        return $this->base_endpoint;
    }
}
