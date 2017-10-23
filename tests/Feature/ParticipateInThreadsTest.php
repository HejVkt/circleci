<?php

namespace Tests\Feature;

use App\Reply;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ParticipateInThreadsTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function unauthenticated_users_may_not_add_replies()
    {
        $this->withExceptionHandling()
            ->post('/threads/some-channel/1/replies', [])
            ->assertRedirect('/login');
    }

    /** @test */
    public function an_authenticated_user_may_participate_in_forum_threads()
    {
        $this->signIn();

        $thread = create('App\Thread');
        $reply = make('App\Reply');

        $this->post($thread->path() . '/replies', $reply->toArray());

        $this->get($thread->path());
        $this->assertDataBaseHas('replies', ['body' => $reply->body]);
        $this->assertEquals(1, $thread->fresh()->replies_count);

    }

    /** @test */
    public function a_reply_requires_a_body()
    {
        $this->withExceptionHandling()->signIn();

        $thread = create('App\Thread');
        $reply = make('App\Reply', ['body' => null]);

        $this->post($thread->path() . '/replies', $reply->toArray())
             ->assertSessionHasErrors('body');
    }

    /** @test */
    public function auth_user_can_delete_reply()
    {

        $this->signIn();

        $reply = create('App\Reply');

        $this->delete("/replies/{$reply->id}")
            ->assertStatus(302);

        $this->assertEquals(0, $reply->thread->fresh()->replies_count);
    }

    /** @test */
    public function a_autorized_user_can_edit_his_reply()
    {
        $this->signIn();
        $reply = create('App\Reply', [
            'id' => 2,
            'user_id' => auth()->id()
        ]);

        $this->patch('/replies/' . $reply->id, ['body' => 'You been changed, fool']);

        $this->assertDatabaseHas('replies', [
            'id' => $reply->id,
            'body' => 'You been changed, fool'
        ]);
    }


    /** @test */
    public function only_autorize_user_can_update_their_replies()
    {
        $this->withExceptionHandling();

        $reply = create('App\Reply');

        $this->patch("/replies/{$reply->id}")
            ->assertRedirect('login');

        $this->signIn();
        $this->patch("/replies/{$reply->id}")
            ->assertStatus(422);
    }

    /** @test */
    public function replies_what_contain_spam_may_not_be_created()
    {
        $this->withExceptionHandling();

        $this->signIn();
        $thread = create('App\Thread');
        $reply = make('App\Reply',[
            'thread_id' => $thread->id,
            'body' => 'Yahoo customer support'
        ]);

        $this->json('post', $thread->path() . '/replies', $reply->toArray())
        ->assertStatus(422);

    }


    /** @test */
    public function user_may_only_reply_a_thread_once_per_minute()
    {
        $this->withExceptionHandling();

        $this->signIn();
        $thread = create('App\Thread');

        $reply = make('App\Reply',[
            'thread_id' => $thread->id,
            'body' => 'new reply'
        ]);

        $this->post($thread->path() . '/replies', $reply->toArray())
            ->assertStatus(200);

        $this->post($thread->path() . '/replies', $reply->toArray())
            ->assertStatus(429);

    }
}
