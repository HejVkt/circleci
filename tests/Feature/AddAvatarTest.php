<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AddAvatarTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function only_members_can_add_avatars()
    {

        $this->withExceptionHandling();

        $this->json('POST','/api/users/1/avatar')
        ->assertStatus(401);
    }

    /** @test */
    public function a_valid_avatar_must_be_provided()
    {

        $this->withExceptionHandling()->signIn();

        Storage::fake('publick');

        $this->json('POST','/api/users/'.auth()->id().'/avatar', [
            'avatar' => $file = UploadedFile::fake()->image('avatar.jpg')
        ]);

        Storage::disk('public')->assertExists('avatar/'.$file->hashName());

        $this->assertEquals(asset('storage/avatar/'.$file->hashName()), auth()->user()->avatar_path);
    }


}
