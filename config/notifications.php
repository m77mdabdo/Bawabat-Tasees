<?php

/*
 * Who hears about new website enquiries, and how.
 * The recipient list itself is managed in the dashboard (Notification recipients).
 */
return [
    // Language of the team's alert emails (the client always gets their own language).
    'staff_locale' => env('NOTIFICATIONS_STAFF_LOCALE', 'ar'),

    // Used only while no recipient is configured in the dashboard, so a fresh install
    // still tells someone. Empty means: fall back to the site's contact email setting.
    'fallback_address' => env('NOTIFICATIONS_FALLBACK_ADDRESS'),

    // Send the client an automatic "we received your request" email.
    'confirm_to_client' => (bool) env('NOTIFICATIONS_CONFIRM_TO_CLIENT', true),
];
