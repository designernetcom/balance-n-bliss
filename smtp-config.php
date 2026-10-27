<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hospital recipient
    |--------------------------------------------------------------------------
    */

    'recipient' => 'designernetcom@gmail.com',

    'recipient_name' =>
        'Aai Women’s and Children’s Hospital',

    /*
    |--------------------------------------------------------------------------
    | Sender name
    |--------------------------------------------------------------------------
    */

    'from_name' =>
        'Aai Women’s and Children’s Hospital',

    /*
    |--------------------------------------------------------------------------
    | Enquiry log
    |--------------------------------------------------------------------------
    */

    'log_dir' => '',

    /*
    |--------------------------------------------------------------------------
    | SMTP hostname / Message-ID hostname
    |--------------------------------------------------------------------------
    */

    'hostname' => 'netcomindia.live',

    /*
    |--------------------------------------------------------------------------
    | SMTP timeout
    |--------------------------------------------------------------------------
    */

    'timeout' => 20,

    /*
    |--------------------------------------------------------------------------
    | SMTP TRANSPORT
    |--------------------------------------------------------------------------
    |
    | Zoho SMTP only.
    |
    */

    'transports' => [

        [
            'label' => 'Zoho SMTP',

            /*
            | Zoho India SMTP
            */
            'host' => 'smtp.zoho.in',

            /*
            | STARTTLS
            */
            'port' => 587,

            'encryption' => 'tls',

            /*
            | SMTP authentication
            */
            'auth' => true,

            /*
            | Zoho mailbox
            |
            | Make sure this mailbox actually exists
            | in your Zoho account.
            */
            'username' => 'noreply@aaihospitals.com',

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            |
            | Put your Zoho APP PASSWORD here.
            |
            | DO NOT put your normal Zoho password here
            | if Zoho MFA/2FA is enabled.
            |
            */
            'password' => 'FpzVdrs6ziZM',

            /*
            | The From address MUST match the authenticated
            | Zoho/domain mailbox.
            */
            'from' => 'noreply@aaihospitals.com',
        ],

        [
            'label' => 'Zoho SMTP (implicit TLS)',

            /*
            | Fallback for hosts that transparently redirect outbound port
            | 587 to their own local mail server. Port 465 often escapes
            | that redirect.
            */
            'host' => 'smtp.zoho.in',

            'port' => 465,

            'encryption' => 'ssl',

            'auth' => true,

            'username' => 'noreply@aaihospitals.com',

            'password' => 'FpzVdrs6ziZM',

            'from' => 'noreply@aaihospitals.com',
        ],

    ],

];
