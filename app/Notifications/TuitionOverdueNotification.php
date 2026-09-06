<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Notifications\Messages\MailMessage;

class TuitionOverdueNotification extends EventNotification
{
    public function __construct(public Invoice $invoice) {}

    public function eventKey(): string
    {
        return 'tuition.overdue';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = $this->params();

        return (new MailMessage)
            ->subject(__('notifications.tuitionOverdue.subject', $params))
            ->line(__('notifications.tuitionOverdue.line', $params))
            ->action(__('notifications.tuitionOverdue.action'), route('member.my-membership'));
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload('notifications.tuitionOverdue.bell', $this->params(), route('member.my-membership'));
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
