<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_upload_replace_and_delete_a_profile_photo(): void
    {
        Storage::fake('local');

        $user = User::factory()->employe()->create();
        $first = UploadedFile::fake()->image('portrait.jpg', 80, 80);
        $second = UploadedFile::fake()->image('nouveau.png', 90, 90);

        $this->actingAs($user)
            ->post('/profile/photo', ['photo' => $first])
            ->assertRedirect()
            ->assertSessionHas('status', 'Photo de profil enregistrée.');

        $user->refresh();
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('local')->assertExists($user->profile_photo_path);
        $previous = $user->profile_photo_path;

        $this->actingAs($user)
            ->post('/profile/photo', ['photo' => $second])
            ->assertRedirect();

        $user->refresh();
        $this->assertNotSame($previous, $user->profile_photo_path);
        Storage::disk('local')->assertMissing($previous);
        Storage::disk('local')->assertExists($user->profile_photo_path);

        $this->actingAs($user)
            ->delete('/profile/photo')
            ->assertRedirect()
            ->assertSessionHas('status', 'Photo de profil supprimée.');

        $user->refresh();
        $this->assertNull($user->profile_photo_path);
    }

    public function test_profile_photo_rejects_invalid_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/photo', [
                'photo' => UploadedFile::fake()->create('note.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');

        $this->actingAs($user)
            ->post('/profile/photo', [
                'photo' => UploadedFile::fake()->image('large.jpg')->size(3000),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_a_profile_photo_is_visible_in_the_same_organization_only(): void
    {
        Storage::fake('local');

        $owner = User::factory()->bossPrincipal()->create();
        $colleague = User::factory()->employe()->create([
            'organization_id' => $owner->organization_id,
        ]);
        $outsider = User::factory()->employe()->create();

        $this->actingAs($owner)->post('/profile/photo', [
            'photo' => UploadedFile::fake()->image('moi.jpg', 60, 60),
        ])->assertRedirect();

        $owner->refresh();

        $this->actingAs($colleague)
            ->get(route('users.photo', $owner))
            ->assertOk();

        $this->actingAs($outsider)
            ->get('/utilisateurs/'.$owner->id.'/photo')
            ->assertNotFound();
    }
}
