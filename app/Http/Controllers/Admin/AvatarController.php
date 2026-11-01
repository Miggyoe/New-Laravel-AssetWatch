<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    /** Photos are personal data, so they are only served to signed-in administrators (no public storage link). */
    public function show(User $user)
    {
        $disk = Storage::disk('local');
        abort_unless($user->avatar && ! str_starts_with($user->avatar, 'http') && $disk->exists($user->avatar), 404);

        return response()->file($disk->path($user->avatar), [
            'Content-Type'  => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
