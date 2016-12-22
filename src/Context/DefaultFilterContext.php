<?php


namespace Mleczek\Rest\Context;


class DefaultFilterContext
{
    /**
     * Filter model's fillable attributes.
     *
     * Attribute name is taken from method name
     * (camelCase is transformed to snake_case).
     *
     * Examples:
     * ?filter=email:"test@example.com",paid_invoice
     *
     * @param $method_name
     * @param array $args One or two argument, the query and expected value.
     */
    public function __call($method_name, array $args)
    {
        $query = $args[0];
        $model = $query->newQuery()->getModel();

        $value = (string) array_get($args, 1);
        $attribute_name = snake_case($method_name);

        if($model->isFillable($attribute_name)) {
            $query->where($attribute_name, $value);
        }
    }
}