<?php namespace Illuminate\Session;

use Illuminate\Cookie\CookieJar;

class CookieSessionHandler implements \SessionHandlerInterface {

	/**
	 * The cookie jar instance.
	 *
	 * @var \Illuminate\Cookie\CookieJar
	 */
	protected $cookie;

	/**
	 * Create a new cookie driven handler instance.
	 *
	 * @param  \Illuminate\Cookie\CookieJar  $cookie
	 * @param  int  $minutes
	 * @return void
	 */
	public function __construct(CookieJar $cookie, $minutes)
	{
		$this->cookie = $cookie;
		$this->minutes = $minutes;
	}

	/**
	 * {@inheritDoc}
	 */
	public function open($savePath, $sessionName)
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close()
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function read($sessionId)
	{
		return $this->cookie->get($sessionId) ?: '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function write($sessionId, $data)
	{
		$this->cookie->queue($sessionId, $data, $this->minutes);
	}

	/**
	 * {@inheritDoc}
	 */
	public function destroy($sessionId)
	{
		$this->cookie->queue($this->cookie->forget($sessionId));
	}

	/**
	 * {@inheritDoc}
	 */
	public function gc($lifetime)
	{
		return true;
	}

}