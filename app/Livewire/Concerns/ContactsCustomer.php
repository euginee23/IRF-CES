<?php

namespace App\Livewire\Concerns;

use App\Contracts\Contactable;
use App\Models\CustomerMessage;
use App\Services\Messaging\CustomerMessenger;
use App\Services\Messaging\MessageTemplates;
use App\Services\Sms\IprogSmsSender;
use App\Services\Sms\NetworkDetector;
use App\Services\Sms\PhoneNumber;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsSender;
use Throwable;

/**
 * The "Contact Customer" composer, shared by the screens that need it.
 *
 * This is a trait rather than a nested Livewire component because it always
 * opens from inside an existing modal, and a child component there would hold
 * its own copy of the record and drift out of step with the parent's.
 *
 * A host supplies contactRecord() and includes the composer partial.
 *
 * @mixin \Livewire\Component
 *
 * @see \App\Services\Messaging\CustomerMessenger
 */
trait ContactsCustomer
{
    public bool $showContactModal = false;

    public string $contactChannel = CustomerMessage::CHANNEL_EMAIL;

    public string $contactTemplate = '';

    public string $contactSubject = '';

    public string $contactBody = '';

    /** Network the recipient's number sits on, e.g. "Globe/TM". Null when not looked up. */
    public ?string $contactNetwork = null;

    /** True when the provider cannot deliver to that network on this account. */
    public bool $contactNetworkUnreachable = false;

    /** The record being messaged about, or null when nothing is selected. */
    abstract protected function contactRecord(): ?Contactable;

    public function openContactModal(): void
    {
        $record = $this->contactRecord();

        if (! $record) {
            return;
        }

        // Default to whichever channel this customer can actually be reached
        // on. Quote requests always have an email but often no phone, so email
        // leads; a record with only a phone opens on SMS instead.
        $this->contactChannel = $this->contactEmailAvailable()
            ? CustomerMessage::CHANNEL_EMAIL
            : CustomerMessage::CHANNEL_SMS;

        $this->contactTemplate = '';
        $this->contactSubject = '';
        $this->contactBody = '';
        $this->resetErrorBag(['contactBody', 'contactSubject']);

        $this->detectContactNetwork();

        $this->showContactModal = true;
    }

    public function closeContactModal(): void
    {
        $this->showContactModal = false;
        $this->contactTemplate = '';
        $this->contactSubject = '';
        $this->contactBody = '';
        $this->resetErrorBag(['contactBody', 'contactSubject']);
    }

    /** Refill the box when a preset is chosen. */
    public function updatedContactTemplate(): void
    {
        $this->applyContactTemplate();
    }

    /**
     * Refill when the channel changes, so switching to SMS swaps the long
     * email wording for the terse version instead of leaving prose that would
     * cost four credits.
     */
    public function updatedContactChannel(): void
    {
        $this->applyContactTemplate();
        $this->detectContactNetwork();
    }

    /**
     * Ask the provider which network the number is on.
     *
     * Done here rather than in the view so it runs when the recipient or
     * channel changes, not on every keystroke in the message box.
     */
    private function detectContactNetwork(): void
    {
        $this->contactNetwork = null;
        $this->contactNetworkUnreachable = false;

        $phone = $this->contactPhonePreview();

        if ($phone === null) {
            return;
        }

        $detector = $this->contactNetworkDetector();

        // Nothing to warn about on the log driver beyond "nothing is being
        // delivered", which the composer says separately.
        if (! $detector) {
            return;
        }

        $detected = $detector->detectNetwork($phone);

        // A prefix IPROG's table does not list still sends fine — 0952 is a
        // Globe number that comes back as "Unknown Network" and delivers. So
        // an unrecognised prefix is treated as ordinary rather than flagged;
        // the only thing worth warning about is a network that genuinely
        // cannot be reached.
        if ($detected === null || $detected['network'] === IprogSmsSender::NETWORK_UNKNOWN) {
            return;
        }

        $this->contactNetwork = $detected['network'];
        $this->contactNetworkUnreachable = $detected['is_smart_tnt']
            && $this->smartTrafficGoesToIprog()
            && ! config('sms.drivers.iprogsms.sender_name_approved', false);
    }

    /**
     * Whoever can say what network a number is on.
     *
     * Under routing the sender is the router, which delegates detection, so
     * the question goes to the configured detector rather than to the sender.
     */
    private function contactNetworkDetector(): ?NetworkDetector
    {
        $driver = config('sms.default') === 'routing'
            ? (string) config('sms.drivers.routing.detector', 'iprogsms')
            : null;

        if ($driver !== null) {
            return app(SmsManager::class)->detector($driver);
        }

        $sender = app(SmsSender::class);

        return $sender instanceof NetworkDetector ? $sender : null;
    }

    /**
     * Whether a Smart, TNT or Sun message would still be handed to IPROG.
     *
     * Routing exists so those numbers can go to a provider that carries them;
     * once they do, the "cannot be reached" banner is simply wrong and would
     * stop staff sending a message that works.
     */
    private function smartTrafficGoesToIprog(): bool
    {
        if (config('sms.default') !== 'routing') {
            return config('sms.default') === 'iprogsms';
        }

        $route = config('sms.drivers.routing.map.'.IprogSmsSender::NETWORK_SMART)
            ?: config('sms.drivers.routing.fallback');

        return $route === 'iprogsms';
    }

    private function applyContactTemplate(): void
    {
        $record = $this->contactRecord();

        if (! $record || $this->contactTemplate === '' || ! MessageTemplates::exists($this->contactTemplate)) {
            return;
        }

        $rendered = MessageTemplates::render($this->contactTemplate, $this->contactChannel, $record);

        $this->contactBody = $rendered['body'];

        if ($this->contactChannel === CustomerMessage::CHANNEL_EMAIL) {
            $this->contactSubject = $rendered['subject'];
        }
    }

    public function sendCustomerMessage(): void
    {
        $record = $this->contactRecord();

        if (! $record) {
            $this->dispatch('error', message: 'No customer is selected.');

            return;
        }

        $rules = ['contactBody' => 'required|string|min:2'];

        if ($this->contactChannel === CustomerMessage::CHANNEL_EMAIL) {
            $rules['contactSubject'] = 'required|string|max:255';
        }

        $this->validate($rules, [
            'contactBody.required' => 'Please write a message.',
            'contactSubject.required' => 'Please give the email a subject.',
        ]);

        try {
            app(CustomerMessenger::class)->send(
                record: $record,
                channel: $this->contactChannel,
                body: $this->contactBody,
                subject: $this->contactSubject,
                template: $this->contactTemplate ?: null,
            );
        } catch (Throwable $e) {
            // Nothing is recorded on failure, so the history stays an accurate
            // account of what the customer was actually told.
            $this->dispatch('error', message: 'Could not send: '.$e->getMessage());

            return;
        }

        $sentTo = $this->contactChannel === CustomerMessage::CHANNEL_SMS
            ? $this->contactPhonePreview()
            : $record->contactEmail();

        $this->closeContactModal();

        if (method_exists($this, 'refreshContactRecord')) {
            $this->refreshContactRecord();
        }

        $this->dispatch('success', message: strtoupper($this->contactChannel).' sent to '.$sentTo);
    }

    // -- View helpers ------------------------------------------------------

    /** Public read access for the composer partial, which cannot see protected methods. */
    public function contactRecordForView(): ?Contactable
    {
        return $this->contactRecord();
    }

    /** @return array<string, string> */
    public function contactTemplateOptions(): array
    {
        return MessageTemplates::options();
    }

    public function contactEmailAvailable(): bool
    {
        $email = $this->contactRecord()?->contactEmail();

        return $email !== null && trim($email) !== '';
    }

    public function contactSmsAvailable(): bool
    {
        return $this->contactPhonePreview() !== null;
    }

    /** The number as it would be sent, or null when there is none to send to. */
    public function contactPhonePreview(): ?string
    {
        $phone = $this->contactRecord()?->contactPhone();

        if ($phone === null || trim($phone) === '') {
            return null;
        }

        return PhoneNumber::toE164($phone, (string) config('sms.country_code', '63'));
    }

    /** Characters per GSM-7 segment; past this an SMS is billed twice. */
    public function contactSegmentLength(): int
    {
        return 160;
    }

    /**
     * Characters the provider adds to the body before sending.
     *
     * IPROG prepends the account's sender name and a space, and those count
     * towards the segment. Ignoring them would show "150 / 160, one credit"
     * for a message that actually costs two.
     */
    public function contactSmsOverhead(): int
    {
        if (! $this->iprogCouldSend()) {
            return 0;
        }

        $senderName = trim((string) config('sms.drivers.iprogsms.sender_name', ''));

        return $senderName === '' ? 0 : mb_strlen($senderName) + 1;
    }

    /**
     * Whether this message could be handed to IPROG.
     *
     * Under routing IPROG still carries Globe, TM and DITO, so its prepended
     * sender name still eats into the segment. Counting the overhead whenever
     * IPROG is on any route errs towards over-stating the cost, which is the
     * safe direction: the opposite promises 160 characters for a message that
     * is billed twice.
     */
    private function iprogCouldSend(): bool
    {
        $default = config('sms.default');

        if ($default === 'iprogsms') {
            return true;
        }

        if ($default !== 'routing') {
            return false;
        }

        $routes = array_values((array) config('sms.drivers.routing.map', []));
        $routes[] = config('sms.drivers.routing.fallback');

        return in_array('iprogsms', $routes, true);
    }
}
