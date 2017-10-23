<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MentionUsersTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function mentioned_users_in_a_reply_are_notified()
    {
        $john = create('App\User', [
            'name'=>'JohnDoe'
        ]);

        $this->signIn($john);

        $jane = create('App\User', [
            'name'=>'JaneDoe'
        ]);


        $thread = create('App\Thread');

        $reply = make('App\Reply',[
            'body' => '@JaneDoe look at this'
        ]);


        $this->json('post', $thread->path() . '/replies', $reply->toArray());

        $this->assertCount(1, $jane->notifications);

    }

    /** @test */
    public function it_wrapw_mentioned_user_name_in_the_body_within_anchor_tags()
    {

        $john = create('App\User', [
            'name'=>'JohnDoe'
        ]);

        $this->signIn($john);

        $reply = make('App\Reply',[
            'body' => 'Hello @JaneDoe'
        ]);

        $this->assertEquals($reply->body, 'Hello <a href="/profiles/JaneDoe">@JaneDoe</a>');

    }

    /** @test */
    public function it_can_fetch_all_mentioned_users()
    {
        create('App\User',[
            'name' => 'JohnDoe',
        ]);

        create('App\User',[
            'name' => 'JoleDoe',
        ]);

        create('App\User',[
            'name' => 'SuzieDoe',
        ]);

        $res = $this->json('get','/api/users', ['name'=>'Jo']);

        $this->assertCount(2, $res->json());
    }
}
