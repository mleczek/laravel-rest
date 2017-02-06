<?php

namespace App\Providers;


use Mleczek\Rest\Providers\BaseContextServiceProvider;

class ContextServiceProvider extends BaseContextServiceProvider
{
    /**
     * @var array
     */
    protected $filter = [
        'App/User' => 'App/Rest/Filter/UserFilterContext',
    ];

    /**
     * @var array
     */
    protected $sort = [
        'App/User' => 'App/Rest/Sort/UserSortContext',
    ];

    /**
     * @var array
     */
    protected $with = [
        'App/User' => 'App/Rest/With/UserWithContext',
    ];
}