<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class HasOne extends HasOneOrMany
{
    /**
     * Indicates if a default model instance should be used.
     *
     * Alternatively, may be a Closure to execute to retrieve default value.
     *
     * @var \Closure|bool
     */
    protected $withDefault;

    /**
     * Return a new model instance in case the relationship does not exist.
     *
     * @param  \Closure|bool  $callback
     * @return $this
     */
    public function withDefault($callback = true)
    {
        $this->withDefault = $callback;

        return $this;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->query->first() ?: $this->getDefaultFor($this->parent);
    }

    /**
     * Set the the relationship value.
     *
     * @param $value
     *
     * @return void
     */
    public function setValue($value)
    {
        $this->save($value);
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array   $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        return $this->matchOne($models, $results, $relation);
    }

    /**
     * Get the default value for this relation.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getDefaultFor(Model $model)
    {
        if (is_callable($this->withDefault)) {
            return call_user_func($this->withDefault);
        } elseif ($this->withDefault === true) {
            return $this->related->newInstance()->setAttribute(
                $this->getPlainForeignKey(), $model->getAttribute($this->localKey)
            );
        }
    }
}
