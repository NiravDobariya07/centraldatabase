<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_image',
        'two_fa_enabled',
        'two_fa_method'
    ];

    protected $appends = ['profile_image_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image
            ? url(Storage::url($this->profile_image))
            : asset('img/avatars/admin.png'); // Default image
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_fa_enabled' => 'boolean',
        ];
    }

    public function twoFactorCodes() {
        return $this->hasMany(TwoFactorCode::class, 'user_id', 'id')
            ->orderBy('created_at', 'desc')
            ->where('expires_at', '>=', now()
        );
    }

    public function exports() {
        return $this->hasMany(Export::class, 'user_id', 'id');
    }

    public function exportFiles() {
        return $this->hasMany(ExportFile::class, 'user_id', 'id');
    }
}
