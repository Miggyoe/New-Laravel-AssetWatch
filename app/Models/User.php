<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mass-assignable attributes. "role" is deliberately NOT here: it is only ever set explicitly in code.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'last_login_at'     => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Profile photo as a URL, or null (the interface then shows the initial).
     * Photos synced from Microsoft are kept private on the server and served through the signed-in portal.
     */
    public function avatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        if (! Route::has('admin.avatars.show') || ! Storage::disk('local')->exists($this->avatar)) {
            return null;
        }

        return route('admin.avatars.show', ['user' => $this->getKey(), 'v' => $this->updated_at?->timestamp]);
    }
}
