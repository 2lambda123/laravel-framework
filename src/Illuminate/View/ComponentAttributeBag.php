<?php

namespace Illuminate\View;

use ArrayAccess;
use ArrayIterator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use IteratorAggregate;

class ComponentAttributeBag implements ArrayAccess, Htmlable, IteratorAggregate
{
    use Macroable;

    /**
     * The raw array of attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Array of attributes that should be imploded. 'class' is still hard coded.
     *
     * @var array
     */
    public static $implodedAttributes = [];

    /**
     * Create a new component attribute bag instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Set our list of imploded attributes.
     *
     * @param  array  $attrs
     * @return void
     */
    public static function implodedAttributes($attrs)
    {
        static::$implodedAttributes = $attrs;
    }

    /**
     * Get the first attribute's value.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function first($default = null)
    {
        return $this->getIterator()->current() ?? value($default);
    }

    /**
     * Get a given attribute from the attribute array.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->attributes[$key] ?? value($default);
    }

    /**
     * Only include the given attribute from the attribute array.
     *
     * @param  mixed|array  $keys
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            $values = $this->attributes;
        } else {
            $keys = Arr::wrap($keys);

            $values = Arr::only($this->attributes, $keys);
        }

        return new static($values);
    }

    /**
     * Exclude the given attribute from the attribute array.
     *
     * @param  mixed|array  $keys
     * @return static
     */
    public function except($keys)
    {
        if (is_null($keys)) {
            $values = $this->attributes;
        } else {
            $keys = Arr::wrap($keys);

            $values = Arr::except($this->attributes, $keys);
        }

        return new static($values);
    }

    /**
     * Filter the attributes, returning a bag of attributes that pass the filter.
     *
     * @param  callable  $callback
     * @return static
     */
    public function filter($callback)
    {
        return new static(collect($this->attributes)->filter($callback)->all());
    }

    /**
     * Return a bag of attributes that have keys starting with the given value / pattern.
     *
     * @param  string  $string
     * @return static
     */
    public function whereStartsWith($string)
    {
        return $this->filter(function ($value, $key) use ($string) {
            return Str::startsWith($key, $string);
        });
    }

    /**
     * Return a bag of attributes with keys that do not start with the given value / pattern.
     *
     * @param  string  $string
     * @return static
     */
    public function whereDoesntStartWith($string)
    {
        return $this->filter(function ($value, $key) use ($string) {
            return ! Str::startsWith($key, $string);
        });
    }

    /**
     * Return a bag of attributes that have keys starting with the given value / pattern.
     *
     * @param  string  $string
     * @return static
     */
    public function thatStartWith($string)
    {
        return $this->whereStartsWith($string);
    }

    /**
     * Exclude the given attribute from the attribute array.
     *
     * @param  mixed|array  $keys
     * @return static
     */
    public function exceptProps($keys)
    {
        $props = [];

        foreach ($keys as $key => $defaultValue) {
            $key = is_numeric($key) ? $defaultValue : $key;

            $props[] = $key;
            $props[] = Str::kebab($key);
        }

        return $this->except($props);
    }

    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param  array  $attributeDefaults
     * @param  bool  $escape
     * @return static
     */
    public function merge(array $attributeDefaults = [], $escape = true)
    {
        $attributes = [];

        $attributeDefaults = array_map(function ($value) use ($escape) {
            if (! $escape || is_object($value) || is_null($value) || is_bool($value)) {
                return $value;
            }

            return e($value);
        }, $attributeDefaults);

        foreach ($this->attributes as $key => $value) {
            if ($this->attributeShouldBeImploded($key)) {
                $attributes[$key] = implode($this->getAttributeImplodeGlueCharacter($key), array_unique(
                    array_filter([$attributeDefaults[$key] ?? '', $value])
                ));

                continue;
            }

            $attributes[$key] = $value;
        }

        return new static(array_merge($attributeDefaults, $attributes));
    }

    /**
     * Determine where or not to implode a merged attribute instead of overwriting it.
     *
     * @param  string  $key
     * @return bool
     */
    protected function attributeShouldBeImploded($key)
    {
        return $key === 'class' || in_array($key, static::$implodedAttributes) || isset(static::$implodedAttributes[$key]);
    }

    /**
     * Determine which character to use when imploding a merged attribute.
     *
     * @param  string  $key
     * @return string
     */
    protected function getAttributeImplodeGlueCharacter($key)
    {
        if ($key === 'class' || in_array($key, static::$implodedAttributes)) {
            return ' ';
        }

        return static::$implodedAttributes[$key] ?? ' ';
    }

    /**
     * Get all of the raw attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the underlying attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    public function setAttributes(array $attributes)
    {
        if (isset($attributes['attributes']) &&
            $attributes['attributes'] instanceof self) {
            $parentBag = $attributes['attributes'];

            unset($attributes['attributes']);

            $attributes = $parentBag->merge($attributes, $escape = false)->getAttributes();
        }

        $this->attributes = $attributes;
    }

    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return (string) $this;
    }

    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param  array  $attributeDefaults
     * @return \Illuminate\Support\HtmlString
     */
    public function __invoke(array $attributeDefaults = [])
    {
        return new HtmlString((string) $this->merge($attributeDefaults));
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Get the value at the given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Remove the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->attributes);
    }

    /**
     * Implode the attributes into a single HTML ready string.
     *
     * @return string
     */
    public function __toString()
    {
        $string = '';

        foreach ($this->attributes as $key => $value) {
            if ($value === false || is_null($value)) {
                continue;
            }

            if ($value === true) {
                $value = $key;
            }

            $string .= ' '.$key.'="'.str_replace('"', '\\"', trim($value)).'"';
        }

        return trim($string);
    }
}
