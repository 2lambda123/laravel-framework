<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class Can implements Rule
{
    /**
     * The gate ability.
     *
     * @var string
     */
    protected $ability;

    /**
     * The gate arguments.
     *
     * @var array
     */
    protected $arguments;

    /**
     * Constructor.
     *
     * @param  string  $ability
     * @param  mixed  $arguments
     */
    public function __construct($ability, array $arguments = [])
    {
        $this->ability = $ability;
        $this->arguments = $arguments;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $arguments = $this->arguments;

        $model = array_shift($arguments);

        return Gate::allows($this->ability, array_filter([$model, ...$arguments, $value]));
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        $message = trans('validation.can');

        return $message === 'validation.can'
            ? ['The :attribute field contains a unauthorized value.']
            : $message;
    }
}
