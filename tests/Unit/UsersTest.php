<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UsersTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function a_user_can_fetch_their_most_recent_reply()
    {
        $user = create('App\User');
        $reply = create('App\Reply', ['user_id' => $user->id]);
        $this->assertEquals($reply->id, $user->lastReply->id);

    }

    /** @test */
    public function a_user_can_determine_their_avatar_path()
    {
        $user = create('App\User');
        $this->assertEquals(asset('/storage/avatar/default.jpg'), $user->avatar_path);

        $user->update([
            'avatar_path' => 'avatar/123.jpg'
        ]);
        $this->assertEquals(asset('/storage/avatar/123.jpg'), $user->avatar_path);

    }
}
