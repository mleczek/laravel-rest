<?php


namespace Mleczek\Rest\Context;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class TimestampFilterContext
{
    /**
     * @param Builder|Relation $query
     * @param $from
     * @param $to
     */
    public function createdBetween($query, $from, $to)
    {
        $from = date("Y-m-d 0:0:0", strtotime($from));
        $to = date("Y-m-d 23:59:59", strtotime($to));

        $query->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to);
    }

    // TODO: More filters...
}