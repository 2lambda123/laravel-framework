<?php

namespace Illuminate\Tests\Database;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\Grammar;
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

    public function testDropIndexesIfExists()
    {
        $indexes = ['index1', 'index2', 'index3'];
        $connection = m::mock(Connection::class);
        $grammar = new class extends Grammar {};
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturnTrue();
        $connection->shouldReceive('getConfig')->with('prefix')->andReturnTrue();
        $sm = m::mock(AbstractSchemaManager::class);
        $connection->shouldReceive('getDoctrineSchemaManager')->once()->andReturn($sm);
        $blueprint = m::mock(Blueprint::class);
        $blueprint->shouldReceive('dropForeign')->times(count($indexes))->andReturnSelf();
        $blueprint->shouldReceive('dropIndex')->times(count($indexes))->andReturnSelf();
        $builder = m::mock(Builder::class, [$connection])->makePartial();
        $builder->shouldReceive('table')->times(count($indexes))->andReturnUsing(function ($table, $callback) use ($blueprint) {
            $callback($blueprint);
        });
        $sm->shouldReceive('listTableIndexes')->once()->with('users')->andReturn(array_flip($indexes));

        $this->assertNull($builder->dropIndexIfExists('users', $indexes));
    }

    public function testDropIndexIfExists()
    {
        $index = 'index';
        $connection = m::mock(Connection::class);
        $grammar = new class extends Grammar {};
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturnTrue();
        $connection->shouldReceive('getConfig')->with('prefix')->andReturnTrue();
        $sm = m::mock(AbstractSchemaManager::class);
        $connection->shouldReceive('getDoctrineSchemaManager')->once()->andReturn($sm);
        $blueprint = m::mock(Blueprint::class);
        $blueprint->shouldReceive('dropForeign')->once()->with($index)->andReturnSelf();
        $blueprint->shouldReceive('dropIndex')->once()->with($index)->andReturnSelf();
        $builder = m::mock(Builder::class, [$connection])->makePartial();
        $builder->shouldReceive('table')->once()->andReturnUsing(function ($table, $callback) use ($blueprint) {
            $callback($blueprint);
        });
        $sm->shouldReceive('listTableIndexes')->once()->with('users')->andReturn([$index => []]);

        $this->assertNull($builder->dropIndexIfExists('users', $index));
    }

    public function testHasTableCorrectlyCallsGrammar()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = new Builder($connection);
        $grammar->shouldReceive('compileTableExists')->once()->andReturn('sql');
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('sql', ['prefix_table'])->andReturn(['prefix_table']);

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
        $column->shouldReceive('getType')->once()->andReturn($type);
        $type->shouldReceive('getName')->once()->andReturn('integer');

        $this->assertSame('integer', $builder->getColumnType('users', 'id'));
    }
}
