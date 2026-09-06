<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Notifications\Messages\MailMessage;

class TuitionDueSoonNotification extends EventNotification
{
    public function __construct(public Invoice $invoice) {}

    public function eventKey(): string
    {
        return 'tuition.due_soon';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = $this->params();

        return (new MailMessage)
            ->subject(__('notifications.tuitionDueSoon.subject', $params))
            ->line(__('notifications.tuitionDueSoon.line', $params))
            ->action(__('notifications.tuitionDueSoon.action'), route('member.my-membership'));
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload('notifications.tuitionDueSoon.bell', $this->params(), route('member.my-membership'));
    }

    private function params(): array
    {
        return [
            'invoice' => $this->invoice->invoice_number,
            'amount' => number_format($this->invoice->total_amount),
            'date' => $this->invoice->due_date->format('d/m/Y'),
        ];
    }
}
