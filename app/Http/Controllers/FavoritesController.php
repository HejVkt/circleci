<?php

namespace App\Http\Controllers;

use App\Favorite;
use App\Reply;

class FavoritesController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth')->only('store');
    }

    /**
     * @param $channelId
     * @param Thread $thread
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Reply $reply)
    {
        $reply->favorite();

        if(request()->wantsJson()){
            return response(['status'=>'favorited']);
        }

        return back();
    }

    public function destroy(Reply $reply){

        $reply->unfavorite();

        if(request()->wantsJson()){
            return response(['status'=>'unfavorited']);
        }

        return back();
    }

}