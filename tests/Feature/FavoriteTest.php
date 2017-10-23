<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class FavoriteTest extends TestCase
{

    use DatabaseMigrations;

    /** @test*/
    public function guest_cannot_favorite_anything(){

        $this->withExceptionHandling();

        $this->post('/replies/1/favorites')
            ->assertRedirect('/login');

    }

    /** @test */
    public function an_autentificated_user_can_favorite_any_reply()
    {
        $this->signIn();

        $reply = create('App\Reply');

        $this->post('/replies/'.$reply->id.'/favorites');

        $this->assertCount(1, $reply->favorites);
    }

    /** @test */
    public function an_autentificated_user_may_only_favorite_a_reply_once()
    {

        $this->signIn();

        $reply = create('App\Reply');

        $this->post('/replies/'.$reply->id.'/favorites');
        $this->post('/replies/'.$reply->id.'/favorites');


        $this->assertCount(1, $reply->favorites);

    }

    /** @test */
    public function autentificated_user_may_unfavorite_a_reply()
    {

        $this->signIn();

        $reply = create('App\Reply');

        $reply->favorite();

        $this->post('/replies/'.$reply->id.'/favorites');
        $this->assertCount(1, $reply->favorites);

        $this->delete('/replies/'.$reply->id.'/favorites');
        $this->assertCount(0, $reply->fresh()->favorites);

    }

}
