<?php

namespace App;

use App\Events\ThreadRecivedNewReply;
use App\Filters\ThreadFilters;
use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    use RecordsActivity;

    protected $guarded = [];

    protected $with = ['creator', 'channel'];

    protected $appends = ['isSubscribedTo'];

    protected static function boot()
    {
        parent::boot();
        /*
        static::addGlobalScope('replyCount', function ($builder) {
            $builder->withCount('replies');
        });
        */
        static::deleting(function ($thread) {
            $thread->replies->each->delete();
        });

        static::created(function ($thread){
           $thread->update(['slug' => $thread->title]);
        });
    }

    public function path()
    {
        return "/threads/{$this->channel->slug}/{$this->slug}";
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param $reply
     * @return Reply
     */
    public function addReply($reply)
    {
        $reply = $this->replies()->create($reply);

        event(new ThreadRecivedNewReply($reply));

        return $reply;
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function scopeFilter($query, ThreadFilters $filters)
    {
        return $filters->apply($query);
    }

    /**
     * @param null $userId
     * @return Model
     */
    public function subscribe($userId = null)
    {
        $this->subscriptions()->create([
            'user_id' => $userId ?: auth()->id(),
            'thread_id' => $this->id
        ]);

        return $this;
    }

    /**
     * @param null $userId
     * @return Model
     */
    public function unsubscribe($userId = null)
    {

        return $this->subscriptions()
            ->where('user_id', $userId ?: auth()->id())
            ->delete();
    }

    public function subscriptions()
    {
        return $this->hasMany(ThreadSubscription::class);
    }

    public function getIsSubscribedToAttribute()
    {
        return $this->subscriptions()
            ->where('user_id', auth()
                ->id())->exists();
    }

    public function hasUpdatedFor($user)
    {
        $key = sprintf('user.%s.thread%s', $user->id, $this->id);
        return $this->updated_at > cache($key);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function setSlugAttribute($value)
    {
        $slug = str_slug($value);
        if(static::whereSlug($slug)->exists()){
            $slug = $slug . '-' . $this->id;
        }

        $this->attributes['slug'] = $slug;
    }

    private function incrementingSlug($slug)
    {
        $max = static::whereTitle($this->title)->latest('id')->value('slug');

        if (is_numeric($max[-1])) {
            return preg_replace_callback('/(\d+)$/', function ($matches) {
                return $matches[1] + 1;
            }, $max);
        }

        return $slug . '-2';
    }
}
