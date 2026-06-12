<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seller / Business Details
    |--------------------------------------------------------------------------
    | These values appear on every GST tax invoice. Update via .env.
    */
    'seller_name'    => env('SELLER_NAME', 'Vriddhi'),
    'seller_gstin'   => env('SELLER_GSTIN', '07AABCV1234D1Z5'),
    'seller_state'   => env('SELLER_STATE', 'Delhi'),
    'seller_address' => env('SELLER_ADDRESS', '42, Craftsmen Lane, Paharganj, New Delhi - 110055'),

    /*
    |--------------------------------------------------------------------------
    | Shipping GST
    |--------------------------------------------------------------------------
    | SAC code 996812 covers courier/express delivery services.
    | Typically taxed at 18% GST.
    */
    'shipping_sac'  => '996812',
    'shipping_gst'  => 18.00,

    /*
    |--------------------------------------------------------------------------
    | PDF Storage
    |--------------------------------------------------------------------------
    | PDFs are stored on Cloudflare R2 (same disk as product images).
    | Signed URLs expire after this many minutes.
    */
    'pdf_disk'            => env('INVOICE_PDF_DISK', 'r2'),
    'pdf_directory'       => 'invoices',
    'signed_url_minutes'  => 15,
];
