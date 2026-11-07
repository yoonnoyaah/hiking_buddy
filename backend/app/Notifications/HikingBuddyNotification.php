<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HikingBuddyNotification extends Notification
{
    use Queueable;

    public function __construct(public array $data)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'category' => $this->data['category'],
            'title' => $this->data['title'],
            'message' => $this->data['message'],
            'url' => $this->data['url'] ?? null,
            'actor_id' => $this->data['actor_id'] ?? null,
            'actor_name' => $this->data['actor_name'] ?? null,
            'related_id' => $this->data['related_id'] ?? null,
            'related_type' => $this->data['related_type'] ?? null,
        ];
    }
}
