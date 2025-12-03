<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use App\Notifications\ResetPasswordNotification;

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
        if (!$this->profile_image) {
            return asset('img/avatars/admin.png'); // Default image
        }

        // Use the public disk to generate the correct URL
        // Storage::url() with 'public' disk returns the path relative to /storage
        return asset('storage/' . $this->profile_image);
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

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
