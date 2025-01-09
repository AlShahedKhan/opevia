<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentReleasedNotification extends Notification
{
    use Queueable;

    protected $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Payment Released to You')
            ->line("The client has approved the work. A payment of {$this->client->amount} USD has been released to your account.")
            ->action('View Details', url('/your-worker-dashboard-url'))
            ->line('Thank you for using our platform!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Payment of {$this->client->amount} USD has been released to you.",
            'client_id' => $this->client->id,
        ];
    }
}
