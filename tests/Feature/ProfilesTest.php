<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ProfilesTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function a_user_has_a_profile()
    {
        $user = create('App\User');

        $this->get(route('profile', $user))
            ->assertSee($user->name);

    }

    /** @test */
    function show_all_thread_created_by_user()
    {

        $this->signIn();

        $threadsByUser = create('App\Thread', ['user_id' => auth()->id()]);

        $this->get('/profiles/'.auth()->user()->name)
            ->assertSee($threadsByUser->title)
            ->assertSee($threadsByUser->body)
        ;

    }
}
