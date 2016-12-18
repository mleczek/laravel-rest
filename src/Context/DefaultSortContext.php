<?php


namespace Mleczek\Rest\Context;


class DefaultSortContext
{
    /**
     * Sort model's fillable attributes.
     *
     * Attribute name is taken from method name
     * (camelCase is transformed to snake_case).
     *
     * If methods ends with the "Desc" string
     * then it's stripped from attribute name
     * and results are sorted descendant.
     *
     * Examples:
     * ?sort=first_name_desc,email
     *
     * @param $method_name
     * @param array $args Always only one argument, the query.
     */
    public function __call($method_name, array $args)
    {
        $query = $args[0];
        $model = $query->newQuery()->getModel();

        $desc = ends_with($method_name, 'Desc');
        $attribute_name = $desc ? substr($method_name, 0, -4) : $method_name;
        $attribute_name = snake_case($attribute_name);

        if($model->isFillable($attribute_name)) {
            $query->orderBy($attribute_name, $desc ? 'desc' : 'asc');
        }
    }
}