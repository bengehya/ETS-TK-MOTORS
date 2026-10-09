<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfilePhotoService
{
    private const DISK = 'local';

    public function store(User $user, UploadedFile $file): void
    {
        $directory = 'profile-photos/'.$user->id;
        $path = $file->store($directory, self::DISK);

        if ($path === false) {
            abort(500, 'La photo n’a pas pu être enregistrée.');
        }

        $previous = $user->profile_photo_path;

        $user->forceFill(['profile_photo_path' => $path])->save();

        if (is_string($previous) && $previous !== '' && $previous !== $path) {
            $this->deleteStoredFile($previous);
        }
    }

    public function clear(User $user): void
    {
        $previous = $user->profile_photo_path;
        $user->forceFill(['profile_photo_path' => null])->save();
        $this->deleteStoredFile($previous);
    }

    public function deleteStoredFile(?string $path): void
    {
        if (! is_string($path) || $path === '' || str_contains($path, '..')) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function response(User $user): StreamedResponse
    {
        $path = $user->profile_photo_path;

        abort_unless($this->isSafePath($user, $path), 404);
        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->response($path, basename($path), [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function isSafePath(User $user, mixed $path): bool
    {
        return is_string($path)
            && $path !== ''
            && ! str_contains($path, '..')
            && str_starts_with($path, 'profile-photos/'.$user->id.'/');
    }
}
