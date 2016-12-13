<?php

use Mleczek\Rest\QueryExecutor;

if(!function_exists('rest'))
{
    function rest()
    {
        return app(QueryExecutor::class);
    }
}