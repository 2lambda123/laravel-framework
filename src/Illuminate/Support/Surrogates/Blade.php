<?php namespace Illuminate\Support\Surrogates;

/**
 * @see \Illuminate\View\Compilers\BladeCompiler
 */
class Blade extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return static::$app['view']->getEngineResolver()->resolve('blade')->getCompiler();
	}

}