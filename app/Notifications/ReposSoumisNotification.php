<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Models\Repo;

class ReposSoumisNotification extends Notification
{
    public function __construct(public Repo $repo) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message'  => "Demande de repos soumise par {$this->repo->user->nom}",
            'repo_id'  => $this->repo->id,
            'user_nom' => $this->repo->user->nom,
            'motif'    => $this->repo->motif,
            'duree'    => $this->repo->duree,
        ];
    }
}
