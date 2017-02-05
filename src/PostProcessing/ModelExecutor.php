<?php


namespace Mleczek\Rest\PostProcessing;


use Illuminate\Database\Eloquent\Model;
use Mleczek\Rest\QueryBuilderFactory;
use Mleczek\Rest\RequestParser;

class ModelExecutor
{
    /**
     * @var RequestParser
     */
    protected $request;

    /**
     * @var ModelBuilderFactory
     */
    protected $modelBuilder;

    /**
     * @var QueryBuilderFactory
     */
    private $queryBuilder;

    /**
     * QueryExecutor constructor.
     *
     * @param RequestParser $request
     * @param ModelBuilderFactory $builder
     * @param QueryBuilderFactory $queryBuilder
     */
    public function __construct(RequestParser $request, ModelBuilderFactory $builder, QueryBuilderFactory $queryBuilder)
    {
        $this->request = $request;
        $this->modelBuilder = $builder;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @param ModelBuilder $builder
     */
    public function includeRelations(ModelBuilder $builder)
    {
        foreach ($this->request->with() as $relation_name) {
            $callback = function ($query, $prefix) {
                return $this->queryBuilder->make($query)
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
     * @param Model $model
     * @return mixed
     */
    public function item(Model $model)
    {
        $builder = $this->modelBuilder->make($model)
            ->fields($this->request->fields());

        $this->includeRelations($builder);
        return $builder->getItem();
    }
}