<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Integration\Database\Fixtures\Post;
use Illuminate\Tests\Integration\Database\Fixtures\User;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentTimestampsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->timestamps();
        });

        $this->schema()->create('users_created_at', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('created_at');
        });

        $this->schema()->create('users_updated_at', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('updated_at');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('users_created_at');
        $this->schema()->drop('users_updated_at');
        Carbon::setTestNow(null);
    }

    /**
     * Tests...
     */
    public function testUserWithCreatedAtAndUpdatedAt()
    {
        Carbon::setTestNow($now = Carbon::now());

        $user = UserWithCreatedAndUpdated::create([
            'email' => 'test@test.com',
        ]);

        $this->assertEquals($now->toDateTimeString(), $user->created_at->toDateTimeString());
        $this->assertEquals($now->toDateTimeString(), $user->updated_at->toDateTimeString());
    }

    public function testUserWithCreatedAt()
    {
        Carbon::setTestNow($now = Carbon::now());

        $user = UserWithCreated::create([
            'email' => 'test@test.com',
        ]);

        $this->assertEquals($now->toDateTimeString(), $user->created_at->toDateTimeString());
    }

    public function testUserWithUpdatedAt()
    {
        Carbon::setTestNow($now = Carbon::now());

        $user = UserWithUpdated::create([
            'email' => 'test@test.com',
        ]);

        $this->assertEquals($now->toDateTimeString(), $user->updated_at->toDateTimeString());
    }

    public function testCanNestIgnoreUpdatedTimestampsCalls()
    {
        $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
        $this->assertFalse(User::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Post::isIgnoringUpdatedTimestamp());

        User::withoutUpdatedTimestamp(function () {
            $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
            $this->assertFalse(Post::isIgnoringUpdatedTimestamp());
            $this->assertTrue(User::isIgnoringUpdatedTimestamp());
            Post::withoutUpdatedTimestamp(function () {
                $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
                $this->assertTrue(User::isIgnoringUpdatedTimestamp());
                $this->assertTrue(Post::isIgnoringUpdatedTimestamp());
            });
            $this->assertFalse(Post::isIgnoringUpdatedTimestamp());
        });

        $this->assertFalse(User::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Post::isIgnoringUpdatedTimestamp());
    }

    public function testArrayOfModelsCanHaveUpdatedTimestampIgnored()
    {
        $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
        $this->assertFalse(User::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Post::isIgnoringUpdatedTimestamp());

        Model::withoutUpdatedTimestampOn([User::class, Post::class], function () {
            $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
            $this->assertTrue(User::isIgnoringUpdatedTimestamp());
            $this->assertTrue(Post::isIgnoringUpdatedTimestamp());
        });

        $this->assertFalse(User::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Post::isIgnoringUpdatedTimestamp());
    }

    public function testWhenBaseModelIsIgnoringUpdatedTimestampsAllModelsAreIgnored()
    {
        $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
        $this->assertFalse(User::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Post::isIgnoringUpdatedTimestamp());

        Model::withoutUpdatedTimestamp(function () {
            $this->assertTrue(Model::isIgnoringUpdatedTimestamp());
            $this->assertTrue(User::isIgnoringUpdatedTimestamp());
            $this->assertTrue(Post::isIgnoringUpdatedTimestamp());
        });

        $this->assertFalse(User::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Post::isIgnoringUpdatedTimestamp());
    }

    public function testWhenASingleModelIsIgnoringUpdatedTimestampsOnlyItIsIgnored()
    {
        $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
        $this->assertFalse(User::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Post::isIgnoringUpdatedTimestamp());

        User::withoutUpdatedTimestamp(function () {
            $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
            $this->assertFalse(Post::isIgnoringUpdatedTimestamp());
            $this->assertTrue(User::isIgnoringUpdatedTimestamp());
        });

        $this->assertFalse(Post::isIgnoringUpdatedTimestamp());
        $this->assertFalse(User::isIgnoringUpdatedTimestamp());
        $this->assertFalse(Model::isIgnoringUpdatedTimestamp());
    }

    public function testWithoutUpdatedTimestampCallback()
    {
        new UserWithCreatedAndUpdated(['id' => 1]);

        $called = false;

        UserWithCreatedAndUpdated::withoutUpdatedTimestamp(function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function testWithoutUpdatedTimestampOnCallback()
    {
        new UserWithCreatedAndUpdated(['id' => 1]);

        $called = false;

        UserWithCreatedAndUpdated::withoutUpdatedTimestampOn([UserWithCreatedAndUpdated::class], function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class UserWithCreatedAndUpdated extends Eloquent
{
    protected $table = 'users';

    protected $guarded = [];
}

class UserWithCreated extends Eloquent
{
    public const UPDATED_AT = null;

    protected $table = 'users_created_at';

    protected $guarded = [];

    protected $dateFormat = 'U';
}

class UserWithUpdated extends Eloquent
{
    public const CREATED_AT = null;

    protected $table = 'users_updated_at';

    protected $guarded = [];

    protected $dateFormat = 'U';
}
