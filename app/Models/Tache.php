<?php
// app/Models/Tache.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tache extends Model
{
    use HasFactory;

    protected $fillable = ['titre', 'description', 'is_permanent'];

    protected $casts = [
        'is_permanent' => 'boolean',
    ];

    // Tache appartient à plusieurs Plannings (many-to-many)
    public function plannings()
    {
        return $this->belongsToMany(Planning::class, 'planning_taches');
    }
}
