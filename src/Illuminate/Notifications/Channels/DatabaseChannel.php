<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Notifications\Channel as ChannelContract;

class DatabaseChannel implements ChannelContract
{
    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send(Collection $notifiables, Notification $notification)
    {
        foreach ($notifiables as $notifiable) {
            $this->createNotification($notifiable, $notification);
        }
    }

    /**
     * Create a database notification record for the notifiable.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Illuminate\Notifications\DatabaseNotification
     */
    protected function createNotification($notifiable, Notification $notification)
    {
        $message = $notification->message($notifiable);

        return $notifiable->routeNotificationFor('database')->create([
            'type' => get_class($notification),
            'level' => $message->level,
            'intro' => $message->introLines,
            'outro' => $message->outroLines,
            'action_text' => $message->actionText,
            'action_url' => $message->actionUrl,
            'read' => false,
        ]);
    }
}
