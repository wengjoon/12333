<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Site Settings
    |--------------------------------------------------------------------------
    |
    | General settings for your movie site
    |
    */

    // Control whether to show the "Watch" buttons
    'show_watch_button' => true,
    
    // Other site settings can go here
    'show_trailer_modal' => true,
    'enable_social_sharing' => true,
    
    // Default "Watch" button text
    'watch_button_text' => 'Watch Now',
    
    // Offer popup settings
    'show_offer_popup' => true,
    'offer_title' => 'Security Warning!',
    'offer_text' => 'Your connection is not secure. Streaming content without protection puts your data at risk. Our premium VPN service offers complete anonymity and security for just $2/month, ensuring your streaming activities remain private.',
    'offer_button_text' => 'Protect My Privacy Now',
    'offer_skip_text' => 'Skip (Available in %s seconds)',
    'offer_url' => '',
    'offer_skip_timeout' => 10, // Seconds before skip button becomes active
];