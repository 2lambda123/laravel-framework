<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabasePostgresQueryGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testToRawSql()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('escape')->with('foo', false)->andReturn("'foo'");
        $grammar = new PostgresGrammar;
        $grammar->setConnection($connection);

        $query = $grammar->substituteBindingsIntoRawSql(
            'select * from "users" where \'{}\' ?? \'Hello\\\'\\\'World?\' AND "email" = ?',
            ['foo'],
        );

        $this->assertSame('select * from "users" where \'{}\' ? \'Hello\\\'\\\'World?\' AND "email" = \'foo\'', $query);
    }

    public function testOrderByPriority()
    {
        $grammar = new PostgresGrammar;
        $queryString = $grammar->orderByPriority('name', ['john', 'doe']);
        $this->assertSame('case when "name" = ? then 0 when "name" = ? then 1 else 2 end asc', $queryString);
    }
}
