<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

trait HasUlids
{
    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootHasUlids()
    {
        static::creating(function (self $model) {
            foreach ($model->uniqueIds() as $column) {
                if (empty($model->{$column})) {
                    $model->{$column} = $model->newUniqueId();
                }
            }
        });
    }

    /**
     * Generate a new ULID for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        return strtolower((string) Str::ulid());
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (in_array($this->getRouteKeyName(), $this->uniqueIds())) {
            if (! Str::isUlid($value)) {
                throw (new ModelNotFoundException)->setModel(get_class($this), $value);
            }
        }

        return parent::resolveRouteBinding($value);
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return [$this->getKeyName()];
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        if (in_array($this->getKeyName(), $this->uniqueIds())) {
            return 'string';
        }

        return $this->keyType;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        if (in_array($this->getKeyName(), $this->uniqueIds())) {
            return false;
        }

        return $this->incrementing;
    }
}
