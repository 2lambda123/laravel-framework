<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Http\ResponseFactory
 */
class Response extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'response'; }

}
