<?php

namespace Tests\Unit;

use App\Notifications\ThreadWasUpdated;
use App\Reply;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ThreadTest extends TestCase
{
    use DatabaseMigrations;

    protected $thread;

    public function setUp()
    {
        parent::setUp();

        $this->thread = create('App\Thread');
    }

    /** @test */
    public function a_thread_can_make_a_string_path()
    {
        $thread = create('App\Thread');

        $this->assertEquals(
            "/threads/{$thread->channel->slug}/{$thread->slug}", $thread->path()
        );
    }

    /** @test */
    public function a_thread_has_a_creator()
    {
        $this->assertInstanceOf('App\User', $this->thread->creator);
    }

    /** @test */
    public function a_thread_has_replies()
    {
        $this->assertInstanceOf(
            'Illuminate\Database\Eloquent\Collection', $this->thread->replies
        );
    }

    /** @test */
    public function a_thread_can_add_a_reply()
    {
        $this->thread->addReply([
            'body' => 'Foobar',
            'user_id' => 1
        ]);

        $this->assertCount(1, $this->thread->replies);
    }

    /** @test */
    public function a_thread_belongs_to_a_channel()
    {
        $thread = create('App\Thread');

        $this->assertInstanceOf('App\Channel', $thread->channel);
    }


    /** @test */
    public function a_thread_can_be_subscribet_to()
    {
        $thread = create('App\Thread');

        $thread->subscribe($userId = 1);

        $this->assertCount(1, $thread->subscriptions()->where('user_id', $userId)->get());

    }

    /** @test */
    public function a_thread_can_be_unsubscribe_from()
    {
        $thread = create('App\Thread');

        $thread->subscribe($userId = 1);

        $thread->unsubscribe($userId);

        $this->assertCount(0, $thread->subscriptions);
    }

    /** @test */
    public function it_know_if_the_autentificated_user_is_subscribedTo()
    {
        $this->signIn();

        $this->assertEquals(false, $this->thread->isSubscribedTo);

        $this->thread->subscribe();

        $this->assertEquals(true, $this->thread->isSubscribedTo);
    }

    /** @test */
    public function a_thread_notifies_all_subscribers_when_a_reply_is_added()
    {
        Notification::fake();

        $this->signIn()->thread
            ->subscribe()
            ->addReply([
                'body' => 'Foobar',
                'user_id' => 999
            ]);

        Notification::assertSentTo(auth()->user(), ThreadWasUpdated::class);

    }

    /** @test */
    public function a_thread_mark_as_read()
    {
        $this->signIn();

        $thread = $this->thread;

        $thread->addReply([
            'body' => 'Foobar',
            'user_id' => 1
        ]);

        $user = auth()->user();

        $this->assertTrue($thread->hasUpdatedFor($user));

        $user->readThread($thread);

        $this->assertFalse($thread->hasUpdatedFor($user));

    }

    /** @test */
    public function we_counts_reading_treads()
    {
        $thread = create('App\Thread')->fresh();

        $this->assertSame('0', $thread->visits);

        $this->call('get', $thread->path());

        $this->assertEquals(1, $thread->fresh()->visits);
    }
}
