<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Schema\Builder;
use LogicException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseSchemaBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCreateDatabase()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = new Builder($connection);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This database driver does not support creating databases.');

        $builder->createDatabase('foo');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = new Builder($connection);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This database driver does not support dropping databases.');

        $builder->dropDatabaseIfExists('foo');
    }

    public function testHasTableCorrectlyCallsGrammar()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $builder = new Builder($connection);
        $grammar->shouldReceive('compileTables')->once()->andReturn('sql');
        $processor->shouldReceive('processTables')->once()->andReturn([['name' => 'prefix_table']]);
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('sql')->andReturn([['name' => 'prefix_table']]);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testTableHasColumns()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = m::mock(Builder::class.'[getColumnListing]', [$connection]);
        $builder->shouldReceive('getColumnListing')->with('users')->twice()->andReturn(['id', 'firstname']);

        $this->assertTrue($builder->hasColumns('users', ['id', 'firstname']));
        $this->assertFalse($builder->hasColumns('users', ['id', 'address']));
    }

    public function testTableHasIndex()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = m::mock(Builder::class.'[getIndexes]', [$connection]);
        $builder->shouldReceive('getIndexes')->with('users')->twice()->andReturn(['id', 'firstname']);

        $this->assertTrue($builder->hasIndex('users', 'id'));
        $this->assertFalse($builder->hasIndex('users', 'address'));
    }

    public function testTableHasIndexes()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = m::mock(Builder::class.'[getIndexes]', [$connection]);
        $builder->shouldReceive('getIndexes')->with('users')->twice()->andReturn(['id', 'firstname']);

        $this->assertTrue($builder->hasIndexes('users', ['id', 'firstname']));
        $this->assertFalse($builder->hasIndexes('users', ['id', 'address']));
    }

    public function testGetColumnTypeAddsPrefix()
    {
        $connection = m::mock(Connection::class);
        $column = m::mock(stdClass::class);
        $type = m::mock(stdClass::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $builder = new Builder($connection);
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('getDoctrineColumn')->once()->with('prefix_users', 'id')->andReturn($column);
        $connection->shouldReceive('usingNativeSchemaOperations')->once()->andReturn(false);
        $column->shouldReceive('getType')->once()->andReturn($type);
        $type->shouldReceive('getName')->once()->andReturn('integer');

        $this->assertSame('integer', $builder->getColumnType('users', 'id'));
    }
}
