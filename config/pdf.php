<?php
return [
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font_size' => '11',
    'default_font' => 'iransans',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
    'margin_header' => 0,
    'margin_footer' => 0,
    'orientation' => 'P',
    'title' => 'Laravel mPDF',
    'author' => 'bagheraloloom.com',
    'watermark' => env('SITE_TITLE'),
    'show_watermark' => true,
    'watermark_alpha' => 1,
    'watermark_font' => 'iransans',
    'display_mode' => 'fullpage',
    'watermark_text_alpha' => 0.1,
    'directionality' => 'rtl',
    'custom_font_dir' => public_path('fonts/iran-sans/ttf'),
    'custom_font_data' => [
        'iransans' => [
            'R' => 'IRANSansWeb(FaNum).ttf',    // regular font
            'B' => 'IRANSansWeb(FaNum)_Bold.ttf',       // optional: bold font
            'useOTL' => 0xFF,    // required for complicated langs like Persian, Arabic and Chinese
            'useKashida' => 75,  // required for complicated langs like Persian, Arabic and Chinese
        ],
    ],
    'auto_language_detection' => false,
    'temp_dir' => storage_path('mpdf-config')
];
