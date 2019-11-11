<?php

namespace VModel;

use Illuminate\Support\Collection;

class VModel implements ReturnInterface
{
    /**
     * @var App\Helpers\VModel;  
     */
    private $builder;

    public function __construct(array $attributes = [])
    {
        $this->boot();
        $this->fill($attributes);
    }

    protected function boot()
    {
        $this->builder = new Builder($this, new ApiManager());
    }

    protected function fill($attributes = [])
    {
        foreach($attributes as $key => $attribute) {
            $this->$key = $attribute;
        }
    }

    public function __call($method, $parameters)
    {
        return $this->builder->$method(...$parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->builder->mapToClass(get_called_class())->$method(...$parameters);
    }
}
