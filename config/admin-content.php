<?php

/*
|--------------------------------------------------------------------------
| Testi delle pagine pubbliche modificabili dal pannello (opzione B)
|--------------------------------------------------------------------------
|
| Per ogni sezione del sito: rotta pubblica e chiavi dei file lingua che la
| cliente può riscrivere, con il loro tipo. Sono i testi descrittivi
| (titoli, sottotitoli, paragrafi introduttivi), non pulsanti, menu o
| etichette dei moduli: quelli restano nei file lingua.
|
| Tipi: `line` una riga, `text` un paragrafo, `paragraphs` una chiave che nel
| file lingua è un elenco di paragrafi (la cliente li separa con una riga
| vuota). Nei blade queste chiavi passano da cms() / cms_paragraphs().
|
| Le etichette del pannello stanno in lang/it/admin-content.php:
| `sections.<sezione>` e `site_blocks.<chiave>`.
|
*/

return [
    'sections' => [
        'home' => [
            'route' => 'home',
            'blocks' => [
                'home.hero_title' => 'line',
                'home.hero_text' => 'text',
                'home.holiday_title' => 'line',
                'home.holiday_subtitle' => 'text',
                'home.events_title' => 'line',
                'home.events_subtitle' => 'text',
                'home.events_empty' => 'text',
                'home.smartbox_title' => 'line',
                'home.smartbox_subtitle' => 'text',
                'home.news_title' => 'line',
                'home.news_subtitle' => 'text',
                'home.community_title' => 'line',
                'home.community_subtitle' => 'text',
            ],
        ],

        'about' => [
            'route' => 'about',
            'blocks' => [
                'about.heading' => 'line',
                'about.body' => 'paragraphs',
                'about.block1_heading' => 'line',
                'about.block1_body' => 'paragraphs',
                'about.block2_heading' => 'line',
                'about.block2_body' => 'paragraphs',
            ],
        ],

        'holiday' => [
            'route' => 'holiday',
            'blocks' => [
                'holiday.empty_catalogue_title' => 'line',
                'holiday.empty_catalogue_body' => 'text',
                'holiday.empty_catalogue_region_title' => 'line',
                'holiday.empty_catalogue_region_body' => 'text',
            ],
        ],

        'smartbox' => [
            'route' => 'smartbox',
            'blocks' => [
                'smartbox.title' => 'line',
                'smartbox.subtitle' => 'text',
                'smartbox.empty_catalogue_title' => 'line',
                'smartbox.empty_catalogue_body' => 'text',
            ],
        ],

        'contact' => [
            'route' => 'contact',
            'blocks' => [
                'contact.heading' => 'line',
                'contact.intro' => 'text',
                'contact.info_heading' => 'line',
                'contact.thanks_heading' => 'line',
                'contact.thanks_line_1' => 'line',
                'contact.thanks_line_2' => 'line',
            ],
        ],
    ],
];
