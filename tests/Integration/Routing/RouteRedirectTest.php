<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class RouteRedirectTest extends TestCase
{
    /**
     * @dataProvider  routeRedirectDataSets
     */
    public function testRouteRedirect($redirectFrom, $redirectTo, $requestUri, $redirectUri)
    {
        $this->withoutExceptionHandling();
        Route::redirect($redirectFrom, $redirectTo, Response::HTTP_MOVED_PERMANENTLY);

        $response = $this->get($requestUri);
        $response->assertRedirect($redirectUri);
        $response->assertMovedPermanently();
    }

    public function routeRedirectDataSets()
    {
        return [
            'route redirect with no parameters' => ['from', 'to', '/from', '/to'],
            'route redirect with one parameter' => ['from/{param}/{param2?}', 'to', '/from/value1', '/to'],
            'route redirect with two parameters' => ['from/{param}/{param2?}', 'to', '/from/value1/value2', '/to'],
            'route redirect with one parameter replacement' => ['users/{user}/repos', 'members/{user}/repos', '/users/22/repos', '/members/22/repos'],
            'route redirect with two parameter replacements' => ['users/{user}/repos/{repo}', 'members/{user}/projects/{repo}', '/users/22/repos/laravel-framework', '/members/22/projects/laravel-framework'],
            'route redirect with non existent optional parameter replacements' => ['users/{user?}', 'members/{user?}', '/users', '/members'],
            'route redirect with existing parameter replacements' => ['users/{user?}', 'members/{user?}', '/users/22', '/members/22'],
            'route redirect with two optional replacements' => ['users/{user?}/{repo?}', 'members/{user?}', '/users/22', '/members/22'],
            'route redirect with two optional replacements that switch position' => ['users/{user?}/{switch?}', 'members/{switch?}/{user?}', '/users/11/22', '/members/22/11'],
        ];
    }

    public function testToRouteHelper()
    {
        Route::get('to', function () {
            // ..
        })->name('to');

        Route::get('from-301', function () {
            return to_route('to', [], Response::HTTP_MOVED_PERMANENTLY);
        });

        Route::get('from-302', function () {
            return to_route('to');
        });

        $this->get('from-301')
            ->assertRedirect('to')
            ->assertMovedPermanently()
            ->assertSee('Redirecting to');

        $this->get('from-302')
            ->assertRedirect('to')
            ->assertFound()
            ->assertSee('Redirecting to');
    }
}
