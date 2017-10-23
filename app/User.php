<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class User
 * @package App
 */
class User extends Authenticatable
{
    use Notifiable;

    public function getRouteKeyName()
    {
        return 'name';
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path'
    ];

    protected $casts = [
        'confirmed' => 'boolean'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email'
    ];

    public function confirm()
    {
        $this->confirmed = true;
        $this->confirmation_token = null;
        $this->save();
    }

    /**
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function threads()
    {
        return $this->hasMany(Thread::class)->latest();
    }

    public function activity()
    {
        return $this->hasMany(Activity::class, 'user_id');
    }

    public function visitedCacheKeyForThreads($thread)
    {
        return sprintf('user.%s.thread%s', $this->id, $thread->id);
    }

    public function readThread($thread)
    {

        cache()->forever(
            $this->visitedCacheKeyForThreads($thread),
            \Carbon\Carbon::now()
        );
    }

    public function lastReply()
    {

        return $this->hasOne(Reply::class)->latest();
    }

    public function getAvatarPathAttribute($value){
        return $value ? asset('storage/'.$value) : asset('storage/avatar/default.jpg');
    }

}
