<?php


namespace Mleczek\Rest\Tests\Mocks;


use Illuminate\Database\Eloquent\Builder;

class UserFilterContext
{
    public function root(Builder $query)
    {
        $query->whereNotNull('is_root');
    }

    public function fullName(Builder $query, $first_name, $last_name)
    {
        $query->where('first_name', '=', $first_name)
            ->where('last_name', '=', $last_name);
    }
}