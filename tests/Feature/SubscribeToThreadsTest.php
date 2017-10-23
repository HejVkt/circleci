<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SubscribeToThreadsTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function a_user_can_subscripbe_to_thread()
    {

        $this->signIn();

        $thread = create('App\Thread');

        $this->post($thread->path() . '/subscriptions', []);

        $this->assertCount(1, $thread->fresh()->subscriptions);
    }

    /** @test */
    public function a_user_can_unsubscripbe_from_thread()
    {
        $this->signIn();
        $thread = create('App\Thread');

        $this->delete($thread->path() . '/subscriptions', []);

        $this->assertCount(0, $thread->subscriptions);
    }
}
