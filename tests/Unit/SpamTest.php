<?php

namespace Tests\Feature;

use App\Inspections\Spam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SpamTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function it_checks_for_invalid_keywords()
    {
        $spam = new Spam();
        $this->assertFalse($spam->detect('Innocent reply here'));

        $this->expectException('Exception');
        $spam->detect('Yahoo customer support');

    }

    /** @test */
    public function it_checks_for_any_key_bind_hold_down()
    {
        $spam = new Spam();

        $this->expectException('Exception');

        $spam->detect('Hello world aaaaaaaaaaaaaaaaaaaaaa');
    }

}
