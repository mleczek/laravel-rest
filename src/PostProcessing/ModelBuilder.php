<?php


namespace Mleczek\Rest\PostProcessing;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mleczek\Rest\ContextRepository;
use Mleczek\Rest\RequestParser;

class ModelBuilder
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var ContextRepository
     */
    protected $context;

    /**
     * @var array
     */
    protected $fields;

    /**
     * Redundant fields retrieved to accomplish Eloquent joins.
     *
     * @var array
     */
    protected $redundant_fields;

    /**
     * QueryBuilder constructor.
     *
     * @param Model $model
     * @param ContextRepository $context
     */
    public function __construct(Model $model, ContextRepository $context)
    {
        $this->model = $model;
        $this->context = $context;
        $this->redundant_fields = [];
    }

    public function with($relation_name, callable $callback)
    {
        // Skip execution if relation not exists
        if (!$this->context->checkWith($this->model, $relation_name)) {
            return $this;
        }

        // Include relation in results
        $this->model->load([$relation_name => function ($query) use ($callback, $relation_name) {
            $builder = $callback($query, $relation_name);

            $this->addRedundantFields($builder->getRedundantFields(), $relation_name);
        }]);

        return $this;
    }

    protected function addRedundantField($field, $prefix = null)
    {
        $ptr = &$this->redundant_fields;

        if (!is_null($prefix)) {
            $parts = explode(RequestParser::PREFIX_SEPARATOR, $prefix);
            foreach ($parts as $part) {
                $ptr = &$ptr[$part];
            }
        }

        $ptr[] = $field;
    }

    protected function addRedundantFields($fields, $prefix = null)
    {
        foreach ($fields as $field) {
            $this->addRedundantField($field, $prefix);
        }
    }

    protected function removeRedundantField($field)
    {
        if (($key = array_search($field, $this->redundant_fields)) !== false) {
            unset($this->redundant_fields[$key]);
        }
    }

    public function getRedundantFields()
    {
        return $this->redundant_fields;
    }

    private function removeFields($results, $mess_collection) {
        // Repeat removing for each collection item
        if($results instanceof Collection) {
            foreach($results as $result) {
                $this->removeFields($result, $mess_collection);
            }

            return;
        }

        foreach($mess_collection as $key => $value) {
            if(is_string($value)) {
                // Remove attribute
                unset($results->{$value});
            }
            else if(is_array($value)) {
                // Remove attributes for [nested] relation
                $this->removeFields($results->{$key}, $value);
            }
        }
    }

    /**
     * @param Model $results
     */
    protected function removeRedundantFields($results)
    {
        $this->removeFields($results, $this->redundant_fields);
    }

    public function fields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    public function getItem()
    {
        $results = $this->model;
        $this->removeRedundantFields($results);

        // Remove fields
        $results = $results->toArray();
        if(!empty($this->fields)) {
            foreach ($this->model->getAttributes() as $key => $value) {
                if (!in_array($key, $this->fields)) {
                    unset($results[$key]);
                }
            }
        }

        return $results;
    }

    public function getModel()
    {
        return $this->model;
    }
}