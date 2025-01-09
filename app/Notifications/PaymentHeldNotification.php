<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentHeldNotification extends Notification
{
    use Queueable;

    protected $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // Add 'database' for storing in the DB
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Payment Held in Escrow')
            ->line("A payment of {$this->client->amount} USD has been held in escrow.")
            ->line('You will receive this payment once the client approves the completed work.')
            ->action('View Details', url('/your-worker-dashboard-url'))
            ->line('Thank you for using our platform!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Payment of {$this->client->amount} USD is held in escrow.",
            'client_id' => $this->client->id,
        ];
    }
}
