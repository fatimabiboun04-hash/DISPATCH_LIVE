<?php
// app/Models/Planning.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'shift',
        'heure_debut',
        'heure_fin',
        'pause_minutes',
         'heures_reelles', 
         'over_44h', 
    ];
    protected $casts = [
    'date'              =>   'date',
    'over_44h'          => 'boolean', 
    'heures_reelles'    => 'float',   
];

    // Planning appartient à un User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Planning a plusieurs Taches (many-to-many)
    public function taches()
    {
        return $this->belongsToMany(Tache::class, 'planning_taches');
    }
}
