<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Tests\Integration\Database\DatabaseTestCase;

abstract class SqlServerTestCase extends DatabaseTestCase
{
    protected function defineDatabaseMigrations()
    {
        if ($this->driver !== 'sqlsrv') {
            dd($this->driver);
            $this->markTestSkipped('Test requires an SQL Server Connection');
        }
    }
}
