<?php namespace Illuminate\Support;

class Pluralizer {

	/**
	 * Plural word form rules.
	 *
	 * @var array
	 */
	protected static $plural = array(
		'/(quiz)$/i' => "$1zes",
		'/^(ox)$/i' => "$1en",
		'/([m|l])ouse$/i' => "$1ice",
		'/(matr|vert|ind)ix|ex$/i' => "$1ices",
		'/(x|ch|ss|sh)$/i' => "$1es",
		'/([^aeiouy]|qu)y$/i' => "$1ies",
		'/(hive)$/i' => "$1s",
		'/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
		'/(shea|lea|loa|thie)f$/i' => "$1ves",
		'/sis$/i' => "ses",
		'/([ti])um$/i' => "$1a",
		'/(tomat|potat|ech|her|vet)o$/i' => "$1oes",
		'/(bu)s$/i' => "$1ses",
		'/(alias)$/i' => "$1es",
		'/(octop)us$/i' => "$1i",
		'/(ax|test)is$/i' => "$1es",
		'/(us)$/i' => "$1es",
		'/s$/i' => "s",
		'/$/' => "s",
	);

	/**
	 * Singular word form rules.
	 *
	 * @var array
	 */
	protected static $singular = array(
		'/(quiz)zes$/i' => "$1",
		'/(matr)ices$/i' => "$1ix",
		'/(vert|ind)ices$/i' => "$1ex",
		'/^(ox)en$/i' => "$1",
		'/(alias)es$/i' => "$1",
		'/(octop|vir)i$/i' => "$1us",
		'/(cris|ax|test)es$/i' => "$1is",
		'/(shoe)s$/i' => "$1",
		'/(o)es$/i' => "$1",
		'/(bus)es$/i' => "$1",
		'/([m|l])ice$/i' => "$1ouse",
		'/(x|ch|ss|sh)es$/i' => "$1",
		'/(m)ovies$/i' => "$1ovie",
		'/(s)eries$/i' => "$1eries",
		'/([^aeiouy]|qu)ies$/i' => "$1y",
		'/([lr])ves$/i' => "$1f",
		'/(tive)s$/i' => "$1",
		'/(hive)s$/i' => "$1",
		'/(li|wi|kni)ves$/i' => "$1fe",
		'/(shea|loa|lea|thie)ves$/i' => "$1f",
		'/(^analy)ses$/i' => "$1sis",
		'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => "$1$2sis",
		'/([ti])a$/i' => "$1um",
		'/(n)ews$/i' => "$1ews",
		'/(h|bl)ouses$/i' => "$1ouse",
		'/(corpse)s$/i' => "$1",
		'/(us)es$/i' => "$1",
		'/(us|ss)$/i' => "$1",
		'/s$/i' => "",
	);

	/**
	 * Irregular word forms.
	 *
	 * @var array
	 */
	protected static $irregular = array(
		'child' => 'children',
		'foot' => 'feet',
		'goose' => 'geese',
		'man' => 'men',
		'move' => 'moves',
		'person' => 'people',
		'sex' => 'sexes',
		'tooth' => 'teeth',
	);

	/**
	 * Uncountable word forms.
	 *
	 * @var array
	 */
	protected static $uncountable = array(
		'audio',
		'equipment',
		'deer',
		'fish',
		'gold',
		'information',
		'money',
		'rice',
		'police',
		'series',
		'sheep',
		'species',
		'moose',
		'chassis',
		'traffic',
	);

	/**
	 * The cached copies of the plural inflections.
	 *
	 * @var array
	 */
	protected static $pluralCache = array();

	/**
	 * The cached copies of the singular inflections.
	 *
	 * @var array
	 */
	protected static $singularCache = array();

	/**
	 * Get the singular form of the given word.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function singular($value)
	{
		if (isset(static::$singularCache[$value]))
		{
			return static::$singularCache[$value];
		}

		$result = static::inflect($value, static::$singular, static::$irregular);

		return static::$singularCache[$value] = $result ?: $value;
	}

	/**
	 * Get the plural form of the given word.
	 *
	 * @param  string  $value
	 * @param  int     $count
	 * @return string
	 */
	public static function plural($value, $count = 2)
	{
		if ($count == 1) return $value;

		// First we'll check the cache of inflected values. We cache each word that
		// is inflected so we don't have to spin through the regular expressions
		// on each subsequent method calls for this word by the app developer.
		if (isset(static::$pluralCache[$value]))
		{
			return static::$pluralCache[$value];
		}

		$irregular = array_flip(static::$irregular);

		// When doing the singular to plural transformation, we'll flip the irregular
		// array since we need to swap sides on the keys and values. After we have
		// the transformed value we will cache it in memory for faster look-ups.
		$plural = static::$plural;

		$result = static::inflect($value, $plural, $irregular);

		return static::$pluralCache[$value] = $result;
	}

	/**
	 * Perform auto inflection on an English word.
	 *
	 * @param  string  $value
	 * @param  array   $source
	 * @param  array   $irregular
	 * @return string
	 */
	protected static function inflect($value, $source, $irregular)
	{
		if (static::uncountable($value)) return $value;

		// Next, we will check the "irregular" patterns which contain words that are
		// not easily summarized in regular expression rules, like "children" and
		// "teeth", both of which cannot get inflected using our typical rules.
		foreach ($irregular as $irregular => $pattern)
		{
			if (preg_match($fullPattern = '/'.$pattern.'$/i', $value))
			{
				// We only need to math the case of the irregular part of the value
				$valuePart = substr($value, -strlen($pattern));
				
				$irregular = static::matchCase($irregular, $valuePart);
				
				return preg_replace($fullPattern, $irregular, $value);
			}
		}

		// Finally, we'll spin through the array of regular expressions and look for
		// matches for the word. If we find a match, we will cache and return the
		// transformed value so we will quickly look it up on subsequent calls.
		foreach ($source as $pattern => $inflected)
		{
			if (preg_match($pattern, $value))
			{
				$inflected = preg_replace($pattern, $inflected, $value);

				return static::matchCase($inflected, $value);
			}
		}
	}

	/**
	 * Determine if the given value is uncountable.
	 *
	 * @param  string  $value
	 * @return bool
	 */
	protected static function uncountable($value)
	{
		return in_array(strtolower($value), static::$uncountable);
	}

	/**
	 * Attempt to match the case on two strings.
	 *
	 * @param  string  $value
	 * @param  string  $comparison
	 * @return string
	 */
	protected static function matchCase($value, $comparison)
	{
		$functions = array('mb_strtolower', 'mb_strtoupper', 'ucfirst', 'ucwords');

		foreach ($functions as $function)
		{
			if (call_user_func($function, $comparison) === $comparison)
			{
				return call_user_func($function, $value);
			}
		}

		return $value;
	}

}
