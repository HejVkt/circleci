<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ReplyTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function it_has_an_owner()
    {
        $reply = create('App\Reply');

        $this->assertInstanceOf('App\User', $reply->owner);
    }

    /** @test */
    public function it_knows_if_it_was_just_publish()
    {
        /** @var \App\Reply $reply */
        $reply = create('App\Reply');
        $this->assertTrue($reply->wasJustPublish());

        $reply->created_at = Carbon::now()->subMonth();
        $this->assertFalse($reply->wasJustPublish());
    }


}
