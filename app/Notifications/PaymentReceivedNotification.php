<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentReceivedNotification extends Notification
{
    use Queueable;

    protected $worker;

    public function __construct($worker)
    {
        $this->worker = $worker;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Worker Received Payment')
            ->line("The worker {$this->worker->name} has successfully received your payment.")
            ->action('View Details', url('/your-client-dashboard-url'))
            ->line('Thank you for using our platform!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Worker {$this->worker->name} has received the payment.",
        ];
    }
}
