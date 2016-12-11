<?php


namespace Mleczek\Rest;


use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;

class RequestParser
{
    /**
     * @var string
     */
    const WITHOUT_RELATION = '_';

    /**
     * @var string
     */
    const ELEMENTS_SEPARATOR = ',';

    /**
     * @var string
     */
    const PREFIX_SEPARATOR = '.';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Repository
     */
    protected $config;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var array
     */
    protected $defaults;

    /**
     * RequestParser constructor.
     *
     * @param Request $request
     * @param Repository $config
     */
    public function __construct(Request $request, Repository $config)
    {
        $this->request = $request;
        $this->config = $config;
        $this->params = [];
        $this->defaults = [];

        $this->processFieldsQuery();
        $this->processFilterQuery();
        $this->processSortQuery();
        $this->processWithQuery();
        $this->processLimitQuery();
        $this->processOffsetQuery();
    }

    protected function processFieldsQuery($value = null)
    {
        $key = $this->config->get('rest.keys.fields');
        $value = (string)$this->request->query($key);

        // Divide items using elements separator and remove elements that converts to false
        // (http://php.net/manual/en/language.types.boolean.php#language.types.boolean.casting)
        $values = array_filter(explode(self::ELEMENTS_SEPARATOR, $value));

        foreach ($values as $namespace) {
            // Divide namespace into name and prefix
            $ns_parts = explode(self::PREFIX_SEPARATOR, $namespace);
            $name = array_pop($ns_parts);
            $prefix = implode(self::PREFIX_SEPARATOR, $ns_parts) ?: self::WITHOUT_RELATION;

            // Store field name
            $this->params[$key][$prefix][] = $name;
        }
    }

    protected function processFilterQuery()
    {
        $key = $this->config->get('rest.keys.filter');
        $value = (string)$this->request->query($key);

        // Symbol | Regexp            | Description
        // -------+-------------------+------------------------------------------------------------------------------------------
        //        | A|[^,]+           | Filter with arguments (A) or filter without arguments (or one argument followed by colon)
        //      A | [^,:]+:B          | Filter name ended with colon, then arguments (B)
        //      B | \[C\]             | Arguments (C) inside curly brackets
        //      C | (?:D|E|,)+        | Complex argument (D), simple argument (E) or argument separator
        //      D | "(?:\\.|[^\\"])*" | Argument with special characters (inside quotation marks)
        //      E | [^,\]]+           | Argument without special characters
        preg_match_all('/[^,:]+:\[(?:"(?:\\.|[^\\"])*"|[^,\]]+|,)+\]|[^,]+/', $value, $matches);

        foreach ($matches[0] as $match) {
            $filter = explode(':', $match, 2);

            // Divide into namespace and arguments
            $namespace_str = array_get($filter, 0);
            $arguments_str = array_get($filter, 1);

            // Divide namespace into name and prefix
            $namespace = explode(self::PREFIX_SEPARATOR, $namespace_str);
            $name = array_pop($namespace);
            $prefix = implode(self::PREFIX_SEPARATOR, $namespace) ?: self::WITHOUT_RELATION;

            // Store empty filter
            $this->params[$key][$prefix][$name] = [];

            // Divide arguments into values
            preg_match_all('/"(?:\\.|[^\\"])*"|[^,\]\["]+/', $arguments_str, $arguments);
            foreach ($arguments[0] as $argument) {
                // Remove quotation marks
                if (str_is('"*"', $argument)) {
                    $argument = substr($argument, 1, -1);
                }

                // Store filter argument
                $this->params[$key][$prefix][$name][] = $argument;
            }
        }
    }

    protected function processSortQuery()
    {
        $key = $this->config->get('rest.keys.sort');
        $value = (string)$this->request->query($key);

        // Divide items using elements separator and remove elements that converts to false
        // (http://php.net/manual/en/language.types.boolean.php#language.types.boolean.casting)
        $values = array_filter(explode(self::ELEMENTS_SEPARATOR, $value));

        foreach ($values as $namespace) {
            // Divide namespace into name and prefix
            $ns_parts = explode(self::PREFIX_SEPARATOR, $namespace);
            $name = array_pop($ns_parts);
            $prefix = implode(self::PREFIX_SEPARATOR, $ns_parts) ?: self::WITHOUT_RELATION;

            // Store sort name
            $this->params[$key][$prefix][] = $name;
        }
    }

    protected function processWithQuery()
    {
        $key = $this->config->get('rest.keys.with');
        $value = (string)$this->request->query($key);

        // Divide items using elements separator and remove elements that converts to false
        // (http://php.net/manual/en/language.types.boolean.php#language.types.boolean.casting)
        $values = array_filter(explode(self::ELEMENTS_SEPARATOR, $value));

        foreach ($values as $name) {
            // Store relation name
            $this->params[$key][] = $name;
        }
    }

    protected function processLimitQuery()
    {
        $key = $this->config->get('rest.keys.limit');
        $value = (string)$this->request->query($key);

        // Divide items using elements separator and remove elements that converts to false
        // (http://php.net/manual/en/language.types.boolean.php#language.types.boolean.casting).
        // Attention: Limit equal 0 will be discarded as well as empty string.
        $values = array_filter(explode(self::ELEMENTS_SEPARATOR, $value));

        foreach ($values as $namespace) {
            // Divide namespace into name and prefix
            $ns_parts = explode(self::PREFIX_SEPARATOR, $namespace);
            $value = (int)array_pop($ns_parts);
            $prefix = implode(self::PREFIX_SEPARATOR, $ns_parts) ?: self::WITHOUT_RELATION;

            // Store limit value
            $this->params[$key][$prefix] = $value;
        }
    }

    protected function processOffsetQuery()
    {
        $key = $this->config->get('rest.keys.offset');
        $value = (string)$this->request->query($key);

        // Divide items using elements separator and remove elements that converts to false
        // (http://php.net/manual/en/language.types.boolean.php#language.types.boolean.casting)
        // Attention: Offset equal 0 will be discarded as well as empty string.
        $values = array_filter(explode(self::ELEMENTS_SEPARATOR, $value));

        foreach ($values as $namespace) {
            // Divide namespace into name and prefix
            $ns_parts = explode(self::PREFIX_SEPARATOR, $namespace);
            $value = (int)array_pop($ns_parts);
            $prefix = implode(self::PREFIX_SEPARATOR, $ns_parts) ?: self::WITHOUT_RELATION;

            // Store limit value
            $this->params[$key][$prefix] = $value;
        }
    }

    /**
     * Get parameter value if exists or default value otherwise.
     *
     * @param string $name Parameter name.
     * @param string $prefix
     * @param mixed $default
     * @return mixed
     */
    protected function param($name, $prefix, $default)
    {
        // Get request user value
        if (isset($this->params[$name]) && isset($this->params[$name][$prefix])) {
            return $this->params[$name][$prefix];
        }

        // Get request default value
        if (isset($this->defaults[$name]) && isset($this->defaults[$name][$prefix])) {
            return $this->defaults[$name][$prefix];
        }

        return $default;
    }

    public function fields($prefix = self::WITHOUT_RELATION)
    {
        $key = $this->config->get('rest.keys.fields');

        return $this->param($key, $prefix, []);
    }

    public function filters($prefix = self::WITHOUT_RELATION)
    {
        $key = $this->config->get('rest.keys.filter');

        return $this->param($key, $prefix, []);
    }

    public function sort($prefix = self::WITHOUT_RELATION)
    {
        $key = $this->config->get('rest.keys.sort');

        return $this->param($key, $prefix, []);
    }

    public function with()
    {
        $key = $this->config->get('rest.keys.with');

        // Request user value
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }

        return array_get($this->defaults, $key, []);
    }

    public function limit($prefix = self::WITHOUT_RELATION)
    {
        $default = $this->config->get('rest.limit.default');
        $maxValue = $this->config->get('rest.limit.maxValue');

        $key = $this->config->get('rest.keys.limit');
        $value = $this->param($key, $prefix, $default);

        return max(1, min($maxValue, $value));
    }

    public function offset($prefix = self::WITHOUT_RELATION)
    {
        $key = $this->config->get('rest.keys.offset');

        return max(0, $this->param($key, $prefix, 0));
    }
}