<?php namespace Illuminate\Database\Eloquent; use Closure; use Illuminate\Database\Query\Expression; use Illuminate\Database\Eloquent\Relations\Relation; use Illuminate\Database\Query\Builder as QueryBuilder; class Builder { protected $query; protected $model; protected $eagerLoad = array(); protected $macros = array(); protected $onDelete; protected $passthru = array( 'toSql', 'lists', 'insert', 'insertGetId', 'pluck', 'count', 'min', 'max', 'avg', 'sum', 'exists', 'getBindings', ); public function __construct(QueryBuilder $query) { $this->query = $query; } public function find($id, $columns = array('*')) { if (is_array($id)) { return $this->findMany($id, $columns); } $this->query->where($this->model->getKeyName(), '=', $id); return $this->first($columns); } public function findMany($id, $columns = array('*')) { $this->query->whereIn($this->model->getKeyName(), $id); return $this->get($columns); } public function findOrFail($id, $columns = array('*')) { if ( ! is_null($model = $this->find($id, $columns))) return $model; throw with(new ModelNotFoundException)->setModel(get_class($this->model)); } public function first($columns = array('*')) { return $this->take(1)->get($columns)->first(); } public function firstOrFail($columns = array('*')) { if ( ! is_null($model = $this->first($columns))) return $model; throw with(new ModelNotFoundException)->setModel(get_class($this->model)); } public function get($columns = array('*')) { $models = $this->getModels($columns); if (count($models) > 0) { $models = $this->eagerLoadRelations($models); } return $this->model->newCollection($models); } public function pluck($column) { $result = $this->first(array($column)); if ($result) return $result->{$column}; } public function chunk($count, $callback) { $results = $this->forPage($page = 1, $count)->get(); while (count($results) > 0) { call_user_func($callback, $results); $page++; $results = $this->forPage($page, $count)->get(); } } public function lists($column, $key = null) { $results = $this->query->lists($column, $key); if ($this->model->hasGetMutator($column)) { foreach ($results as $key => &$value) { $fill = array($column => $value); $value = $this->model->newFromBuilder($fill)->$column; } } return $results; } public function paginate($perPage = null, $columns = array('*')) { $perPage = $perPage ?: $this->model->getPerPage(); $paginator = $this->query->getConnection()->getPaginator(); if (isset($this->query->groups)) { return $this->groupedPaginate($paginator, $perPage, $columns); } else { return $this->ungroupedPaginate($paginator, $perPage, $columns); } } protected function groupedPaginate($paginator, $perPage, $columns) { $results = $this->get($columns)->all(); return $this->query->buildRawPaginator($paginator, $results, $perPage); } protected function ungroupedPaginate($paginator, $perPage, $columns) { $total = $this->query->getPaginationCount(); $page = $paginator->getCurrentPage($total); $this->query->forPage($page, $perPage); return $paginator->make($this->get($columns)->all(), $total, $perPage); } public function update(array $values) { return $this->query->update($this->addUpdatedAtColumn($values)); } public function increment($column, $amount = 1, array $extra = array()) { $extra = $this->addUpdatedAtColumn($extra); return $this->query->increment($column, $amount, $extra); } public function decrement($column, $amount = 1, array $extra = array()) { $extra = $this->addUpdatedAtColumn($extra); return $this->query->decrement($column, $amount, $extra); } protected function addUpdatedAtColumn(array $values) { if ( ! $this->model->usesTimestamps()) return $values; $column = $this->model->getUpdatedAtColumn(); return array_add($values, $column, $this->model->freshTimestampString()); } public function delete() { if (isset($this->onDelete)) { return call_user_func($this->onDelete, $this); } else { return $this->query->delete(); } } public function forceDelete() { return $this->query->delete(); } public function onDelete(Closure $callback) { $this->onDelete = $callback; } public function getModels($columns = array('*')) { $results = $this->query->get($columns); $connection = $this->model->getConnectionName(); $models = array(); foreach ($results as $result) { $models[] = $model = $this->model->newFromBuilder($result); $model->setConnection($connection); } return $models; } public function eagerLoadRelations(array $models) { foreach ($this->eagerLoad as $name => $constraints) { if (strpos($name, '.') === false) { $models = $this->loadRelation($models, $name, $constraints); } } return $models; } protected function loadRelation(array $models, $name, Closure $constraints) { $relation = $this->getRelation($name); $relation->addEagerConstraints($models); call_user_func($constraints, $relation); $models = $relation->initRelation($models, $name); $results = $relation->getEager(); return $relation->match($models, $results, $name); } public function getRelation($relation) { $query = Relation::noConstraints(function() use ($relation) { return $this->getModel()->$relation(); }); $nested = $this->nestedRelations($relation); if (count($nested) > 0) { $query->getQuery()->with($nested); } return $query; } protected function nestedRelations($relation) { $nested = array(); foreach ($this->eagerLoad as $name => $constraints) { if ($this->isNested($name, $relation)) { $nested[substr($name, strlen($relation.'.'))] = $constraints; } } return $nested; } protected function isNested($name, $relation) { $dots = str_contains($name, '.'); return $dots && starts_with($name, $relation) && $name != $relation; } public function where($column, $operator = null, $value = null, $boolean = 'and') { if ($column instanceof Closure) { $query = $this->model->newQueryWithoutScopes(); call_user_func($column, $query); $this->query->addNestedWhereQuery($query->getQuery(), $boolean); } else { call_user_func_array(array($this->query, 'where'), func_get_args()); } return $this; } public function orWhere($column, $operator = null, $value = null) { return $this->where($column, $operator, $value, 'or'); } public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null) { $relation = $this->getHasRelationQuery($relation); $query = $relation->getRelationCountQuery($relation->getRelated()->newQuery(), $this); if ($callback) call_user_func($callback, $query); return $this->addHasWhere($query, $relation, $operator, $count, $boolean); } public function whereHas($relation, Closure $callback, $operator = '>=', $count = 1) { return $this->has($relation, $operator, $count, 'and', $callback); } public function orHas($relation, $operator = '>=', $count = 1) { return $this->has($relation, $operator, $count, 'or'); } public function orWhereHas($relation, Closure $callback, $operator = '>=', $count = 1) { return $this->has($relation, $operator, $count, 'or', $callback); } protected function addHasWhere(Builder $hasQuery, Relation $relation, $operator, $count, $boolean) { $this->mergeWheresToHas($hasQuery, $relation); return $this->where(new Expression('('.$hasQuery->toSql().')'), $operator, $count, $boolean); } protected function mergeWheresToHas(Builder $hasQuery, Relation $relation) { $relationQuery = $relation->getBaseQuery(); $hasQuery->mergeWheres( $relationQuery->wheres, $relationQuery->getBindings() ); $this->query->mergeBindings($hasQuery->getQuery()); } protected function getHasRelationQuery($relation) { return Relation::noConstraints(function() use ($relation) { return $this->getModel()->$relation(); }); } public function with($relations) { if (is_string($relations)) $relations = func_get_args(); $eagers = $this->parseRelations($relations); $this->eagerLoad = array_merge($this->eagerLoad, $eagers); return $this; } protected function parseRelations(array $relations) { $results = array(); foreach ($relations as $name => $constraints) { if (is_numeric($name)) { $f = function() {}; list($name, $constraints) = array($constraints, $f); } $results = $this->parseNested($name, $results); $results[$name] = $constraints; } return $results; } protected function parseNested($name, $results) { $progress = array(); foreach (explode('.', $name) as $segment) { $progress[] = $segment; if ( ! isset($results[$last = implode('.', $progress)])) { $results[$last] = function() {}; } } return $results; } protected function callScope($scope, $parameters) { array_unshift($parameters, $this); return call_user_func_array(array($this->model, $scope), $parameters) ?: $this; } public function getQuery() { return $this->query; } public function setQuery($query) { $this->query = $query; } public function getEagerLoads() { return $this->eagerLoad; } public function setEagerLoads(array $eagerLoad) { $this->eagerLoad = $eagerLoad; } public function getModel() { return $this->model; } public function setModel(Model $model) { $this->model = $model; $this->query->from($model->getTable()); return $this; } public function macro($name, Closure $callback) { $this->macros[$name] = $callback; } public function getMacro($name) { return array_get($this->macros, $name); } public function __call($method, $parameters) { if (isset($this->macros[$method])) { array_unshift($parameters, $this); return call_user_func_array($this->macros[$method], $parameters); } elseif (method_exists($this->model, $scope = 'scope'.ucfirst($method))) { return $this->callScope($scope, $parameters); } else { $result = call_user_func_array(array($this->query, $method), $parameters); } return in_array($method, $this->passthru) ? $result : $this; } public function __clone() { $this->query = clone $this->query; } }
