<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_mysql')]
#[RequiresOperatingSystemFamily('Linux|Darwin')]
class JoinLateralTest extends MySqlTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('id');
            $table->string('name');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id('id');
            $table->string('title');
            $table->integer('rating');
            $table->unsignedBigInteger('user_id');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('posts');
        Schema::drop('users');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->checkMySqlVersion();

        DB::table('users')->insert([
            ['name' => Str::random()],
            ['name' => Str::random()],
        ]);

        DB::table('posts')->insert([
            ['title' => Str::random(), 'rating' => 1, 'user_id' => 1],
            ['title' => Str::random(), 'rating' => 3, 'user_id' => 1],
            ['title' => Str::random(), 'rating' => 7, 'user_id' => 1],
        ]);
    }

    protected function checkMySqlVersion()
    {
        $mySqlVersion = DB::select('select version()')[0]->{'version()'} ?? '';

        if (strpos($mySqlVersion, 'Maria') !== false) {
            $this->markTestSkipped('Lateral joins are not supported on MariaDB'.__CLASS__);
        } elseif ((float) $mySqlVersion < '8.0.14') {
            $this->markTestSkipped('Lateral joins are not supported on MySQL < 8.0.14'.__CLASS__);
        }
    }

    public function testJoinLateral()
    {
        $subquery = DB::table('posts')
            ->select('title as best_post_title', 'rating as best_post_rating')
            ->whereColumn('user_id', 'users.id')
            ->orderBy('rating', 'desc')
            ->limit(2);

        $userWithPosts = DB::table('users')
            ->where('id', 1)
            ->joinLateral($subquery, 'best_post')
            ->get();

        $this->assertCount(2, $userWithPosts);
        $this->assertEquals(7, $userWithPosts[0]->best_post_rating);
        $this->assertEquals(3, $userWithPosts[1]->best_post_rating);

        $userWithoutPosts = DB::table('users')
            ->where('id', 2)
            ->joinLateral($subquery, 'best_post')
            ->get();

        $this->assertCount(0, $userWithoutPosts);
    }

    public function testLeftJoinLateral()
    {
        $subquery = DB::table('posts')
            ->select('title as best_post_title', 'rating as best_post_rating')
            ->whereColumn('user_id', 'users.id')
            ->orderBy('rating', 'desc')
            ->limit(2);

        $userWithPosts = DB::table('users')
            ->where('id', 1)
            ->leftJoinLateral($subquery, 'best_post')
            ->get();

        $this->assertCount(2, $userWithPosts);
        $this->assertEquals(7, $userWithPosts[0]->best_post_rating);
        $this->assertEquals(3, $userWithPosts[1]->best_post_rating);

        $userWithoutPosts = DB::table('users')
            ->where('id', 2)
            ->leftJoinLateral($subquery, 'best_post')
            ->get();

        $this->assertCount(1, $userWithoutPosts);
        $this->assertNull($userWithoutPosts[0]->best_post_title);
        $this->assertNull($userWithoutPosts[0]->best_post_rating);
    }
}
