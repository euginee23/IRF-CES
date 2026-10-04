<?php

/*
|--------------------------------------------------------------------------
| Shop details for printed paperwork
|--------------------------------------------------------------------------
|
| What the transaction slip prints above and below the repair: who the
| shop is, how to reach it, and the claim terms the customer agrees to by
| leaving the device. Blank values are simply left off the slip.
|
*/

return [
    'name' => env('SHOP_NAME', env('APP_NAME', 'IRF-CES')),
    'address' => env('SHOP_ADDRESS'),
    'phone' => env('SHOP_PHONE'),

    'slip_terms' => [
        'Present this slip when claiming your device.',
        'Devices unclaimed 30 days after completion are not the shop\'s responsibility.',
        'The shop is not liable for data loss. Please back up your data.',
        'Warranty covers replaced parts only and is void if the seal is broken.',
    ],
];
