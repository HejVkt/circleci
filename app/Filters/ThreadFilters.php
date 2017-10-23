<?php

namespace App\Filters;

use App\User;

class ThreadFilters extends Filters
{

    protected $filters = ['by', 'popular', 'unanswered'];

    /**
     * Filter the query by a username
     * @param $username
     * @return mixed
     */
    protected function by($username)
    {
        $user = User::where('name', $username)->firstOrFail();

        return $this->builder->where('user_id', $user->id);
    }


    /**
     * Filter the query according to most popular threads
     * @param $order
     * @return mixed
     */
    protected function popular($order)
    {
        return $this->builder->orderBy('replies_count', 'DESC');
    }

    protected function unanswered($order)
    {
        return $this->builder->where('replies_count', 0);
    }

}