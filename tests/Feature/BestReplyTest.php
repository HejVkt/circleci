<?php

namespace Tests\Feature;

use App\Reply;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BestReplyTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function a_thread_creator_can_mark_reply_as_best_reply()
    {
        $this->signIn();
        $thread = factory('App\Thread')->create(['user_id' => auth()->id()]);
        $replies = create('App\Reply', ['thread_id'=> $thread->id], 2);

        $this->assertFalse($replies[0]->fresh()->isBest());
        $this->postJson(route('best-reply.store', $replies[0]->id));
        $this->assertTrue($replies[0]->fresh()->isBest());
        $this->assertFalse($replies[1]->fresh()->isBest());

    }

    /** @test */
    public function only_thread_creator_can_mark_reply_as_best()
    {
        $this->withExceptionHandling();

        $this->signIn();
        $thread = factory('App\Thread')->create(['user_id' => auth()->id()]);
        $replies = create('App\Reply', ['thread_id'=> $thread->id], 2);

        $this->signIn(create('App\User'));
        $this->postJson(route('best-reply.store', $replies[0]->id))->assertStatus(403);
        $this->assertFalse($replies[0]->fresh()->isBest());
    }

}
