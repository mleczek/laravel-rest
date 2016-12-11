<?php


namespace Mleczek\Rest\Tests\Mocks;


use Illuminate\Database\Eloquent\Builder;

class UserSortContext
{
    public function latest(Builder $query)
    {
        $query->latest();
    }
}