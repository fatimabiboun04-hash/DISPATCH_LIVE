<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanningResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'date'           => $this->date,
            'shift'          => $this->shift,
            'heure_debut'    => $this->heure_debut,
            'heure_fin'      => $this->heure_fin,
            'pause_minutes'  => $this->pause_minutes,
            'heures_reelles' => $this->heures_reelles,
            'over_44h'       => $this->over_44h,
            'est_le_mien'    => $this->whenNotNull(
                optional($request->user())->id === $this->user_id ? true : null
            ),
            'user'           => [
                'id'    => $this->user->id,
                'nom'   => $this->user->nom,
                'email' => $this->user->email,
            ],
            'equipe'         => $this->user->equipe?->nom,
            'taches'         => $this->taches->map(fn($t) => [
                'id'    => $t->id,
                'titre' => $t->titre,
            ]),
        ];
    }
}
