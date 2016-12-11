<?php


namespace Mleczek\Rest\Tests\Mocks;


use Illuminate\Contracts\Config\Repository;

class ArrayConfig implements Repository
{
    /**
     * @var array
     */
    protected $config;

    /**
     * ArrayConfig constructor.
     */
    public function __construct()
    {
        $this->config = [
            'rest' => require(__DIR__.'/../../config/rest.php')
        ];
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return array_has($this->config, $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_get($this->config, $key);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        return $this->config;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string $key
     * @param  mixed $value
     * @return void
     */
    public function set($key, $value = null)
    {
        array_set($this->config, $key, $value);
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function prepend($key, $value)
    {
        array_prepend($this->config, $value, $key);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function push($key, $value)
    {
        array_add($this->config, $key, $value);
    }
}