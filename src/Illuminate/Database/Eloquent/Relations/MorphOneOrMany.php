<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class MorphOneOrMany extends HasOneOrMany {

	/**
	 * The foreign key type for the relationship.
	 *
	 * @var string
	 */
	protected $morphType;

	/**
	 * The class name of the parent model.
	 *
	 * @var string
	 */
	protected $morphClass;

	/**
	 * Create a new has many relationship instance.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder $query
	 * @param  \Illuminate\Database\Eloquent\Model $parent
	 * @param  string $type
	 * @param  string $id
	 */
	public function __construct(Builder $query, Model $parent, $type, $id)
	{
		$this->morphType = $type;

		$this->morphClass = get_class($parent);

		parent::__construct($query, $parent, $id);
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	public function addConstraints()
	{
		parent::addConstraints();

		$this->query->where($this->morphType, $this->morphClass);
	}

	/**
	 * Add the constraints for a relationship count query.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function getRelationCountQuery(Builder $query)
	{
		$query = parent::getRelationCountQuery($query);

		return $query->where($this->morphType, $this->morphClass);
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{
		parent::addEagerConstraints($models);

		$this->query->where($this->morphType, $this->morphClass);
	}	

	/**
	 * Remove the original where clause set by the relationship.
	 *
	 * The remaining constraints on the query will be reset and returned.
	 *
	 * @return array
	 */
	public function getAndResetWheres()
	{
		// We actually need to remove two where clauses from polymorphic queries so we
		// will make an extra call to clear the second where clause here so that it
		// will not get in the way. This parent method will remove the other one.
		$this->removeSecondWhereClause();

		return parent::getAndResetWheres();
	}

	/**
	 * Attach a model instance to the parent model.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function save(Model $model)
	{
		$model->setAttribute($this->getPlainMorphType(), $this->morphClass);

		return parent::save($model);
	}

	/**
	 * Create a new instance of the related model.
	 *
	 * @param  array  $attributes
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function create(array $attributes)
	{
		$foreign = $this->getForeignAttributesForCreate();

		// When saving a polymorphic relationship, we need to set not only the foreign
		// key, but also the foreign key type, which is typically the class name of
		// the parent model. This makes the polymorphic item unique in the table.
		$attributes = array_merge($attributes, $foreign);

		$instance = $this->related->newInstance($attributes);

		$instance->save();

		return $instance;
	}

	/**
	 * Get the foreign ID and type for creating a related model.
	 *
	 * @return array
	 */
	protected function getForeignAttributesForCreate()
	{
		$foreign = array($this->getPlainForeignKey() => $this->parent->getKey());

		$foreign[last(explode('.', $this->morphType))] = $this->morphClass;

		return $foreign;
	}

	/**
	 * Get the foreign key "type" name.
	 *
	 * @return string
	 */
	public function getMorphType()
	{
		return $this->morphType;
	}

	/**
	 * Get the plain morph type name without the table.
	 *
	 * @return string
	 */
	public function getPlainMorphType()
	{
		return last(explode('.', $this->morphType));
	}

	/**
	 * Get the class name of the parent model.
	 *
	 * @return string
	 */
	public function getMorphClass()
	{
		return $this->morphClass;
	}

}