<?php


namespace Mleczek\Rest;


use Illuminate\Database\Eloquent\Builder;

class QueryExecutor
{
    /**
     * @var RequestParser
     */
    protected $request;

    /**
     * @var QueryBuilderFactory
     */
    protected $builder;

    /**
     * QueryExecutor constructor.
     *
     * @param RequestParser $request
     * @param QueryBuilderFactory $builder
     */
    public function __construct(RequestParser $request, QueryBuilderFactory $builder)
    {
        $this->request = $request;
        $this->builder = $builder;
    }

    /**
     * @param QueryBuilder $builder
     */
    public function includeRelations(QueryBuilder $builder)
    {
        foreach ($this->request->with() as $relation_name) {
            $callback = function ($query, $prefix) {
                return $this->builder->make($query)
                    ->sort($this->request->sort($prefix))
                    ->filter($this->request->filters($prefix))
                    ->fields($this->request->fields($prefix))
                    ->offset($this->request->offset($prefix))
                    ->limit($this->request->limit($prefix));
            };

            $builder->with($relation_name, $callback);
        }
    }

    public function item(Builder $query)
    {
        $builder = $this->builder->make($query)
            ->fields($this->request->fields());

        $this->includeRelations($builder);
        return $builder->getItem();
    }

    public function collection(Builder $query)
    {
        $builder = $this->builder->make($query)
            ->sort($this->request->sort())
            ->filter($this->request->filters())
            ->fields($this->request->fields())
            ->offset($this->request->offset())
            ->limit($this->request->limit());

        $this->includeRelations($builder);
        $entities = $builder->getCollection();

        return (object)[
            'count' => $entities->count(),
            'limit' => $this->request->limit(),
            'offset' => $this->request->offset(),
            'data' => $entities,
        ];
    }
}