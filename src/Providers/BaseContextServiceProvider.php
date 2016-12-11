<?php


namespace Mleczek\Rest\Providers;


use Illuminate\Support\ServiceProvider;
use Mleczek\Rest\ContextRepository;

class BaseContextServiceProvider extends ServiceProvider
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
     * Register rest models context.
     */
    public function boot(ContextRepository $repository)
    {
        // Filter
        foreach ($this->filter as $model => $context) {
            $repository->setFilterContext($model, $context);
        }

        // Sort
        foreach ($this->sort as $model => $context) {
            $repository->setSortContext($model, $context);
        }

        // Relation
        foreach ($this->with as $model => $context) {
            $repository->setWithContext($model, $context);
        }
    }
}