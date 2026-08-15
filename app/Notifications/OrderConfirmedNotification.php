<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تایید سفارش '.$this->order->tracking_code)
            ->line('سفارش شما با موفقیت ثبت شد.')
            ->line('مبلغ: '.number_format($this->order->final_amount).' تومان')
            ->action('مشاهده سفارش', url('/account/orders/'.$this->order->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'tracking_code' => $this->order->tracking_code,
            'final_amount' => $this->order->final_amount,
        ];
    }
}
