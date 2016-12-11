<?php


namespace Mleczek\Rest;


use Illuminate\Database\Eloquent\Builder;

class QueryBuilderFactory
{
    /**
     * @var RequestParser
     */
    protected $request;

    /**
     * @var ContextRepository
     */
    protected $context;

    /**
     * QueryBuilderFactory constructor.
     *
     * @param ContextRepository $context
     */
    public function __construct(ContextRepository $context)
    {
        $this->context = $context;
    }

    public function get($query)
    {
        return new QueryBuilder($query, $this->context);
    }
}