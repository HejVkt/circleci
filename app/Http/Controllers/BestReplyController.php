<?php

namespace App\Http\Controllers;

use App\Reply;
use Illuminate\Http\Request;

class BestReplyController extends Controller
{
    public function store(Reply $reply)
    {
        $this->authorize('update', $reply->thread);
        $reply->markReplyAsBest();
    }

}
