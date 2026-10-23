<?php

/*
| CVH Africa — in-house certificate of completion (see app/Http/Controllers/Web/CvhCertificatesController.php).
| Image paths are relative to /public. Leave signature/stamp empty until CVH provides them.
*/
return [
    'issuer' => 'Community Virtual Hub (CVH Africa)',
    'logo' => '/store/cvh/brand/cvh-logo.png',

    // Person who signs the certificates
    'signatory_name' => 'CVH Africa',
    'signatory_title' => 'Community Virtual Hub',
    'signature_image' => null,   // e.g. '/store/cvh/certificate/signature.png' (transparent PNG)
    'stamp_image' => null,       // e.g. '/store/cvh/certificate/stamp.png'

    // Optional small partner/funder logos along the bottom edge (e.g. EU emblem, HD4A, INNOECOFOOD)
    'footer_logos' => [
        '/store/cvh/files/HD4A logo.png',
        '/store/cvh/files/Innoecofood Logo Original.png',
    ],
];
