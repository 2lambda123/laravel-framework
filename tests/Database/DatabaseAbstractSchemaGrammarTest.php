<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use LogicException;
use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use Mockery as m;

class DatabaseAbstractSchemaGrammarTest extends TestCase
{
    public function testCreateDatabase()
    {
        $grammar = new class extends Grammar {};

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This database driver does not support creating databases.');

        $grammar->compileCreateDatabase('foo', m::mock(Connection::class));
    }

    public function testDropDatabaseIfExists()
    {
        $grammar = new class extends Grammar {};

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This database driver does not support dropping databases.');

        $grammar->compileDropDatabaseIfExists('foo');
    }
}
