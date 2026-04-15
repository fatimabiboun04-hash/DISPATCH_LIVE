<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nom',
        'email',
        'password',
        'role',
        'equipe_id',
        'rating',
        'description',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
        'rating'            => 'integer',
    ];

    public function equipe()
    {
        return $this->belongsTo(Equipe::class);
    }

    public function plannings()
    {
        return $this->hasMany(Planning::class);
    }

    public function repos()
    {
        return $this->hasMany(Repo::class);
    }
}
