<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\MySqlBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMySQLSchemaBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testHasTable()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(MySqlGrammar::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('db');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = new MySqlBuilder($connection);
        $grammar->shouldReceive('compileTableExists')->once()->with('db', 'prefix_table')->andReturn('sql');
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('sql')->andReturn(['prefix_table']);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testGetColumnListing()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(MySqlGrammar::class);
        $processor = m::mock(MySqlProcessor::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('db');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $grammar->shouldReceive('compileColumns')->once()->with('db', 'prefix_table')->andReturn('sql');
        $processor->shouldReceive('processColumns')->once()->andReturn([['name' => 'column']]);
        $builder = new MySqlBuilder($connection);
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('sql')->andReturn([['name' => 'column']]);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
