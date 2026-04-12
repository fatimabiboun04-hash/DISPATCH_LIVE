<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Repo;

class ReposReponseNotification extends Notification
{
    public function __construct(
        public Repo   $repo,
        public string $statut
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statut = $this->statut === 'accepte' ? 'acceptée' : 'refusée';

        return (new MailMessage)
            ->subject("Dispatch Live — Demande de repos {$statut}")
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("Votre demande de repos a été **{$statut}**.")
            ->line("Motif: {$this->repo->motif}")
            ->line("Durée: {$this->repo->duree} jours")
            ->line("Merci de consulter votre planning.");
    }
}
