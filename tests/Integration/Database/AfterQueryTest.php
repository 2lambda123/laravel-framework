<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AfterQueryTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id')->nullable();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('owner_id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('users_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('post_id');
            $table->timestamps();
        });
    }

    public function testAfterQueryOnEloquentBuilder()
    {
        AfterQueryUser::create();
        AfterQueryUser::create();

        $afterQueryIds = collect();

        $users = AfterQueryUser::query()
            ->afterQuery(function (Collection $users) use ($afterQueryIds) {
                $afterQueryIds->push(...$users->pluck('id')->all());

                foreach ($users as $user) {
                    $this->assertInstanceOf(AfterQueryUser::class, $user);
                }
            })
            ->get();

        $this->assertCount(2, $users);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $users->pluck('id')->toArray());
    }

    public function testAfterQueryOnBaseBuilder()
    {
        AfterQueryUser::create();
        AfterQueryUser::create();

        $afterQueryIds = collect();

        $users = AfterQueryUser::query()
            ->toBase()
            ->afterQuery(function (Collection $users) use ($afterQueryIds) {
                $afterQueryIds->push(...$users->pluck('id')->all());

                foreach ($users as $user) {
                    $this->assertNotInstanceOf(AfterQueryUser::class, $user);
                }
            })
            ->get();

        $this->assertCount(2, $users);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $users->pluck('id')->toArray());
    }

    public function testAfterQueryHookOnBelongsToManyRelationship()
    {
        $user = AfterQueryUser::create();
        $firstPost = AfterQueryPost::create();
        $secondPost = AfterQueryPost::create();

        $user->posts()->attach($firstPost);
        $user->posts()->attach($secondPost);

        $afterQueryIds = collect();

        $posts = $user->posts()
            ->afterQuery(function (Collection $posts) use ($afterQueryIds) {
                $afterQueryIds->push(...$posts->pluck('id')->all());

                foreach ($posts as $post) {
                    $this->assertInstanceOf(AfterQueryPost::class, $post);
                }
            })
            ->get();

        $this->assertCount(2, $posts);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $posts->pluck('id')->toArray());
    }

    public function testAfterQueryHookOnHasManyThroughRelationship()
    {
        $user = AfterQueryUser::create();
        $team = AfterQueryTeam::create(['owner_id' => $user->id]);

        AfterQueryUser::create(['team_id' => $team->id]);
        AfterQueryUser::create(['team_id' => $team->id]);

        $afterQueryIds = collect();

        $teamMates = $user->teamMates()
            ->afterQuery(function (Collection $teamMates) use ($afterQueryIds) {
                $afterQueryIds->push(...$teamMates->pluck('id')->all());

                foreach ($teamMates as $teamMate) {
                    $this->assertInstanceOf(AfterQueryUser::class, $teamMate);
                }
            })
            ->get();

        $this->assertCount(2, $teamMates);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $teamMates->pluck('id')->toArray());
    }
}

class AfterQueryUser extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;

    public function teamMates()
    {
        return $this->hasManyThrough(self::class, AfterQueryTeam::class, 'owner_id', 'team_id');
    }

    public function posts()
    {
        return $this->belongsToMany(AfterQueryPost::class, 'users_posts', 'user_id', 'post_id')->withTimestamps();
    }
}

class AfterQueryTeam extends Model
{
    protected $table = 'teams';
    protected $guarded = [];
    public $timestamps = false;

    public function members()
    {
        return $this->hasMany(AfterQueryUser::class, 'team_id');
    }
}

class AfterQueryPost extends Model
{
    protected $table = 'posts';
    protected $guarded = [];
    public $timestamps = false;
}
