<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    |
    | Presets offered in the "Contact Customer" composer. Staff pick one, it
    | fills the box, and they can edit it before sending — so these are
    | starting points, not fixed wording.
    |
    | SMS and email bodies are written separately on purpose: an SMS costs a
    | credit per 160 characters, so it stays terse and leans on the portal
    | link, while the email can spell things out.
    |
    | Placeholders are substituted from the record being messaged about:
    |
    |   :name        Customer's name
    |   :device      e.g. "Samsung Galaxy A52"
    |   :price       Quoted price, formatted, or "to be confirmed"
    |   :portal_url  Tokenised link to view and approve
    |   :shop        Application name
    |
    | An unknown placeholder is left as-is rather than blanked, so a typo shows
    | up in the preview instead of silently sending an empty gap.
    |
    */

    'templates' => [

        'quote_ready' => [
            'label' => 'Quote is ready',
            'subject' => 'Your repair quote for :device',
            'sms' => 'Hi :name, your :device repair quote is ready: :price. View and approve it here: :portal_url',
            'email' => <<<'TEXT'
            Hi :name,

            Your repair quote for the :device is ready. The estimated total is :price.

            You can review the full breakdown and approve it here:
            :portal_url

            No work begins until you approve, so take your time. Reply to this email if anything looks off.

            Thank you,
            :shop
            TEXT,
        ],

        'need_more_info' => [
            'label' => 'Need more information',
            'subject' => 'A few more details about your :device',
            // No ":shop" sign-off on the SMS bodies: IPROG already prepends the
            // account's sender name, so signing off repeats it to the customer
            // and costs characters that push the message into a second credit.
            'sms' => 'Hi :name, we need a little more detail about your :device before we can quote it. Please reply or call us.',
            'email' => <<<'TEXT'
            Hi :name,

            Thanks for sending through your repair request for the :device.

            Before we can put an accurate quote together, we need a few more details from you. Could you reply to this email with anything else you have noticed, and a photo of the problem if you can take one?

            Thank you,
            :shop
            TEXT,
        ],

        'awaiting_approval' => [
            'label' => 'Reminder: awaiting your approval',
            'subject' => 'Reminder: your :device quote is waiting for approval',
            'sms' => 'Hi :name, a reminder that your :device quote (:price) is waiting for your approval: :portal_url',
            'email' => <<<'TEXT'
            Hi :name,

            Just a friendly reminder that your quote for the :device is still waiting for your approval. The estimated total is :price.

            Approve it here whenever you are ready:
            :portal_url

            We have not started any work yet, and we will hold your slot for now.

            Thank you,
            :shop
            TEXT,
        ],

        'ready_for_pickup' => [
            'label' => 'Ready for pickup',
            'subject' => 'Your :device is ready for pickup',
            'sms' => 'Hi :name, good news - your :device is repaired and ready for pickup at :shop.',
            'email' => <<<'TEXT'
            Hi :name,

            Good news: your :device is repaired and ready for pickup.

            Please bring your reference with you when you collect it.

            Thank you for choosing us,
            :shop
            TEXT,
        ],

        'parts_delayed' => [
            'label' => 'Parts delayed',
            'subject' => 'A short delay on your :device repair',
            'sms' => 'Hi :name, the parts for your :device are delayed. We will update you as soon as they arrive. Sorry for the wait.',
            'email' => <<<'TEXT'
            Hi :name,

            We wanted to let you know that the parts needed for your :device have been delayed by our supplier.

            Your repair is still booked in, and we will contact you as soon as the parts arrive. We are sorry for the wait.

            Thank you for your patience,
            :shop
            TEXT,
        ],

    ],

];
