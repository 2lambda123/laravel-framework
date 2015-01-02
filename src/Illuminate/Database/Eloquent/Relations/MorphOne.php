<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;

class MorphOne extends MorphOneOrMany {

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->initInverseRelation($this->query->first());
	}

	/**
	 * Initialize the parent relationship on the model.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model|null  $model
	 * @return \Illuminate\Database\Eloquent\Model|null
	 */
	protected function initInverseRelation($model = null)
	{
		if ( ! empty($this->relationToParent) && $model)
		{
			$model->setRelation($this->relationToParent, $this->parent);
		}

		return $model;
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
		foreach ($models as $model)
		{
			$model->setRelation($relation, null);
		}

		return $models;
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $models
	 * @param  \Illuminate\Database\Eloquent\Collection  $results
	 * @param  string  $relation
	 * @return array
	 */
	public function match(array $models, Collection $results, $relation)
	{
		return $this->matchOne($models, $results, $relation);
	}

}
