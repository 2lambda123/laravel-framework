<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\TracksPreviousAttributes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentModelTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('nullable_date')->nullable();
        });

        Schema::create('test_model2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
        });

        Schema::create('test_model3', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
        });
    }

    public function testUserCanUpdateNullableDate()
    {
        $user = TestModel1::create([
            'nullable_date' => null,
        ]);

        $user->fill([
            'nullable_date' => $now = Carbon::now(),
        ]);
        $this->assertTrue($user->isDirty('nullable_date'));

        $user->save();
        $this->assertEquals($now->toDateString(), $user->nullable_date->toDateString());
    }

    public function testAttributeChanges()
    {
        $user = TestModel2::create([
            'name' => Str::random(), 'title' => Str::random(),
        ]);

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->name = $name = Str::random();

        $this->assertEquals(['name' => $name], $user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertTrue($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->save();

        $this->assertEmpty($user->getDirty());
        $this->assertEquals(['name' => $name], $user->getChanges());
        $this->assertTrue($user->wasChanged());
        $this->assertTrue($user->wasChanged('name'));
    }

    public function testPreviousAttributes()
    {
        $user = TestModel3::create([
            'name' => $oldName = Str::random(), 'title' => Str::random(),
        ]);

        $this->assertEmpty($user->getPrevious());

        $user->name = $newName = Str::random();

        $original = $user->getOriginal();
        $rawOriginal = $user->getRawOriginal();

        $user->save();

        $this->assertEquals($original, $user->getPrevious());
        $this->assertEquals($rawOriginal, $user->getRawPrevious());

        $this->assertEquals($oldName, $user->getPrevious('name'));
        $this->assertEquals($newName, $user->getOriginal('name'));
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['nullable_date' => 'datetime'];
}

class TestModel2 extends Model
{
    public $table = 'test_model2';
    public $timestamps = false;
    protected $guarded = [];
}

class TestModel3 extends Model
{
    use TracksPreviousAttributes;

    public $table = 'test_model3';
    public $timestamps = false;
    protected $guarded = [];
}
