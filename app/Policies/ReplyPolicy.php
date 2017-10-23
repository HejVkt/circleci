<?php

namespace App\Policies;

use App\Reply;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReplyPolicy
{
    use HandlesAuthorization;


    public function update(User $user, Reply $reply){

        return (auth()->id() === $user->id);

    }

    public function create(User $user, Reply $reply)
    {
        $lastReply = $user->fresh()->lastReply;

        if(!$lastReply){
            return true;
        }

        return ! $lastReply->wasJustPublish();
    }
}
