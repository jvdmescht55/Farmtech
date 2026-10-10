<?php

/*
 * Business details shown on every legal page (ECTA s43 requires most of
 * these on a site that sells online). Set them in .env — anything left
 * empty shows up highlighted as "to be completed".
 */
return [
    'trading_name' => env('LEGAL_TRADING_NAME', 'Farmtech'),
    'legal_name' => env('LEGAL_ENTITY_NAME'),            // e.g. "Farmtech (Pty) Ltd" or the owner's name if a sole proprietor
    'legal_status' => env('LEGAL_STATUS'),              // e.g. "Private company" / "Sole proprietor"
    'registration_number' => env('LEGAL_REG_NUMBER'),   // CIPC number (if registered)
    'vat_number' => env('LEGAL_VAT_NUMBER'),            // only if VAT-registered
    'address' => env('LEGAL_ADDRESS'),                  // physical address for legal notices
    'email' => env('LEGAL_EMAIL', 'hello@farmtech.site'),
    'phone' => env('LEGAL_PHONE'),
    'information_officer' => env('LEGAL_INFO_OFFICER'), // POPIA Information Officer (usually the owner/director)
    'icasa_approval' => env('LEGAL_ICASA_APPROVAL'),    // ICASA type-approval number(s) for the devices
    'hosting' => 'DigitalOcean (Frankfurt, Germany) with Cloudflare in front',
    'updated' => '10 October 2026',
    // Bump when the terms change materially: signed-in users are asked to accept again.
    'terms_version' => '2026-10-10',
];
