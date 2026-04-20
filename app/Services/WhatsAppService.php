<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $instance;
    private string $token;
    private string $group;

    public function __construct()
    {
        $this->instance = env('ULTRAMSG_INSTANCE_ID');
        $this->token    = env('ULTRAMSG_TOKEN');
        $this->group    = env('WHATSAPP_GROUP_ID');
    }

    public function sendToGroup(string $message): void
    {
        try {
            Http::post("https://api.ultramsg.com/{$this->instance}/messages/chat", [
                'token' => $this->token,
                'to'    => $this->group,
                'body'  => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp error: ' . $e->getMessage());
        }
    }

    public function sendPlanningCreated(string $nom, string $date, string $shift, bool $over44): void
    {
        $this->sendToGroup(
            "📅 Planning jadid wajed!\n" .
            "👤 Employe: {$nom}\n" .
            "📆 Date: {$date}\n" .
            "⏰ Shift: {$shift}\n" .
            ($over44 ? "⚠️ Attention: dépasse 44h cette semaine!\n" : "") .
            "Dkhol tchouf planning dyalek."
        );
    }

    public function sendPlanningUpdated(string $nom, string $date, string $shift, bool $over44): void
    {
        $this->sendToGroup(
            "✏️ Planning tbddel!\n" .
            "👤 Employe: {$nom}\n" .
            "📆 Date: {$date}\n" .
            "⏰ Shift: {$shift}\n" .
            ($over44 ? "⚠️ Attention: dépasse 44h cette semaine!\n" : "") .
            "Dkhol tchouf planning dyalek."
        );
    }
}