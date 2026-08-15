<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('ارسال سفارش '.$this->order->tracking_code)
            ->line('سفارش شما ارسال شد.');

        if ($this->order->shipping_tracking_code) {
            $mail->line('کد رهگیری پست: '.$this->order->shipping_tracking_code);
        }

        return $mail->action('پیگیری سفارش', url('/account/orders/'.$this->order->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'tracking_code' => $this->order->tracking_code,
            'type' => 'order_shipped',
        ];
    }
}
