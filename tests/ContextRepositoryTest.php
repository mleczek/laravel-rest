<?php


namespace Mleczek\Rest\Tests;


use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mleczek\Rest\ContextRepository;
use Mleczek\Rest\Tests\Fixtures\Message;
use Mleczek\Rest\Tests\Fixtures\User;
use Mleczek\Rest\Tests\Mocks\UserFilterContext;
use Mleczek\Rest\Tests\Mocks\UserSortContext;
use Mleczek\Rest\Tests\Mocks\UserWithContext;
use Mockery\Mock;

class ContextRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextRepository
     */
    protected $context;

    protected function setUp()
    {
        $container = \Mockery::mock(Container::class)
            ->shouldReceive('make')
            ->andReturnUsing(function($handler) {
                return new $handler;
            })->getMock();

        $this->context = new ContextRepository($container);
    }

    protected function tearDown()
    {
        \Mockery::close();
    }

    public function testWithContextWithOneArgument()
    {
        $this->context->setWithContext(User::class, 'messages');

        $this->assertTrue($this->context->checkWith(User::class, 'messages'));
    }

    public function testWithContextUsingNames()
    {
        $this->context->setWithContext(User::class, ['messages', 'messages.recipient']);

        $this->assertTrue($this->context->checkWith(User::class, 'messages'));
        $this->assertTrue($this->context->checkWith(User::class, 'messages.recipient'));
        $this->assertFalse($this->context->checkWith(User::class, 'unknown'));
    }

    public function testWithContextParameterTypes()
    {
        $user = new User();

        $this->assertFalse($this->context->checkWith($user, 'unknown'));
        $this->assertFalse($this->context->checkWith(User::class, 'unknown'));
    }

    public function testWithContextUsingHandler()
    {
        $this->context->setWithContext(User::class, [UserWithContext::class, 'messages']);

        $this->assertFalse($this->context->checkWith(User::class, 'messages')); // first occur from list is important
        $this->assertTrue($this->context->checkWith(User::class, 'messages.recipient'));
    }

    public function testWithContextMultipleModels()
    {
        $this->context->setWithContext(Message::class, 'author');
        $this->context->setWithContext(User::class, 'messages');

        $this->assertTrue($this->context->checkWith(User::class, 'messages'));
        $this->assertFalse($this->context->checkWith(User::class, 'author'));
        $this->assertFalse($this->context->checkWith(Message::class, 'messages'));
        $this->assertTrue($this->context->checkWith(Message::class, 'author'));
    }

    public function testSortContextSkipNonExisting()
    {
        $query_mock = \Mockery::mock(Builder::class);
        $query_mock->shouldReceive('getModel')->withNoArgs()->andReturn(new User())->once();
        $query_mock->shouldNotReceive('not_exists');

        $this->context->sort($query_mock, 'not_exists');
    }

    public function testSortContextWithOneArgument()
    {
        $this->context->setSortContext(User::class, UserSortContext::class);

        $query_mock = \Mockery::mock(Builder::class);
        $query_mock->shouldReceive('getModel')->withNoArgs()->andReturn(new User())->once();
        $query_mock->shouldReceive('latest')->withNoArgs()->once();

        $this->context->sort($query_mock, 'latest');
    }

    public function testFilterContextSkipNonExisting()
    {
        $query_mock = \Mockery::mock(Builder::class);
        $query_mock->shouldReceive('getModel')->withNoArgs()->andReturn(new User())->once();
        $query_mock->shouldNotReceive('not_exists');

        $this->context->filter($query_mock, 'not_exists');
    }

    public function testFilterWithNoArgsContext()
    {
        $this->context->setFilterContext(User::class, UserFilterContext::class);

        $query_mock = \Mockery::mock(Builder::class);
        $query_mock->shouldReceive('getModel')->withNoArgs()->andReturn(new User())->once();
        $query_mock->shouldReceive('whereNotNull')->withArgs(['is_root'])->once();

        $this->context->filter($query_mock, 'root');
    }

    public function testFilterWithArgsContext()
    {
        $this->context->setFilterContext(User::class, UserFilterContext::class);

        $query_mock = \Mockery::mock(Builder::class);
        $query_mock->shouldReceive('getModel')->withNoArgs()->andReturn(new User())->once();
        $query_mock->shouldReceive('where')->withArgs(['first_name', '=', 'John'])->andReturn($query_mock)->once();
        $query_mock->shouldReceive('where')->withArgs(['last_name', '=', 'Smith'])->andReturn($query_mock)->once();

        $this->context->filter($query_mock, 'full_name', ['John', 'Smith']);
    }
}