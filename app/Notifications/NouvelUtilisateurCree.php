<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NouvelUtilisateurCree extends Notification
{
    use Queueable;
    protected $utilisateur;

    public function __construct($utilisateur)
    {
        $this->utilisateur = $utilisateur;
    }

    public function via($notifiable)
    {
        return ['database']; // Pour stocker dans la base
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Nouveau compte créé',
            'message' => "Un nouvel utilisateur ({$this->utilisateur->username}) s’est inscrit.",
            'utilisateur_id' => $this->utilisateur->id,
        ];
    }
}