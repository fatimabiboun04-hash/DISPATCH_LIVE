<?php
// app/Models/Repo.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repo extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'motif', 'duree', 'statut'];

    // Repo appartient à un User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
