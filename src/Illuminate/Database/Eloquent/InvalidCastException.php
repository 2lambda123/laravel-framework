<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class InvalidCastException extends RuntimeException
{
    /**
     * The name of the affected Eloquent model.
     *
     * @var string
     */
    public $model;

    /**
     * The name of the column.
     *
     * @var string
     */
    public $column;

    /**
     * The name of the cast type.
     *
     * @var string
     */
    public $castType;

    /**
     * Create a new exception instance.
     *
     * @param  string  $model
     * @param  string  $column
     * @param  string  $castType
     * @return static
     */
    public function __construct($model, $column, $castType)
    {
        parent::__construct("Call to undefined cast [{$castType}] on column [{$column}] in model [{$model}].");

        $this->model = $model;
        $this->column = $column;
        $this->castType = $castType;
    }
}
