<?php


namespace Mleczek\Rest\Tests;

use Illuminate\Http\Request;
use Mleczek\Rest\RequestParser;
use Mleczek\Rest\Tests\Mocks\ArrayConfig;
use \Symfony\Component\HttpFoundation\Request as BaseRequest;

class RequestParserTest extends \PHPUnit_Framework_TestCase
{
    private $uri = '?fields=id,first_name,last_name,messages.recipient.last_name'
    . '&filter=last_name_in:["Smith",Bloggs],messages.content_not_empty'
    . '&sort=messages.latest,last_name_asc,first_name_asc'
    . '&with=messages,messages.recipient'
    . '&offset=1,messages.recipient.0'
    . '&limit=5,messages.3';

    protected $parser;

    /**
     * Create parser before each test is executed.
     */
    protected function setUp()
    {
        $baseRequest = BaseRequest::create($this->uri);
        $request = Request::createFromBase($baseRequest);
        $config = new ArrayConfig();

        $this->parser = new RequestParser($request, $config);
    }

    public function testFieldsQueryParam()
    {
        $result = $this->parser->fields();
        $expected = ['id', 'first_name', 'last_name'];

        $this->assertInternalType('array', $result);
        $this->assertEquals($expected, $result);
    }

    public function testFieldsQueryParamNotExists()
    {
        $result = $this->parser->fields('unknown');
        $expected = [];

        $this->assertInternalType('array', $result);
        $this->assertEquals($expected, $result);
    }

    public function testFieldsQueryParamWithPrefix()
    {
        $result = $this->parser->fields('messages.recipient');
        $expected = ['last_name'];

        $this->assertInternalType('array', $result);
        $this->assertEquals($expected, $result);
    }

    public function testFilterQueryParam()
    {
        $result = $this->parser->filters();
        $expected = ['last_name_in' => ['Smith', 'Bloggs']];

        $this->assertInternalType('array', $result);
        $this->assertEquals($expected, $result);
    }

    public function testFilterQueryParamWithPrefix()
    {
        $result = $this->parser->filters('messages');
        $expected = ['content_not_empty' => []];

        $this->assertTrue(is_array($result));
        $this->assertEquals($expected, $result);
    }

    public function testSortQueryParam()
    {
        $result = $this->parser->sort();
        $expected = ['last_name_asc','first_name_asc'];

        $this->assertInternalType('array', $result);
        $this->assertEquals($expected, $result);
    }

    public function testSortQueryParamWithPrefix()
    {
        $result = $this->parser->sort('messages');
        $expected = ['latest'];

        $this->assertTrue(is_array($result));
        $this->assertEquals($expected, $result);
    }

    public function testWithQueryParam()
    {
        $result = $this->parser->with();
        $expected = ['messages', 'messages.recipient'];

        $this->assertInternalType('array', $result);
        $this->assertEquals($expected, $result);
    }

    public function testOffsetQueryParam()
    {
        $result = $this->parser->offset();
        $expected = 1;

        $this->assertInternalType('int', $result);
        $this->assertEquals($expected, $result);
    }

    public function testOffsetQueryParamNotExists()
    {
        $result = $this->parser->offset('unknown');
        $expected = 0;

        $this->assertInternalType('int', $result);
        $this->assertEquals($expected, $result);
    }

    public function testOffsetQueryParamWithPrefix()
    {
        $result = $this->parser->offset('messages.recipient');
        $expected = 0;

        $this->assertInternalType('int', $result);
        $this->assertEquals($expected, $result);
    }

    public function testLimitQueryParam()
    {
        $result = $this->parser->limit();
        $expected = 5;

        $this->assertInternalType('int', $result);
        $this->assertEquals($expected, $result);
    }

    public function testLimitQueryParamWithPrefix()
    {
        $result = $this->parser->limit('messages');
        $expected = 3;

        $this->assertInternalType('int', $result);
        $this->assertEquals($expected, $result);
    }
}