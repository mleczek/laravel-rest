<?php


namespace Mleczek\Rest;


use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Context;

class ResponseFactoryMacros
{
    /**
     * @var ResponseFactory
     */
    protected $response;

    /**
     * @var RequestParser
     */
    protected $request;

    /**
     * @var QueryBuilderFactory
     */
    protected $builder;

    /**
     * Macros constructor.
     *
     * @param ResponseFactory $response
     * @param RequestParser $request
     * @param QueryBuilderFactory $builder
     */
    public function __construct(ResponseFactory $response, RequestParser $request, QueryBuilderFactory $builder)
    {
        $this->response = $response;
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
                return $this->builder->get($query)
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
        $this->request->refresh();
        $builder = $this->builder->get($query)
            ->fields($this->request->fields());

        $this->includeRelations($builder);
        $entity = $builder->getItem();

        return $this->response->json($entity, 200);
    }

    public function collection(Builder $query)
    {
        $builder = $this->builder->get($query)
            ->sort($this->request->sort())
            ->filter($this->request->filters())
            ->fields($this->request->fields())
            ->offset($this->request->offset())
            ->limit($this->request->limit());

        $this->includeRelations($builder);
        $entities = $builder->getCollection();

        $json = [
            'count' => $entities->count(),
            'limit' => $this->request->limit(),
            'offset' => $this->request->offset(),
            'data' => $entities,
        ];

        return $this->response->json($json, 206);
    }

    public function accepted()
    {
        return $this->response->make('', 202);
    }

    public function noContent()
    {
        return $this->response->make('', 204);
    }

    public function created(Model $model, $location = null)
    {
        $response = $this->response->json($model, 201);

        if(!is_null($location)) {
            $response->withHeaders(['Location' => $location]);
        }

        return $response;
    }

    public function updated()
    {
        return $this->response->make('', 200);
    }

    public function patched()
    {
        return $this->response->make('', 200);
    }

    public function deleted()
    {
        return $this->noContent();
    }
}