<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FechoCaixaNotification extends Notification
{
    use Queueable;private $user;
    private $route;
    private $title;
    private $description;
    private $parameter;


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        //
        $this->user = $notification;
        $this->route = "notifications/index";
        $this->title = "Fecho do Caixa - " . $notification->operador->nome;
        $this->description = "O Operador(a) {$notification->operador->nome} fez o fecho de caixa com sucesso!";
        
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
            'user' => $this->user,
            'title' => $this->title,
            'description' => $this->description,
            'route' => $this->route,
        ];
    }
}
