<?php

namespace Illuminate\Tests\Integration\Foundation\Testing\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class InteractsWithAuthenticationTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers.users.model', AuthenticationTestUser::class);

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('username');
            $table->string('password');
            $table->string('remember_token')->default(null)->nullable();
            $table->tinyInteger('is_active')->default(0);
        });

        AuthenticationTestUser::create([
            'username' => 'taylorotwell',
            'email' => 'taylorotwell@laravel.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    public function testActingAsIsProperlyHandledForSessionAuth()
    {
        Route::get('me', function (Request $request) {
            return 'Hello '.$request->user()->username;
        })->middleware(['auth']);

        $user = AuthenticationTestUser::where('username', '=', 'taylorotwell')->first();

        $this->actingAs($user)
            ->get('/me')
            ->assertSuccessful()
            ->assertSeeText('Hello taylorotwell');
    }

    public function testActingAsGuestIsProperlyHandledForSessionAuth()
    {
        Route::get('me', function (Request $request) {
            return 'Hello '.$request->user()->username;
        })->middleware(['auth']);

        Route::get('login', function (Request $request) {
            return 'This is a login page.';
        })->middleware(['guest'])->name('login');

        $user = AuthenticationTestUser::where('username', '=', 'taylorotwell')->first();

        $this->withoutExceptionHandling()
            ->expectException(AuthenticationException::class);

        $this->actingAs($user)
            ->actingAsGuest()
            ->get('/me');
    }

    public function testActingAsIsProperlyHandledForAuthViaRequest()
    {
        Route::get('me', function (Request $request) {
            return 'Hello '.$request->user()->username;
        })->middleware(['auth:api']);
        Auth::viaRequest('api', function ($request) {
            return $request->user();
        });
        $user = AuthenticationTestUser::where('username', '=', 'taylorotwell')->first();
        $this->actingAs($user, 'api')
            ->get('/me')
            ->assertSuccessful()
            ->assertSeeText('Hello taylorotwell');
    }

    public function testActingAsGuestIsProperlyHandledForAuthViaRequest()
    {
        Route::get('me', function (Request $request) {
            return 'Hello '.$request->user()->username;
        })->middleware(['auth:api']);

        Route::get('login', function (Request $request) {
            return 'This is a login page.';
        })->middleware(['guest'])->name('login');

        Auth::viaRequest('api', function ($request) {
            return $request->user();
        });

        $user = AuthenticationTestUser::where('username', '=', 'taylorotwell')->first();

        $this->withoutExceptionHandling()
            ->expectException(AuthenticationException::class);

        $this->actingAs($user, 'api')
            ->actingAsGuest('api')
            ->get('/me');
    }
}

class AuthenticationTestUser extends Authenticatable
{
    public $table = 'users';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
