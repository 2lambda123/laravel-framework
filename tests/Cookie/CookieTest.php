<?php

use Mockery as m;
use Illuminate\Cookie\CookieJar;
use Symfony\Component\HttpFoundation\Request;

class CookieTest extends PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }


    public function testCookiesAreCreatedWithProperOptions()
    {
        $cookie = $this->getCreator();
        $cookie->setDefaultPathAndDomain('foo', 'bar');
        $c = $cookie->make('color', 'blue', 10, '/path', '/domain', true, false);
        $this->assertEquals('blue', $c->getValue());
        $this->assertFalse($c->isHttpOnly());
        $this->assertTrue($c->isSecure());
        $this->assertEquals('/domain', $c->getDomain());
        $this->assertEquals('/path', $c->getPath());

        $c2 = $cookie->forever('color', 'blue', '/path', '/domain', true, false);
        $this->assertEquals('blue', $c2->getValue());
        $this->assertFalse($c2->isHttpOnly());
        $this->assertTrue($c2->isSecure());
        $this->assertEquals('/domain', $c2->getDomain());
        $this->assertEquals('/path', $c2->getPath());
    }


    public function testCookiesAreCreatedWithProperOptionsUsingDefaultPathAndDomain()
    {
        $cookie = $this->getCreator();
        $cookie->setDefaultPathAndDomain('/path', '/domain');
        $c = $cookie->make('color', 'blue', 10, null, null, true, false);
        $this->assertEquals('blue', $c->getValue());
        $this->assertFalse($c->isHttpOnly());
        $this->assertTrue($c->isSecure());
        $this->assertEquals('/domain', $c->getDomain());
        $this->assertEquals('/path', $c->getPath());
    }


    public function testQueuedCookies()
    {
        $cookie = $this->getCreator();
        $this->assertEmpty($cookie->getQueuedCookies());
        $cookie->queue($cookie->make('foo', 'bar'));
        $this->assertArrayHasKey('foo', $cookie->getQueuedCookies());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie->queued('foo'));
    }

    public function testUnqueue()
    {
        $cookie = $this->getCreator();
        $cookie->queue($cookie->make('foo', 'bar'));
        $this->assertArrayHasKey('foo', $cookie->getQueuedCookies());
        $cookie->unqueue('foo');
        $this->assertEmpty($cookie->getQueuedCookies());
    }

    public function getCreator()
    {
        return new CookieJar(Request::create('/foo', 'GET'), array(
            'path' => '/path',
            'domain' => '/domain',
            'secure' => true,
            'httpOnly' => false,
        ));
    }

}
