<?php
// app/Models/User.php
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
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // User appartient à une Equipe
    public function equipe()
    {
        return $this->belongsTo(Equipe::class);
    }

    // User a plusieurs Plannings
    public function plannings()
    {
        return $this->hasMany(Planning::class);
    }

    // User a plusieurs Repos
    public function repos()
    {
        return $this->hasMany(Repo::class);
    }
}
