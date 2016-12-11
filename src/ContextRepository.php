<?php


namespace Mleczek\Rest;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ContextRepository
{
    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var array
     */
    protected $sort = [];

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @param Builder|Relation $query
     * @param string $filter
     * @param array $args
     * @return void
     */
    public function filter($query, $filter, array $args = [])
    {
        $model = $query->getModel();
        $model_ns = get_class($model);

        // Check whether relation context was set for this model
        if(!array_key_exists($model_ns, $this->filter)) {
            return;
        }

        foreach($this->filter[$model_ns] as $context) {
            // Create instance of the context handler
            $handler = new $context; // TODO: Resolve using service container
            $method_name = $this->camelCaseMethodName($filter);

            // If method exists then return value determine
            // whether relation can be used or not
            if(method_exists($handler, $method_name)) {
                // FIXME: Check number of parameters passed to handler (http://stackoverflow.com/questions/3989190/get-number-of-arguments-for-a-class-function)

                call_user_func_array([$handler, $method_name], array_merge([$query], $args));
            }
        }
    }

    /**
     * @param string $model_ns
     * @param array|string $handler
     */
    public function setFilterContext($model_ns, $handler)
    {
        $this->filter[$model_ns] = (array) $handler;
    }

    /**
     * @param Builder|Relation $query
     * @param string $sort
     * @return void
     */
    public function sort($query, $sort)
    {
        $model = $query->getModel();
        $model_ns = get_class($model);

        // Check whether relation context was set for this model
        if(!array_key_exists($model_ns, $this->sort)) {
            return;
        }

        foreach($this->sort[$model_ns] as $context) {
            // Create instance of the context handler
            $handler = new $context; // TODO: Resolve using service container
            $method_name = $this->camelCaseMethodName($sort);

            // If method exists then return value determine
            // whether relation can be used or not
            if(method_exists($handler, $method_name)) {
                call_user_func([$handler, $method_name], $query);
            }
        }
    }

    /**
     * @param string $model_ns
     * @param array|string $handler
     */
    public function setSortContext($model_ns, $handler)
    {
        $this->sort[$model_ns] = (array) $handler;
    }

    /**
     * @param Model|string $model
     * @return bool
     */
    public function checkWith($model, $relation)
    {
        $model_ns = (string) $model;
        if($model instanceof Model) {
            $model_ns = get_class($model);
        }

        // Check whether relation context was set for this model
        if(!array_key_exists($model_ns, $this->with)) {
            return false;
        }

        foreach($this->with[$model_ns] as $context) {
            // Check if raw relation name was provided
            if(!class_exists($context)) {
                if($context === $relation) {
                    return true;
                }

                continue;
            }

            // Create instance of the context handler
            $handler = new $context; // TODO: Resolve using service container
            $method_name = $this->camelCaseMethodName($relation);

            // If method exists then return value determine
            // whether relation can be used or not
            if(method_exists($handler, $method_name)) {
                return (bool) call_user_func([$handler, $method_name]);
            }
        }

        // No context matches
        // (relation not allowed)
        return false;
    }

    /**
     * @param string $model_ns
     * @param array|string $handler
     */
    public function setWithContext($model_ns, $handler)
    {
        $this->with[$model_ns] = (array) $handler;
    }

    /**
     * Get camelCase method name.
     *
     * @param mixed $value
     * @return string
     */
    protected function camelCaseMethodName($value)
    {
        // FIXME Convert all symbols except alphanum to dash in $value?

        return camel_case(str_replace('.', '_', (string) $value));
    }
}