<?php

namespace Illuminate\Tests\Foundation\Configuration;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class MiddlewareTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        Container::setInstance(null);
        ConvertEmptyStringsToNull::flushState();
        EncryptCookies::flushState();
        TrimStrings::flushState();
        TrustProxies::flushState();
    }

    public function testConvertEmptyStringsToNull()
    {
        $configuration = new Middleware();
        $middleware = new ConvertEmptyStringsToNull();

        $configuration->convertEmptyStringsToNull(except: [
            fn (Request $request) => $request->has('skip-all-1'),
            fn (Request $request) => $request->has('skip-all-2'),
        ]);

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '',
        ]);

        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertNull($request->get('bbb'));

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '',
            'skip-all-1' => 'true',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertSame('', $request->get('bbb'));

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '',
            'skip-all-2' => 'true',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertSame('', $request->get('bbb'));
    }

    public function testTrimStrings()
    {
        $configuration = new Middleware();
        $middleware = new TrimStrings();

        $configuration->trimStrings(except: [
            'aaa',
            fn (Request $request) => $request->has('skip-all'),
        ]);

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '  456  ',
            'ccc' => '  789  ',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertSame('456', $request->get('bbb'));
        $this->assertSame('789', $request->get('ccc'));

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '  456  ',
            'ccc' => '  789  ',
            'skip-all' => true,
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertSame('  456  ', $request->get('bbb'));
        $this->assertSame('  789  ', $request->get('ccc'));
    }

    public function testTrustProxies()
    {
        $configuration = new Middleware();
        $middleware = new TrustProxies;

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('proxies');
        $method->setAccessible(true);

        $property = $reflection->getProperty('proxies');
        $property->setAccessible(true);

        $this->assertNull($method->invoke($middleware));

        $property->setValue($middleware, [
            '192.168.1.1',
            '192.168.1.2',
        ]);

        $this->assertEquals([
            '192.168.1.1',
            '192.168.1.2',
        ], $method->invoke($middleware));

        $configuration->trustProxies(at: '*');
        $this->assertEquals('*', $method->invoke($middleware));

        $configuration->trustProxies(at: [
            '192.168.1.3',
            '192.168.1.4',
        ]);
        $this->assertEquals([
            '192.168.1.3',
            '192.168.1.4',
        ], $method->invoke($middleware));
    }

    public function testTrustHeaders()
    {
        $configuration = new Middleware();
        $middleware = new TrustProxies;

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('headers');
        $method->setAccessible(true);

        $property = $reflection->getProperty('headers');
        $property->setAccessible(true);

        $this->assertEquals(Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB, $method->invoke($middleware));

        $property->setValue($middleware, Request::HEADER_X_FORWARDED_AWS_ELB);

        $this->assertEquals(Request::HEADER_X_FORWARDED_AWS_ELB, $method->invoke($middleware));

        $configuration->trustProxies(withHeaders: Request::HEADER_X_FORWARDED_FOR);

        $this->assertEquals(Request::HEADER_X_FORWARDED_FOR, $method->invoke($middleware));

        $configuration->trustProxies([
            '192.168.1.3',
            '192.168.1.4',
        ], Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT
        );

        $this->assertEquals(Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT, $method->invoke($middleware));
    }
  
    public function testTrustHosts()
    {
        $app = Mockery::mock(Application::class);
        $configuration = new Middleware();
        $middleware = new class($app) extends TrustHosts
        {
            protected function allSubdomainsOfApplicationUrl()
            {
                return '^(.+\.)?laravel\.test$';
            }
        };

        $this->assertEquals(['^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts();
        $this->assertEquals(['^(.+\.)?laravel\.test$'], $middleware->hosts());

        app()['config'] = Mockery::mock(Repository::class);
        app()['config']->shouldReceive('get')->with('app.url', null)->once()->andReturn('http://laravel.test');

        $configuration->trustHosts(at: ['my.test']);
        $this->assertEquals(['my.test', '^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: ['my.test']);
        $this->assertEquals(['my.test', '^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: ['my.test'], subdomains: false);
        $this->assertEquals(['my.test'], $middleware->hosts());

        $configuration->trustHosts(at: []);
        $this->assertEquals(['^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: [], subdomains: false);
        $this->assertEquals([], $middleware->hosts());
    }

    public function testEncryptCookies()
    {
        $configuration = new Middleware();
        $encrypter = Mockery::mock(Encrypter::class);
        $middleware = new EncryptCookies($encrypter);

        $this->assertFalse($middleware->isDisabled('aaa'));
        $this->assertFalse($middleware->isDisabled('bbb'));

        $configuration->encryptCookies(except: [
            'aaa',
            'bbb',
        ]);

        $this->assertTrue($middleware->isDisabled('aaa'));
        $this->assertTrue($middleware->isDisabled('bbb'));
    }
}
