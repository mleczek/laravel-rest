<?php


namespace Mleczek\Rest\PostProcessing;


use Mleczek\Rest\ContextRepository;

class ModelBuilderFactory
{
    /**
     * @var ContextRepository
     */
    protected $context;

    /**
     * QueryBuilderFactory constructor.
     *
     * @param ContextRepository $context
     */
    public function __construct(ContextRepository $context)
    {
        $this->context = $context;
    }

    public function make($model)
    {
        return new ModelBuilder($model, $this->context);
    }
}