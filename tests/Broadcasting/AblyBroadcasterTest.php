<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\AblyBroadcaster;
use Illuminate\Http\Request;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AblyBroadcasterTest extends TestCase
{
    /**
     * @var \Illuminate\Broadcasting\Broadcasters\AblyBroadcaster
     */
    public $broadcaster;

    public $ably;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ably = m::mock('Ably\AblyRest');

        $this->ably->shouldReceive('time')
            ->zeroOrMoreTimes()
            ->andReturn(time()); // TODO - make this call at runtime

        $this->ably->options = (object) ['key' => 'abcd:efgh'];

        $this->broadcaster = m::mock(AblyBroadcaster::class, [$this->ably])->makePartial();
    }

    public function testAuthCallValidAuthenticationResponseWithPrivateChannelWhenCallbackReturnTrue()
    {
        $this->broadcaster->channel('test', function () {
            return true;
        });

        $this->broadcaster->shouldReceive('validAuthenticationResponse')
            ->once();

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('private:test', null)
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPrivateChannelWhenCallbackReturnFalse()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            return false;
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('private:test', null)
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPrivateChannelWhenRequestUserNotFound()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            return true;
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithoutUserForChannel('private:test', null)
        );
    }

    public function testAuthCallValidAuthenticationResponseWithPresenceChannelWhenCallbackReturnAnArray()
    {
        $returnData = [1, 2, 3, 4];
        $this->broadcaster->channel('test', function () use ($returnData) {
            return $returnData;
        });

        $this->broadcaster->shouldReceive('validAuthenticationResponse')
            ->once();

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('presence:test', null)
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPresenceChannelWhenCallbackReturnNull()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            //
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithUserForChannel('presence:test', null)
        );
    }

    public function testAuthThrowAccessDeniedHttpExceptionWithPresenceChannelWhenRequestUserNotFound()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('test', function () {
            return [1, 2, 3, 4];
        });

        $this->broadcaster->auth(
            $this->getMockRequestWithoutUserForChannel('private:test', null)
        );
    }

    public function testGenerateAndValidateToken() {
        $headers = array('alg'=>'HS256','typ'=>'JWT');
        $payload = array('sub'=>'1234567890','name'=>'John Doe', 'admin'=>true, 'exp'=>(time() + 60));
        $jwtToken = $this->broadcaster->generateJwt($headers, $payload);

        $parsedJwt = AblyBroadcaster::parseJwt($jwtToken);
        self::assertEquals("HS256", $parsedJwt['header']['alg']);
        self::assertEquals("JWT", $parsedJwt['header']['typ']);

        self::assertEquals("1234567890", $parsedJwt['payload']['sub']);
        self::assertEquals("John Doe", $parsedJwt['payload']['name']);
        self::assertEquals(true, $parsedJwt['payload']['admin']);


        $timeFn = function () {return time(); };
        $jwtIsValid = $this->broadcaster->isJwtValid($jwtToken, $timeFn);
        self::assertTrue($jwtIsValid);
    }

    public function testShouldGetSignedToken() {
        $token = $this->broadcaster->getSignedToken(null, null, 'user123');
        $parsedToken = AblyBroadcaster::parseJwt($token);
        $header = $parsedToken["header"];
        $payload = $parsedToken["payload"];

        self::assertEquals("JWT", $header["typ"]);
        self::assertEquals("HS256", $header["alg"]);
        self::assertEquals("abcd", $header["kid"]);

        self::assertEquals(array('public:*' => ["subscribe", "history", "channel-metadata"]), $payload["x-ably-capability"]);
        self::assertEquals("user123", $payload["x-ably-clientId"]);

        self::assertEquals("integer", gettype($payload["iat"]));
        self::assertEquals("integer", gettype($payload["exp"]));
    }

    public function testShouldGetSignedTokenForGivenChannel() {
        $token = $this->broadcaster->getSignedToken("private:channel", null, 'user123');
        $parsedToken = AblyBroadcaster::parseJwt($token);
        $header = $parsedToken["header"];
        $payload = $parsedToken["payload"];

        self::assertEquals("JWT", $header["typ"]);
        self::assertEquals("HS256", $header["alg"]);
        self::assertEquals("abcd", $header["kid"]);

        $expectedCapability = array(
            'public:*' => ["subscribe", "history", "channel-metadata"],
            'private:channel' => ["*"]
        );
        self::assertEquals( $expectedCapability, $payload["x-ably-capability"]);
        self::assertEquals("user123", $payload["x-ably-clientId"]);

        self::assertEquals("integer", gettype($payload["iat"]));
        self::assertEquals("integer", gettype($payload["exp"]));
    }

    public function testShouldHaveUpgradedCapabilitiesForValidToken() {
        $token = $this->broadcaster->getSignedToken("private:channel", null, 'user123');

        $parsedToken = AblyBroadcaster::parseJwt($token);
        $payload = $parsedToken["payload"];
        self::assertEquals("integer", gettype($payload["iat"]));
        self::assertEquals("integer", gettype($payload["exp"]));
        $iat = $payload["iat"];
        $exp = $payload["exp"];

        $token = $this->broadcaster->getSignedToken("private:channel2", $token, 'user123');
        $parsedToken = AblyBroadcaster::parseJwt($token);
        $payload = $parsedToken["payload"];
        $expectedCapability = array(
            'public:*' => ["subscribe", "history", "channel-metadata"],
            'private:channel' => ["*"],
            'private:channel2' => ["*"]
        );
        self::assertEquals("user123", $payload["x-ably-clientId"]);
        self::assertEquals( $expectedCapability, $payload["x-ably-capability"]);
        self::assertEquals($iat, $payload["iat"]);
        self::assertEquals($exp, $payload["exp"]);

        $token = $this->broadcaster->getSignedToken("private:channel3", $token, 'user98');
        $parsedToken = AblyBroadcaster::parseJwt($token);
        $payload = $parsedToken["payload"];
        $expectedCapability = array(
            'public:*' => ["subscribe", "history", "channel-metadata"],
            'private:channel' => ["*"],
            'private:channel2' => ["*"],
            'private:channel3' => ["*"],
        );
        self::assertEquals("user98", $payload["x-ably-clientId"]);
        self::assertEquals( $expectedCapability, $payload["x-ably-capability"]);
        self::assertEquals($iat, $payload["iat"]);
        self::assertEquals($exp, $payload["exp"]);
    }

    public function testAuthSignedToken() {

    }

    public function testShouldFormatChannels() {
        $result = $this->broadcaster->formatChannels(['private-hello']);
        self::assertEquals("private:hello", $result[0]);

        $result = $this->broadcaster->formatChannels(['presence-hello']);
        self::assertEquals("presence:hello", $result[0]);

        $result = $this->broadcaster->formatChannels(['hello']);
        self::assertEquals("public:hello", $result[0]);
    }

    /**
     * @param  string  $channel
     * @return \Illuminate\Http\Request
     */
    protected function getMockRequestWithUserForChannel($channel, $token)
    {
        $request = m::mock(Request::class);
        $request->channel_name = $channel;
        $request->token = $token;
        $request->socket_id = 'abcd.1234';

        $request->shouldReceive('input')
            ->with('callback', false)
            ->andReturn(false);

        $user = m::mock('User');
        $user->shouldReceive('getAuthIdentifierForBroadcasting')
            ->andReturn(42);
        $user->shouldReceive('getAuthIdentifier')
            ->andReturn(42);

        $request->shouldReceive('user')
            ->andReturn($user);

        return $request;
    }

    /**
     * @param  string  $channel
     * @return \Illuminate\Http\Request
     */
    protected function getMockRequestWithoutUserForChannel($channel, $token)
    {
        $request = m::mock(Request::class);
        $request->channel_name = $channel;
        $request->token = $token;
        $request->socket_id = 'abcd.1234';


        $request->shouldReceive('user')
            ->andReturn(null);

        return $request;
    }
}
