<?php namespace Illuminate\Database\Query; use Closure; use Illuminate\Support\Collection; use Illuminate\Database\ConnectionInterface; use Illuminate\Database\Query\Grammars\Grammar; use Illuminate\Database\Query\Processors\Processor; class Builder { protected $connection; protected $grammar; protected $processor; protected $bindings = array(); public $aggregate; public $columns; public $distinct = false; public $from; public $joins; public $wheres; public $groups; public $havings; public $orders; public $limit; public $offset; public $unions; public $lock; protected $cacheKey; protected $cacheMinutes; protected $cacheTags; protected $cacheDriver; protected $operators = array( '=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like', 'between', 'ilike', '&', '|', '^', '<<', '>>', ); public function __construct(ConnectionInterface $connection, Grammar $grammar, Processor $processor) { $this->grammar = $grammar; $this->processor = $processor; $this->connection = $connection; } public function select($columns = array('*')) { $this->columns = is_array($columns) ? $columns : func_get_args(); return $this; } public function selectRaw($expression) { return $this->select(new Expression($expression)); } public function addSelect($column) { $column = is_array($column) ? $column : func_get_args(); $this->columns = array_merge((array) $this->columns, $column); return $this; } public function distinct() { $this->distinct = true; return $this; } public function from($table) { $this->from = $table; return $this; } public function join($table, $one, $operator = null, $two = null, $type = 'inner', $where = false) { if ($one instanceof Closure) { $this->joins[] = new JoinClause($this, $type, $table); call_user_func($one, end($this->joins)); } else { $join = new JoinClause($this, $type, $table); $this->joins[] = $join->on( $one, $operator, $two, 'and', $where ); } return $this; } public function joinWhere($table, $one, $operator, $two, $type = 'inner') { return $this->join($table, $one, $operator, $two, $type, true); } public function leftJoin($table, $first, $operator = null, $second = null) { return $this->join($table, $first, $operator, $second, 'left'); } public function leftJoinWhere($table, $one, $operator, $two) { return $this->joinWhere($table, $one, $operator, $two, 'left'); } public function where($column, $operator = null, $value = null, $boolean = 'and') { if (func_num_args() == 2) { list($value, $operator) = array($operator, '='); } elseif ($this->invalidOperatorAndValue($operator, $value)) { throw new \InvalidArgumentException("Value must be provided."); } if ($column instanceof Closure) { return $this->whereNested($column, $boolean); } if ( ! in_array(strtolower($operator), $this->operators, true)) { list($value, $operator) = array($operator, '='); } if ($value instanceof Closure) { return $this->whereSub($column, $operator, $value, $boolean); } if (is_null($value)) { return $this->whereNull($column, $boolean, $operator != '='); } $type = 'Basic'; $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean'); if ( ! $value instanceof Expression) { $this->bindings[] = $value; } return $this; } public function orWhere($column, $operator = null, $value = null) { return $this->where($column, $operator, $value, 'or'); } protected function invalidOperatorAndValue($operator, $value) { $isOperator = in_array($operator, $this->operators); return ($isOperator && $operator != '=' && is_null($value)); } public function whereRaw($sql, array $bindings = array(), $boolean = 'and') { $type = 'raw'; $this->wheres[] = compact('type', 'sql', 'boolean'); $this->bindings = array_merge($this->bindings, $bindings); return $this; } public function orWhereRaw($sql, array $bindings = array()) { return $this->whereRaw($sql, $bindings, 'or'); } public function whereBetween($column, array $values, $boolean = 'and', $not = false) { $type = 'between'; $this->wheres[] = compact('column', 'type', 'boolean', 'not'); $this->bindings = array_merge($this->bindings, $values); return $this; } public function orWhereBetween($column, array $values, $not = false) { return $this->whereBetween($column, $values, 'or'); } public function whereNotBetween($column, array $values, $boolean = 'and') { return $this->whereBetween($column, $values, $boolean, true); } public function orWhereNotBetween($column, array $values) { return $this->whereNotBetween($column, $values, 'or'); } public function whereNested(Closure $callback, $boolean = 'and') { $query = $this->newQuery(); $query->from($this->from); call_user_func($callback, $query); return $this->addNestedWhereQuery($query, $boolean); } public function addNestedWhereQuery($query, $boolean = 'and') { if (count($query->wheres)) { $type = 'Nested'; $this->wheres[] = compact('type', 'query', 'boolean'); $this->mergeBindings($query); } return $this; } protected function whereSub($column, $operator, Closure $callback, $boolean) { $type = 'Sub'; $query = $this->newQuery(); call_user_func($callback, $query); $this->wheres[] = compact('type', 'column', 'operator', 'query', 'boolean'); $this->mergeBindings($query); return $this; } public function whereExists(Closure $callback, $boolean = 'and', $not = false) { $type = $not ? 'NotExists' : 'Exists'; $query = $this->newQuery(); call_user_func($callback, $query); $this->wheres[] = compact('type', 'operator', 'query', 'boolean'); $this->mergeBindings($query); return $this; } public function orWhereExists(Closure $callback, $not = false) { return $this->whereExists($callback, 'or', $not); } public function whereNotExists(Closure $callback, $boolean = 'and') { return $this->whereExists($callback, $boolean, true); } public function orWhereNotExists(Closure $callback) { return $this->orWhereExists($callback, true); } public function whereIn($column, $values, $boolean = 'and', $not = false) { $type = $not ? 'NotIn' : 'In'; if ($values instanceof Closure) { return $this->whereInSub($column, $values, $boolean, $not); } $this->wheres[] = compact('type', 'column', 'values', 'boolean'); $this->bindings = array_merge($this->bindings, $values); return $this; } public function orWhereIn($column, $values) { return $this->whereIn($column, $values, 'or'); } public function whereNotIn($column, $values, $boolean = 'and') { return $this->whereIn($column, $values, $boolean, true); } public function orWhereNotIn($column, $values) { return $this->whereNotIn($column, $values, 'or'); } protected function whereInSub($column, Closure $callback, $boolean, $not) { $type = $not ? 'NotInSub' : 'InSub'; call_user_func($callback, $query = $this->newQuery()); $this->wheres[] = compact('type', 'column', 'query', 'boolean'); $this->mergeBindings($query); return $this; } public function whereNull($column, $boolean = 'and', $not = false) { $type = $not ? 'NotNull' : 'Null'; $this->wheres[] = compact('type', 'column', 'boolean'); return $this; } public function orWhereNull($column) { return $this->whereNull($column, 'or'); } public function whereNotNull($column, $boolean = 'and') { return $this->whereNull($column, $boolean, true); } public function orWhereNotNull($column) { return $this->whereNotNull($column, 'or'); } public function whereDay($column, $operator, $value, $boolean = 'and') { return $this->addDateBasedWhere('Day', $column, $operator, $value, $boolean); } public function whereMonth($column, $operator, $value, $boolean = 'and') { return $this->addDateBasedWhere('Month', $column, $operator, $value, $boolean); } public function whereYear($column, $operator, $value, $boolean = 'and') { return $this->addDateBasedWhere('Year', $column, $operator, $value, $boolean); } protected function addDateBasedWhere($type, $column, $operator, $value, $boolean = 'and') { $this->wheres[] = compact('column', 'type', 'boolean', 'operator', 'value'); $this->bindings[] = $value; return $this; } public function dynamicWhere($method, $parameters) { $finder = substr($method, 5); $segments = preg_split('/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE); $connector = 'and'; $index = 0; foreach ($segments as $segment) { if ($segment != 'And' && $segment != 'Or') { $this->addDynamic($segment, $connector, $parameters, $index); $index++; } else { $connector = $segment; } } return $this; } protected function addDynamic($segment, $connector, $parameters, $index) { $bool = strtolower($connector); $this->where(snake_case($segment), '=', $parameters[$index], $bool); } public function groupBy() { $this->groups = array_merge((array) $this->groups, func_get_args()); return $this; } public function having($column, $operator = null, $value = null) { $type = 'basic'; $this->havings[] = compact('type', 'column', 'operator', 'value'); $this->bindings[] = $value; return $this; } public function havingRaw($sql, array $bindings = array(), $boolean = 'and') { $type = 'raw'; $this->havings[] = compact('type', 'sql', 'boolean'); $this->bindings = array_merge($this->bindings, $bindings); return $this; } public function orHavingRaw($sql, array $bindings = array()) { return $this->havingRaw($sql, $bindings, 'or'); } public function orderBy($column, $direction = 'asc') { $direction = strtolower($direction) == 'asc' ? 'asc' : 'desc'; $this->orders[] = compact('column', 'direction'); return $this; } public function latest($column = 'created_at') { return $this->orderBy($column, 'desc'); } public function oldest($column = 'created_at') { return $this->orderBy($column, 'asc'); } public function orderByRaw($sql, $bindings = array()) { $type = 'raw'; $this->orders[] = compact('type', 'sql'); $this->bindings = array_merge($this->bindings, $bindings); return $this; } public function offset($value) { $this->offset = max(0, $value); return $this; } public function skip($value) { return $this->offset($value); } public function limit($value) { if ($value > 0) $this->limit = $value; return $this; } public function take($value) { return $this->limit($value); } public function forPage($page, $perPage = 15) { return $this->skip(($page - 1) build composer.json composer.lock CONTRIBUTING.md LICENSE.txt phpmin.sh phpunit.php phpunit.xml readme.md src tests $perPage)->take($perPage); } public function union($query, $all = false) { if ($query instanceof Closure) { call_user_func($query, $query = $this->newQuery()); } $this->unions[] = compact('query', 'all'); return $this->mergeBindings($query); } public function unionAll($query) { return $this->union($query, true); } public function lock($value = true) { $this->lock = $value; return $this; } public function lockForUpdate() { return $this->lock(true); } public function sharedLock() { return $this->lock(false); } public function toSql() { return $this->grammar->compileSelect($this); } public function remember($minutes, $key = null) { list($this->cacheMinutes, $this->cacheKey) = array($minutes, $key); return $this; } public function rememberForever($key = null) { return $this->remember(-1, $key); } public function cacheTags($cacheTags) { $this->cacheTags = $cacheTags; return $this; } public function cacheDriver($cacheDriver) { $this->cacheDriver = $cacheDriver; return $this; } public function find($id, $columns = array('*')) { return $this->where('id', '=', $id)->first($columns); } public function pluck($column) { $result = (array) $this->first(array($column)); return count($result) > 0 ? reset($result) : null; } public function first($columns = array('*')) { $results = $this->take(1)->get($columns); return count($results) > 0 ? reset($results) : null; } public function get($columns = array('*')) { if ( ! is_null($this->cacheMinutes)) return $this->getCached($columns); return $this->getFresh($columns); } public function getFresh($columns = array('*')) { if (is_null($this->columns)) $this->columns = $columns; return $this->processor->processSelect($this, $this->runSelect()); } protected function runSelect() { return $this->connection->select($this->toSql(), $this->bindings); } public function getCached($columns = array('*')) { if (is_null($this->columns)) $this->columns = $columns; list($key, $minutes) = $this->getCacheInfo(); $cache = $this->getCache(); $callback = $this->getCacheCallback($columns); if ($minutes < 0) { return $cache->rememberForever($key, $callback); } else { return $cache->remember($key, $minutes, $callback); } } protected function getCache() { $cache = $this->connection->getCacheManager()->driver($this->cacheDriver); return $this->cacheTags ? $cache->tags($this->cacheTags) : $cache; } protected function getCacheInfo() { return array($this->getCacheKey(), $this->cacheMinutes); } public function getCacheKey() { return $this->cacheKey ?: $this->generateCacheKey(); } public function generateCacheKey() { $name = $this->connection->getName(); return md5($name.$this->toSql().serialize($this->bindings)); } protected function getCacheCallback($columns) { return function() use ($columns) { return $this->getFresh($columns); }; } public function chunk($count, $callback) { $results = $this->forPage($page = 1, $count)->get(); while (count($results) > 0) { call_user_func($callback, $results); $page++; $results = $this->forPage($page, $count)->get(); } } public function lists($column, $key = null) { $columns = $this->getListSelect($column, $key); $results = new Collection($this->get($columns)); $values = $results->fetch($columns[0])->all(); if ( ! is_null($key) && count($results) > 0) { $keys = $results->fetch($key)->all(); return array_combine($keys, $values); } return $values; } protected function getListSelect($column, $key) { $select = is_null($key) ? array($column) : array($column, $key); if (($dot = strpos($select[0], '.')) !== false) { $select[0] = substr($select[0], $dot + 1); } return $select; } public function implode($column, $glue = null) { if (is_null($glue)) return implode($this->lists($column)); return implode($glue, $this->lists($column)); } public function paginate($perPage = 15, $columns = array('*')) { $paginator = $this->connection->getPaginator(); if (isset($this->groups)) { return $this->groupedPaginate($paginator, $perPage, $columns); } else { return $this->ungroupedPaginate($paginator, $perPage, $columns); } } protected function groupedPaginate($paginator, $perPage, $columns) { $results = $this->get($columns); return $this->buildRawPaginator($paginator, $results, $perPage); } public function buildRawPaginator($paginator, $results, $perPage) { $start = ($paginator->getCurrentPage() - 1) build composer.json composer.lock CONTRIBUTING.md LICENSE.txt phpmin.sh phpunit.php phpunit.xml readme.md src tests $perPage; $sliced = array_slice($results, $start, $perPage); return $paginator->make($sliced, count($results), $perPage); } protected function ungroupedPaginate($paginator, $perPage, $columns) { $total = $this->getPaginationCount(); $page = $paginator->getCurrentPage($total); $results = $this->forPage($page, $perPage)->get($columns); return $paginator->make($results, $total, $perPage); } public function getPaginationCount() { list($orders, $this->orders) = array($this->orders, null); $columns = $this->columns; $total = $this->count(); $this->orders = $orders; $this->columns = $columns; return $total; } public function exists() { return $this->count() > 0; } public function count($column = '*') { return $this->aggregate(__FUNCTION__, array($column)); } public function min($column) { return $this->aggregate(__FUNCTION__, array($column)); } public function max($column) { return $this->aggregate(__FUNCTION__, array($column)); } public function sum($column) { return $this->aggregate(__FUNCTION__, array($column)); } public function avg($column) { return $this->aggregate(__FUNCTION__, array($column)); } public function aggregate($function, $columns = array('*')) { $this->aggregate = compact('function', 'columns'); $results = $this->get($columns); $this->columns = null; $this->aggregate = null; if (isset($results[0])) { $result = (array) $results[0]; return $result['aggregate']; } } public function insert(array $values) { if ( ! is_array(reset($values))) { $values = array($values); } else { foreach ($values as $key => $value) { ksort($value); $values[$key] = $value; } } $bindings = array(); foreach ($values as $record) { $bindings = array_merge($bindings, array_values($record)); } $sql = $this->grammar->compileInsert($this, $values); $bindings = $this->cleanBindings($bindings); return $this->connection->insert($sql, $bindings); } public function insertGetId(array $values, $sequence = null) { $sql = $this->grammar->compileInsertGetId($this, $values, $sequence); $values = $this->cleanBindings($values); return $this->processor->processInsertGetId($this, $sql, $values, $sequence); } public function update(array $values) { $bindings = array_values(array_merge($values, $this->bindings)); $sql = $this->grammar->compileUpdate($this, $values); return $this->connection->update($sql, $this->cleanBindings($bindings)); } public function increment($column, $amount = 1, array $extra = array()) { $wrapped = $this->grammar->wrap($column); $columns = array_merge(array($column => $this->raw("$wrapped + $amount")), $extra); return $this->update($columns); } public function decrement($column, $amount = 1, array $extra = array()) { $wrapped = $this->grammar->wrap($column); $columns = array_merge(array($column => $this->raw("$wrapped - $amount")), $extra); return $this->update($columns); } public function delete($id = null) { if ( ! is_null($id)) $this->where('id', '=', $id); $sql = $this->grammar->compileDelete($this); return $this->connection->delete($sql, $this->bindings); } public function truncate() { foreach ($this->grammar->compileTruncate($this) as $sql => $bindings) { $this->connection->statement($sql, $bindings); } } public function newQuery() { return new Builder($this->connection, $this->grammar, $this->processor); } public function mergeWheres($wheres, $bindings) { $this->wheres = array_merge((array) $this->wheres, (array) $wheres); $this->bindings = array_values(array_merge($this->bindings, (array) $bindings)); } protected function cleanBindings(array $bindings) { return array_values(array_filter($bindings, function($binding) { return ! $binding instanceof Expression; })); } public function raw($value) { return $this->connection->raw($value); } public function getBindings() { return $this->bindings; } public function setBindings(array $bindings) { $this->bindings = $bindings; return $this; } public function addBinding($value) { $this->bindings[] = $value; return $this; } public function mergeBindings(Builder $query) { $this->bindings = array_values(array_merge($this->bindings, $query->bindings)); return $this; } public function getConnection() { return $this->connection; } public function getProcessor() { return $this->processor; } public function getGrammar() { return $this->grammar; } public function __call($method, $parameters) { if (starts_with($method, 'where')) { return $this->dynamicWhere($method, $parameters); } $className = get_class($this); throw new \BadMethodCallException("Call to undefined method {$className}::{$method}()"); } }
