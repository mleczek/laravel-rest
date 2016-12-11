<?php


namespace Mleczek\Rest\Context;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class TimestampSortContext
{
    /**
     * Latest items first.
     *
     * @param Builder|Relation $query
     */
    public function latest($query)
    {
        $query->latest();
    }

    // TODO: More sort methods...
}