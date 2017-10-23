<?php

namespace App\Filters;

use Illuminate\Http\Request;

abstract class Filters
{
    protected $request, $builder;
    protected $filters = [];
    /**
     * Filters constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param $builder
     * @return mixed
     */
    public function apply($builder)
    {
        $this->builder = $builder;

        collect($this->getFilters())
            ->filter(function ($filter){
                return method_exists($this, $filter);
            })
            ->each(function ($filter, $value){
                $this->$filter($value);
            });

        return $this->builder;

    }

    public function getFilters(){
        return collect($this->request->only($this->filters))->flip();
    }

}