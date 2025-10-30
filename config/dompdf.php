<?php

return [

    'show_warnings' => false,

    'public_path' => null,

    'convert_entities' => true,

    'options' => [
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true, // 👈 Needed for loading storage images
        'isPhpEnabled' => true,
        'defaultFont' => 'DejaVu Sans',
        'isFontSubsettingEnabled' => true,
        'debugPng' => false,
        'debugKeepTemp' => false,
        'chroot' => base_path(),
        'dpi' => 96,
    ],

];
