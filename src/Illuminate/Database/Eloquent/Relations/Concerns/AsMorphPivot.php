<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait AsMorphPivot
{
    use AsPivot;

    /**
     * The type of the polymorphic relation.
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * @var string
     */
    protected $morphType;

    /**
     * The value of the polymorphic relation.
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->morphType, $this->morphClass);

        return parent::setKeysForSaveQuery($query);
    }

    /**
     * Delete the pivot model record from the database.
     *
     * @return int
     */
    public function delete()
    {
        if ($this->fireModelEvent('deleting') === false) {
            return 0;
        }

        $query = $this->getDeleteQuery();

        $query->where($this->morphType, $this->morphClass);

        return tap($query->delete(), function () {
            $this->fireModelEvent('deleted', false);
        });
    }

    /**
     * Set the morph type for the pivot.
     *
     * @param  string  $morphType
     * @return $this
     */
    public function setMorphType($morphType)
    {
        $this->morphType = $morphType;

        return $this;
    }

    /**
     * Set the morph class for the pivot.
     *
     * @param  string  $morphClass
     * @return $this
     */
    public function setMorphClass($morphClass)
    {
        $this->morphClass = $morphClass;

        return $this;
    }

    /**
     * Get the queueable identity for the entity.
     *
     * @return mixed
     */
    public function getQueueableId()
    {
        if (isset($this->attributes[$this->getKeyName()])) {
            return $this->getKey();
        }

        return sprintf(
            '%s:%s:%s:%s:%s:%s',
            $this->foreignKey, $this->getAttribute($this->foreignKey),
            $this->relatedKey, $this->getAttribute($this->relatedKey),
            $this->morphType, $this->morphClass
        );
    }

    /**
     * Get a new query to restore one or more models by their queueable IDs.
     *
     * @param  array|int  $ids
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryForRestoration($ids)
    {
        if (is_array($ids)) {
            return $this->newQueryForCollectionRestoration($ids);
        }

        if (! Str::contains($ids, ':')) {
            return parent::newQueryForRestoration($ids);
        }

        $segments = explode(':', $ids);

        return $this->newQueryWithoutScopes()
            ->where($segments[0], $segments[1])
            ->where($segments[2], $segments[3])
            ->where($segments[4], $segments[5]);
    }

    /**
     * Get a new query to restore multiple models by their queueable IDs.
     *
     * @param  array  $ids
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newQueryForCollectionRestoration(array $ids)
    {
        if (! Str::contains($ids[0], ':')) {
            return parent::newQueryForRestoration($ids);
        }

        $query = $this->newQueryWithoutScopes();

        foreach ($ids as $id) {
            $segments = explode(':', $id);

            $query->orWhere(function ($query) use ($segments) {
                return $query->where($segments[0], $segments[1])
                    ->where($segments[2], $segments[3])
                    ->where($segments[4], $segments[5]);
            });
        }

        return $query;
    }
}
