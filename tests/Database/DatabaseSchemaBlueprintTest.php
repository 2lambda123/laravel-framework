<?php

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;

class DatabaseSchemaBlueprintTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testToSqlRunsCommandsFromBlueprint()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('statement')->once()->with('foo');
        $conn->shouldReceive('statement')->once()->with('bar');
        $grammar = m::mock('Illuminate\Database\Schema\Grammars\MySqlGrammar');
        $blueprint = $this->getMockBuilder('Illuminate\Database\Schema\Blueprint')->setMethods(['toSql'])->setConstructorArgs([$conn, 'users'])->getMock();
        $blueprint->expects($this->once())->method('toSql')->with($this->equalTo($grammar))->will($this->returnValue(['foo', 'bar']));

        $blueprint->build($grammar);
    }

    public function testIndexDefaultNames()
    {
        $connection = m::mock('Illuminate\Database\Connection');

        $blueprint = new Blueprint($connection, 'users');
        $blueprint->unique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertEquals('users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint($connection, 'users');
        $blueprint->index('foo');
        $commands = $blueprint->getCommands();
        $this->assertEquals('users_foo_index', $commands[0]->index);
    }

    public function testDropIndexDefaultNames()
    {
        $connection = m::mock('Illuminate\Database\Connection');

        $blueprint = new Blueprint($connection, 'users');
        $blueprint->dropUnique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertEquals('users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint($connection, 'users');
        $blueprint->dropIndex(['foo']);
        $commands = $blueprint->getCommands();
        $this->assertEquals('users_foo_index', $commands[0]->index);
    }

    public function testDefaultCurrentTimestamp()
    {
        $connection = m::mock('Illuminate\Database\Connection');

        $base = new Blueprint($connection, 'users', function ($table) {
            $table->timestamp('created')->useCurrent();
        });

        $blueprint = clone $base;
        $this->assertEquals(['alter table `users` add `created` timestamp default CURRENT_TIMESTAMP not null'], $blueprint->toSql(new MySqlGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" add column "created" timestamp(0) without time zone default CURRENT_TIMESTAMP(0) not null'], $blueprint->toSql(new PostgresGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" add column "created" datetime default CURRENT_TIMESTAMP not null'], $blueprint->toSql(new SQLiteGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" add "created" datetime default CURRENT_TIMESTAMP not null'], $blueprint->toSql(new SqlServerGrammar));
    }
}
