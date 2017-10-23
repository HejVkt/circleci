<?php

namespace Tests\Feature;

use App\Activity;
use App\Thread;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ManageThreadsTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function guests_may_not_create_threads()
    {
        $this->withExceptionHandling();

        $this->get('/threads/create')
            ->assertRedirect('/login');

        $this->post('/threads')
            ->assertRedirect('/login');
    }

    /** @test */
    public function only_authorized_confirmed_user_may_create_a_thread()
    {
        $user = factory('App\User')->states('unconfirm')->create();
        $this->signIn($user);

        $thread = make('App\Thread');

        $this->post('/threads', $thread->toArray())->assertRedirect('/threads')
            ->assertSessionHas('flash', 'You must first confirm your email adress.');;

    }

    /** @test */
    public function create_unique_thread_slug()
    {
        $this->signIn();

        $thread = factory('App\Thread')->create(['title' => 'help-me']);
        $this->assertEquals($thread->slug, str_slug($thread->title));

        factory('App\Thread', 2)->create();

        $this->post('threads', $thread->toArray());
        $this->assertTrue(Thread::whereSlug($thread->slug . '-4')->exists());

        $thread = $this->postJson('threads', $thread->toArray())->json();
        $this->assertEquals($thread['title'] . '-' .$thread['id'], $thread['slug']);

    }

    /** @test */
    public function an_authenticated_user_can_create_new_forum_threads()
    {
        $this->signIn();

        $thread = make('App\Thread');

        $response = $this->post('/threads', $thread->toArray());

        $this->get($response->headers->get('Location'))
            ->assertSee($thread->title)
            ->assertSee($thread->body);
    }

    /** @test */
    public function a_thread_requires_a_title()
    {
        $this->publishThread(['title' => null])
            ->assertSessionHasErrors('title');
    }

    /** @test */
    public function a_thread_requires_a_body()
    {
        $this->publishThread(['body' => null])
            ->assertSessionHasErrors('body');
    }

    /** @test */
    public function a_thread_requires_a_valid_channel()
    {
        factory('App\Channel', 2)->create();

        $this->publishThread(['channel_id' => null])
            ->assertSessionHasErrors('channel_id');

        $this->publishThread(['channel_id' => 999])
            ->assertSessionHasErrors('channel_id');
    }

    /** @test */
    public function gues_cannot_delete_threads()
    {
        $this->withExceptionHandling();

        $thread = create('App\Thread');
        $this->delete($thread->path())
            ->assertStatus(302);

        $this->signIn();
        $this->delete($thread->path())
            ->assertStatus(403);
    }

    /** @test */
    public function a_user_can_delete_thread()
    {

        $this->signIn();
        $thread = create('App\Thread', ['user_id' => auth()->id()]);
        $reply = create('App\Reply', ['thread_id' => $thread->id]);

        $response = $this->json('DELETE', $thread->path());
        $response->assertStatus(204);

        $this->assertDatabaseMissing('threads', ['id' => $thread->id]);
        $this->assertDatabaseMissing('replies', ['id' => $reply->id]);

        $this->assertDatabaseMissing('activities',
            [
                'subject_id' => $thread->id,
                'subject_type' => get_class($thread),
            ]
        );

        $this->assertDatabaseMissing('activities',
            [
                'subject_id' => $reply->id,
                'subject_type' => get_class($reply),
            ]
        );

        $this->assertEquals(0, Activity::count());
    }


    protected function publishThread($overrides = [])
    {
        $this->withExceptionHandling()->signIn();

        $thread = make('App\Thread', $overrides);

        return $this->post('/threads', $thread->toArray());
    }
}
