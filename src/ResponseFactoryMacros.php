<?php


namespace Mleczek\Rest;


use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mleczek\Rest\PostProcessing\ModelExecutor;
use phpDocumentor\Reflection\Types\Context;

class ResponseFactoryMacros
{
    /**
     * @var ResponseFactory
     */
    protected $response;

    /**
     * @var QueryExecutor
     */
    protected $queryExecutor;

    /**
     * @var ModelExecutor
     */
    private $modelExecutor;

    /**
     * Macros constructor.
     *
     * @param ResponseFactory $response
     * @param QueryExecutor $queryExecutor
     * @param ModelExecutor $modelExecutor
     * @internal param RequestParser $request
     * @internal param QueryBuilderFactory $builder
     */
    public function __construct(ResponseFactory $response, QueryExecutor $queryExecutor, ModelExecutor $modelExecutor)
    {
        $this->response = $response;
        $this->queryExecutor = $queryExecutor;
        $this->modelExecutor = $modelExecutor;
    }

    public function item($data)
    {
        if ($data instanceof Builder || $data instanceof Relation) {
            $data = $this->queryExecutor->item($data);
        } else if ($data instanceof Model) {
            $data = $this->modelExecutor->item($data);
        }

        return $this->response->json($data, 200);
    }

    public function collection($data)
    {
        if ($data instanceof Builder || $data instanceof Relation) {
            $data = $this->queryExecutor->collection($data);
        }

        return $this->response->json($data, 206);
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

        if (!is_null($location)) {
            $response->withHeaders(['Location' => $location]);
        }

        return $response;
    }

    public function updated($model = null)
    {
        return $this->response->make($model, 200);
    }

    public function patched($model = null)
    {
        return $this->response->make($model, 200);
    }

    public function deleted()
    {
        return $this->noContent();
    }
}
