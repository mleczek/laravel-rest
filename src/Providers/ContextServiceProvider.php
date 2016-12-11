<?php


namespace Mleczek\Rest\Providers;


use Illuminate\Support\ServiceProvider;
use Mleczek\Rest\ContextRepository;

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