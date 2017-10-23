<?php

namespace Tests\Browser;

use App\User;
use Tests\DuskTestCase;
use Tests\Browser\Helpers\ChromeTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ExampleTest extends ChromeTestCase
{
    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
//        $user = factory(User::class)->create([]);

        $this->browser
//            ->actingAs($user)
            ->visit('/')
            ->waitForText('There');
    }

//    /** @test */
//    public function acting_as_works()
//    {
//
//        $user = factory('App\User')->create();
//        $this->browse(function (Browser $browser) use ($user) {
//
//            $browser->loginAs($user)
//                ->visit('/')
//                ->waitForText($user->name);
//        });
//    }

}
