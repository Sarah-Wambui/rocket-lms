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

    /*
    | The design used on the old learn.cvh.africa site ("COURSE COMPLETION _HD4A"): the text below is drawn on the
    | background image exactly as the old site did (position/size in pixels of that image, Montserrat font).
    | Used whenever the background file exists (it is copied by database/cvh/14_import_old_site.php --store=...);
    | otherwise the built-in design above is used. Set 'background' => null to always use the built-in design.
    | Placeholders: [student] [course] [date] [certificate_id] [platform_name] [instructor_name] [duration]
    */
    'template' => [
        'background' => '/store/oldcvh/1/Certificates Templates/CVH Template Certificate_compressed_page-0001.jpg',
        'position_x' => 150,
        'position_y' => 700,
        'font_size' => 40,
        'text_color' => '#000000',
        'body' => "This certificate is awarded to [student] in recognition of their dedication \n\nand successful completion of the course\n\n[course]",
        // QR code + certificate ID, so anyone can verify it (position as a percentage of the page)
        'verify_box' => ['right' => 4, 'bottom' => 4, 'size_mm' => 22],
    ],
];
