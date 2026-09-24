<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Models\JobOrder;
use App\Models\JobOrderEvent;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Takes payments and keeps the record of them honest.
 *
 * @see \App\Models\Payment
 */
class PaymentService
{
    /** Receipt numbers, like job order numbers: OR-YYYYMMDD-0001. */
    private const RECEIPT_PREFIX = 'OR-';

    public function take(
        JobOrder $jobOrder,
        float $amount,
        PaymentMethod $method,
        ?string $referenceNo = null,
        ?string $note = null,
        ?User $receivedBy = null,
    ): Payment {
        if ($amount <= 0) {
            throw new RuntimeException('A payment must be for more than zero.');
        }

        return DB::transaction(function () use ($jobOrder, $amount, $method, $referenceNo, $note, $receivedBy) {
            $payment = $this->createWithReceiptNumber($jobOrder, [
                'amount' => $amount,
                'method' => $method,
                'reference_no' => $referenceNo,
                'paid_at' => now(),
                'received_by' => $receivedBy?->id ?? Auth::id(),
                'note' => $note,
            ]);

            $jobOrder->refresh();

            JobOrderEvent::create([
                'job_order_id' => $jobOrder->id,
                'type' => JobOrderEvent::TYPE_PAYMENT_RECEIVED,
                'description' => $this->describe($jobOrder, $amount),
                'meta' => [
                    'amount' => $amount,
                    'method' => $method->value,
                    'receipt_number' => $payment->receipt_number,
                    'balance' => $jobOrder->balance(),
                ],
                // The customer should be able to see what they have paid.
                'is_customer_visible' => true,
                'user_id' => Auth::id(),
            ]);

            return $payment;
        });
    }

    /**
     * Cancel a payment without erasing it.
     *
     * The customer already has the receipt, so the row stays and is marked
     * void; deleting it would leave a receipt number that refers to nothing.
     */
    public function void(Payment $payment, ?string $reason = null, ?User $by = null): Payment
    {
        if ($payment->isVoided()) {
            return $payment;
        }

        $payment->update([
            'voided_at' => now(),
            'voided_by' => $by?->id ?? Auth::id(),
            'note' => trim(($payment->note ? $payment->note.' ' : '').'Voided: '.($reason ?: 'no reason given')),
        ]);

        JobOrderEvent::create([
            'job_order_id' => $payment->job_order_id,
            'type' => JobOrderEvent::TYPE_PAYMENT_RECEIVED,
            'description' => "Payment {$payment->receipt_number} was voided.",
            'meta' => ['receipt_number' => $payment->receipt_number, 'reason' => $reason],
            'is_customer_visible' => false,
            'user_id' => Auth::id(),
        ]);

        return $payment;
    }

    /**
     * Insert the payment, letting the unique index settle the numbering.
     *
     * Two counter staff taking payment in the same moment will compute the
     * same next number; rather than a check-then-insert that quietly loses
     * one of them, the insert is attempted and retried on the collision. The
     * unique index is the arbiter, not the read.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createWithReceiptNumber(JobOrder $jobOrder, array $attributes): Payment
    {
        $prefix = self::RECEIPT_PREFIX.now()->format('Ymd').'-';

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $last = Payment::where('receipt_number', 'like', $prefix.'%')
                ->orderByDesc('receipt_number')
                ->value('receipt_number');

            $next = $last ? ((int) substr($last, -4)) + 1 + $attempt : 1 + $attempt;

            try {
                return $jobOrder->payments()->create($attributes + [
                    'receipt_number' => $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT),
                ]);
            } catch (QueryException $e) {
                if (! $this->isDuplicateReceipt($e)) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException('Could not allocate a receipt number. Try again.');
    }

    private function isDuplicateReceipt(QueryException $e): bool
    {
        // 23000/23505 are the SQL standard integrity-violation classes, which
        // is what both MySQL and SQLite report a unique collision as.
        return in_array($e->getCode(), ['23000', '23505'], true);
    }

    private function describe(JobOrder $jobOrder, float $amount): string
    {
        $paid = 'Payment of PHP '.number_format($amount, 2).' received.';
        $balance = $jobOrder->balance();

        if ($balance > 0) {
            return $paid.' Balance due: PHP '.number_format($balance, 2).'.';
        }

        return $paid.' Paid in full — thank you.';
    }
}
