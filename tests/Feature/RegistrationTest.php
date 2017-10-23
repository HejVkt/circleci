<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Mail\PleaseConfirmYourRegistartion;

class RegistrationTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function send_confirm_email_registration_to_user()
    {
        Mail::fake();
        $this->post('/register', [
            'name' => 'Joxczhn',
            'email' => 'tesxzxczxct@test.ru',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);

        Mail::assertSent(PleaseConfirmYourRegistartion::class);
    }

    /** @test */
    public function user_can_confirm_his_registration()
    {
        Mail::fake();
        $this->post('/register', [
            'name' => 'Joxczhn',
            'email' => 'tesxzxczxct@test.ru',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);

        $user = User::first();

        $this->assertFalse($user->confirmed);
        $this->assertNotEmpty($user->confirmation_token);

        $response = $this->get(route('register.confirm', ['token' => $user->confirmation_token]));
        $response->assertRedirect(route('threads'));

        $this->assertTrue($user->fresh()->confirmed);
        $this->assertNull($user->fresh()->confirmation_token);

    }

    /** @test */
    public function cannot_confirm_registartion_with_invalid_token()
    {
        $response = $this->get(route('register.confirm', ['token' => 'invalid']));
        $response->assertRedirect(route('threads'));
        $response->assertSessionHas('flash', 'Invalid token');

    }
}
