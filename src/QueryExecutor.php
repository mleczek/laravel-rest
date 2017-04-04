<?php


namespace Mleczek\Rest;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

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

    /**
     * @param Builder|Relation $query
     * @return Model
     */
    public function item($query)
    {
        $builder = $this->builder->make($query)
            ->fields($this->request->fields());

        $this->includeRelations($builder);
        return $builder->getItem();
    }

    /**
     * @param Builder|Relation $query
     * @return object
     */
    public function collection($query)
    {
        $builder = $this->builder->make($query)
            ->sort($this->request->sort())
            ->filter($this->request->filters())
            ->fields($this->request->fields())
            ->offset($this->request->offset())
            ->limit($this->request->limit());

        $this->includeRelations($builder);
        $entities = $builder->getCollection();

        // TODO: Throw an exception (404 not found) if: count==0, offset!=0 and limit>0?

        return (object)[
            'count' => $entities->count(),
            'limit' => $this->request->limit(),
            'total' => $builder->getQuery()->count(),
            'offset' => $this->request->offset(),
            'data' => $entities,
        ];
    }
}
