<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class TransformsRequestTest extends TestCase
{
    public function testTransformKeyOncePerKeyWhenMethodIsGet()
    {
        $middleware = new TruncateKey;
        $symfonyRequest = new SymfonyRequest([
            '123' => 'bar',
            'abc' => 'baz',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('bar', $request->get('12'));
            $this->assertSame('baz', $request->get('ab'));
            $this->assertNull($request->get('123'));
        });
    }

    public function testTransformOncePerKeyWhenMethodIsGet()
    {
        $middleware = new TruncateInput;
        $symfonyRequest = new SymfonyRequest([
            'bar' => '123',
            'baz' => 'abc',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('12', $request->get('bar'));
            $this->assertSame('ab', $request->get('baz'));
        });
    }

    public function testTransformOncePerKeyWhenMethodIsPost()
    {
        $middleware = new ManipulateInput;
        $symfonyRequest = new SymfonyRequest(
            [
                'name' => 'Damian',
                'beers' => 4,
            ],
            ['age' => 28]
        );
        $symfonyRequest->server->set('REQUEST_METHOD', 'POST');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('Damian', $request->get('name'));
            $this->assertEquals(27, $request->get('age'));
            $this->assertEquals(5, $request->get('beers'));
        });
    }

    public function testTransformOncePerArrayKeysWhenMethodIsPost()
    {
        $middleware = new ManipulateArrayInput;
        $symfonyRequest = new SymfonyRequest(
            [
                'name' => 'Damian',
                'beers' => [4, 8, 12],
            ],
            [
                'age' => [28, 56, 84],
            ]
        );
        $symfonyRequest->server->set('REQUEST_METHOD', 'POST');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('Damian', $request->get('name'));
            $this->assertEquals([27, 55, 83], $request->get('age'));
            $this->assertEquals([5, 9, 13], $request->get('beers'));
        });
    }

    public function testTransformOncePerKeyWhenContentTypeIsJson()
    {
        $middleware = new ManipulateInput;
        $symfonyRequest = new SymfonyRequest(
            [
                'name' => 'Damian',
                'beers' => 4,
            ],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => '/json'],
            json_encode(['age' => 28])
        );
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('Damian', $request->input('name'));
            $this->assertEquals(27, $request->input('age'));
            $this->assertEquals(5, $request->input('beers'));
        });
    }
}

class ManipulateInput extends TransformsRequest
{
    protected function transform($key, $value)
    {
        if ($key === 'beers') {
            $value++;
        }

        if ($key === 'age') {
            $value--;
        }

        return $value;
    }
}

class ManipulateArrayInput extends TransformsRequest
{
    protected function transform($key, $value)
    {
        if (Str::contains($key, 'beers')) {
            $value++;
        }

        if (Str::contains($key, 'age')) {
            $value--;
        }

        return $value;
    }
}

class TruncateInput extends TransformsRequest
{
    protected function transform($key, $value)
    {
        return substr($value, 0, -1);
    }
}

class TruncateKey extends TransformsRequest
{
    protected function transformKey($key)
    {
        return substr($key, 0, -1);
    }
}
