<?php

namespace Illuminate\Notifications;

use Illuminate\Database\Eloquent\SoftDeletes;

trait HasDatabaseNotifications
{
    /**
     * Get the entity's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    /**
     * Get the entity's read notifications.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function readNotifications()
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    /**
     * Get the entity's unread notifications.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Boot the trait on the notifiable model.
     *
     * @return void
     */
    public static function bootHasDatabaseNotifications()
    {
        static::deleting(function ($notifiable) {
            if (in_array(SoftDeletes::class, class_uses_recursive($notifiable))) {
                if (! $notifiable->forceDeleting) {
                    return;
                }
            }

            $notifiable->notifications->each->delete();
        });
    }
}
