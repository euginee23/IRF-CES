<?php

namespace App\Jobs;

use App\Contracts\Contactable;
use App\Models\CustomerMessage;
use App\Services\Messaging\CustomerMessenger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Sends an automatic message to a customer, off the request.
 *
 * Only for messages the system sends on its own — the intake tracking text,
 * parts arriving, a payment receipt. The staff composer stays synchronous on
 * purpose: it reports a failure in the modal and records nothing, and that
 * honesty is worth the wait. Nobody is watching these.
 *
 * Provider calls take up to 15 seconds and run inside the Livewire request,
 * so an intake form that texts the customer would otherwise freeze for that
 * long while the counter has someone waiting.
 *
 * Requires a queue worker: `php artisan queue:work`. Without one these sit
 * in the jobs table unsent.
 */
class SendCustomerMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Two attempts, not more: a send costs credits and carries no
     * idempotency key, so a message the provider accepted just before
     * timing out would be delivered twice on every retry.
     */
    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(
        private readonly Contactable $record,
        private readonly string $channel,
        private readonly string $body,
        private readonly string $subject = '',
        private readonly ?string $template = null,
    ) {}

    /** @return array<int, object> */
    public function middleware(): array
    {
        // One message at a time per record, so a status change and a payment
        // landing together cannot interleave into a confusing pair of texts.
        return [new WithoutOverlapping($this->lockKey())];
    }

    public function handle(CustomerMessenger $messenger): void
    {
        $messenger->send(
            record: $this->record,
            channel: $this->channel,
            body: $this->body,
            subject: $this->subject,
            template: $this->template,
        );
    }

    private function lockKey(): string
    {
        $model = $this->record;

        return 'customer-message:'.$model::class.':'.($model->getKey() ?? 'new');
    }

    /**
     * Convenience for the common case: text the customer if we can, email
     * them if we cannot.
     */
    public static function bestChannelFor(Contactable $record): ?string
    {
        if ($record->contactPhone()) {
            return CustomerMessage::CHANNEL_SMS;
        }

        return $record->contactEmail() ? CustomerMessage::CHANNEL_EMAIL : null;
    }
}
