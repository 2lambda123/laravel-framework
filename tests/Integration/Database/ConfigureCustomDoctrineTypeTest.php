<?php

namespace Illuminate\Tests\Integration\Database\SchemaTest;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class ConfigureCustomDoctrineTypeTest extends DatabaseTestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']['database.connections.sqlite.database'] = ':memory:';
        $app['config']['database.dbal.types'] = [
            'xml' => PostgresXmlType::class,
            'bit' => MySQLBitType::class,
        ];
    }

    public function test_register_custom_doctrine_types_on_multiple_database_connections_with_postgres()
    {
        if ($this->driver !== 'pgsql') {
            $this->markTestSkipped('Test requires a Postgres connection.');
        }

        $this->assertTrue(
            $this->app['db']->connection('pgsql')
                ->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->hasDoctrineTypeMappingFor('xml')
        );

        // Custom type mappings are registered for a connection when it's created,
        // this is not the default connection but it has the custom type mappings
        $this->assertTrue(
            $this->app['db']->connection('sqlite')
                ->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->hasDoctrineTypeMappingFor('xml')
        );
    }

    public function test_register_custom_doctrine_types_on_multiple_database_connections_with_mysql()
    {
        if ($this->driver !== 'mysql') {
            $this->markTestSkipped('Test requires a MySQL connection.');
        }

        $this->assertTrue(
            $this->app['db']->connection('mysql')
                ->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->hasDoctrineTypeMappingFor('xml')
        );

        // Custom type mappings are registered for a connection when it's created,
        // this is not the default connection but it has the custom type mappings
        $this->assertTrue(
            $this->app['db']->connection('sqlite')
                ->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->hasDoctrineTypeMappingFor('xml')
        );
    }

    public function test_rename_column_with_postgres_and_custom_doctrine_type_in_config()
    {
        if ($this->driver !== 'pgsql') {
            $this->markTestSkipped('Test requires a Postgres connection.');
        }

        Grammar::macro('typeXml', function () {
            return 'xml';
        });

        Schema::create('test', function (Blueprint $table) {
            $table->addColumn('xml', 'test_column');
        });

        Schema::table('test', function (Blueprint $table) {
            $table->renameColumn('test_column', 'renamed_column');
        });

        $this->assertFalse(Schema::hasColumn('test', 'test_column'));
        $this->assertTrue(Schema::hasColumn('test', 'renamed_column'));
    }

    public function test_rename_column_with_mysql_and_custom_doctrine_type_in_config()
    {
        if ($this->driver !== 'mysql') {
            $this->markTestSkipped('Test requires a MySQL connection.');
        }

        Grammar::macro('typeBit', function () {
            return 'bit';
        });

        Schema::create('test', function (Blueprint $table) {
            $table->addColumn('bit', 'test_column');
        });

        Schema::table('test', function (Blueprint $table) {
            $table->renameColumn('test_column', 'renamed_column');
        });

        $this->assertFalse(Schema::hasColumn('test', 'test_column'));
        $this->assertTrue(Schema::hasColumn('test', 'renamed_column'));
    }
}

class PostgresXmlType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'xml';
    }

    public function getName()
    {
        return 'xml';
    }
}

class MySQLBitType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'bit';
    }

    public function getName()
    {
        return 'bit';
    }
}
