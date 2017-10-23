<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Reply extends Model
{
    use Favoritable, RecordsActivity;

    protected $guarded = [];

    protected $appends = ['favoritesCount', 'isFavorited', 'isBest'];

    protected $with = ['owner', 'favorites'];

    protected static function boot()
    {
        parent::boot();

        static::created(function($reply){
            $reply->thread->increment('replies_count');
        });

        static::deleted(function($reply){
            $reply->thread->decrement('replies_count');
        });

    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function thread(){
        return $this->belongsTo(Thread::class);
    }

    public function path(){
        return $this->thread->path() . '#reply-' . $this->id;
    }

    public function wasJustPublish(){

        return $this->created_at->gt(Carbon::now()->subMinute());
    }

    public function mentionedUsers(){

        preg_match_all('/@([\w\-]+)/', $this->body, $matches);

        return $matches[1];

    }

    public function setBodyAttribute($value){

        $this->attributes['body'] = preg_replace('/@([\w\-]+)/', '<a href="/profiles/$1">$0</a>', $value);

        return $value;
    }

    public function isBest(){
        return $this->thread->bestr_reply_id == $this->id;
    }

    public function markReplyAsBest()
    {
        $this->thread->update(['bestr_reply_id' => $this->id]);

    }

    public function getIsBestAttribute(){
        return $this->isBest();
    }

}
