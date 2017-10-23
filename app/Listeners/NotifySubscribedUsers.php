<?php

namespace App\Listeners;

use App\Events\ThreadRecivedNewReply;

class NotifySubscribedUsers
{
    /**
     * Handle the event.
     *
     * @param  ThreadRecivedNewReply $event
     * @return void
     */
    public function handle(ThreadRecivedNewReply $event)
    {

        $event->reply->thread->subscriptions
            ->where('user_id', '!=', $event->reply->user_id)
            ->each
            ->notify($event->reply);

        //
    }
}
