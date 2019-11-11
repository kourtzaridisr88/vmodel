<?php

namespace VModel;

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Request;

class ResponseParser
{
    private $reference;
    public function __construct() { }

    public function parse($data, $reference)
    {   
        $this->reference = $reference;

        if(isset($data['links'])) {
            return $this->parsePagination($data);
        }

        if(isset($data['data'][0]) && is_array($data['data'][0])) {
            return $this->parseCollection($data['data']);
        }

        return $this->parseModel($data['data']);
    }

    public function parsePagination($data) : LengthAwarePaginator
    {
        $collection = $this->parseCollection($data['data']);
        $path = Request::segment(1);
        $options = [
            'path' => $path
        ];
        return new LengthAwarePaginator(
            $collection, 
            $data['meta']['total'], 
            $data['meta']['per_page'], 
            $data['meta']['current_page'],
            $options
        );
    }

    /**
     * Parses a collection of models as fetched from API.
     *
     * @param   array   $array
     * @return  Collection
     */
    public function parseCollection(array $array) : Collection
    {
        $result = collect();

        foreach($array as $data){
            $result->push(new VModel($data));
        }
        return $result;
    }

    /**
     * @param   array   $api_data   A model as fetched from API
     */
    public function parseModel(array $array) : VModel{
        return new $this->reference($array);
    }
}