<?php


namespace Mleczek\Rest;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Context;

class QueryBuilder
{
    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var ContextRepository
     */
    protected $context;

    /**
     * Redundant fields retrieved to accomplish Eloquent joins.
     *
     * @var array
     */
    protected $redundant_fields;

    /**
     * QueryBuilder constructor.
     *
     * @param $query
     */
    public function __construct($query, ContextRepository $context)
    {
        $this->query = $query;
        $this->context = $context;
        $this->redundant_fields = [];
    }

    public function with($relation_name, callable $callback)
    {
        // Skip execution if relation not exists
        $model = $this->query->newQuery()->getModel();
        if (!$this->context->checkWith($model, $relation_name)) {
            return $this;
        }

        // Include relation in results
        $this->query->with([$relation_name => function ($query) use ($callback, $relation_name) {
            $builder = $callback($query, $relation_name);

            $this->addRedundantFields($builder->getRedundantFields(), $relation_name);
        }]);

        return $this;
    }

    public function filter(array $filters)
    {
        foreach ($filters as $filter_name => $filter_args) {
            $this->context->filter($this->query, $filter_name, $filter_args);
        }

        return $this;
    }

    public function sort(array $sort)
    {
        foreach ($sort as $sort_name) {
            $this->context->sort($this->query, $sort_name);
        }

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
     * @param Collection|Model $results
     */
    protected function removeRedundantFields($results)
    {
        $this->removeFields($results, $this->redundant_fields);
    }

    public function fields(array $fields)
    {
        // Get all columns if fields has not been specified
        if (empty($fields)) {
            return $this;
        }

        // Reset selected columns state for the query
        $this->query->select([]);

        // Include primary key in results
        $model = $this->query->newQuery()->getModel();
        $this->query->addSelect($model->getKeyName());
        $this->addRedundantField($model->getKeyName());

        // Include foreign key to be able to
        // perform join operation on server side.
        if (method_exists($this->query, 'getPlainForeignKey')) {
            $this->query->addSelect($this->query->getPlainForeignKey());
            $this->addRedundantField($this->query->getPlainForeignKey());
        }

        // Include specified columns
        foreach ($fields as $field_name) {
            $this->query->addSelect($field_name);
            $this->removeRedundantField($field_name);
        }

        return $this;
    }

    public function offset($offset)
    {
        $this->query->offset($offset);

        return $this;
    }

    public function limit($limit)
    {
        $this->query->limit($limit);

        return $this;
    }

    /**
     * @return Model
     */
    public function getItem()
    {
        $results = $this->query->firstOrFail();
        $this->removeRedundantFields($results);

        return $results;
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        $results = $this->query->get();
        $this->removeRedundantFields($results);

        return $results;
    }

    public function getQuery()
    {
        return $this->query;
    }
}
