<?php

/*
|--------------------------------------------------------------------------
| Testi delle pagine pubbliche modificabili dal pannello (opzione B)
|--------------------------------------------------------------------------
|
| Per ogni sezione del sito: etichetta nel pannello, rotta pubblica e le
| chiavi dei file lingua che la cliente può riscrivere. Sono i testi
| descrittivi (titoli, sottotitoli, paragrafi introduttivi), non pulsanti,
| menu o etichette dei moduli: quelli restano nei file lingua.
|
| Tipi: `line` una riga, `text` un paragrafo, `paragraphs` una chiave che nel
| file lingua è un elenco di paragrafi (la cliente li separa con una riga
| vuota). Nei blade queste chiavi passano da cms() / cms_paragraphs().
|
*/

return [
    'sections' => [
        'home' => [
            'label' => 'Home',
            'route' => 'home',
            'blocks' => [
                'home.hero_title' => ['label' => 'Titolo in apertura', 'type' => 'line'],
                'home.hero_text' => ['label' => 'Testo in apertura', 'type' => 'text'],
                'home.holiday_title' => ['label' => 'Vacanze: titolo', 'type' => 'line'],
                'home.holiday_subtitle' => ['label' => 'Vacanze: descrizione', 'type' => 'text'],
                'home.events_title' => ['label' => 'Eventi: titolo', 'type' => 'line'],
                'home.events_subtitle' => ['label' => 'Eventi: descrizione', 'type' => 'text'],
                'home.events_empty' => ['label' => 'Eventi: descrizione quando non ce ne sono', 'type' => 'text'],
                'home.smartbox_title' => ['label' => 'Smartbox: titolo', 'type' => 'line'],
                'home.smartbox_subtitle' => ['label' => 'Smartbox: descrizione', 'type' => 'text'],
                'home.news_title' => ['label' => 'Animal Times: titolo', 'type' => 'line'],
                'home.news_subtitle' => ['label' => 'Animal Times: descrizione', 'type' => 'text'],
                'home.community_title' => ['label' => 'Community: titolo', 'type' => 'line'],
                'home.community_subtitle' => ['label' => 'Community: descrizione', 'type' => 'text'],
            ],
        ],

        'about' => [
            'label' => 'Chi siamo',
            'route' => 'about',
            'blocks' => [
                'about.heading' => ['label' => 'Titolo', 'type' => 'line'],
                'about.body' => ['label' => 'Testo di presentazione', 'type' => 'paragraphs'],
                'about.block1_heading' => ['label' => 'Primo blocco: titolo', 'type' => 'line'],
                'about.block1_body' => ['label' => 'Primo blocco: testo', 'type' => 'paragraphs'],
                'about.block2_heading' => ['label' => 'Secondo blocco: titolo', 'type' => 'line'],
                'about.block2_body' => ['label' => 'Secondo blocco: testo', 'type' => 'paragraphs'],
            ],
        ],

        'holiday' => [
            'label' => 'Vacanze con il tuo animale',
            'route' => 'holiday',
            'blocks' => [
                'holiday.empty_catalogue_title' => ['label' => 'Senza strutture: titolo', 'type' => 'line'],
                'holiday.empty_catalogue_body' => ['label' => 'Senza strutture: testo', 'type' => 'text'],
                'holiday.empty_catalogue_region_title' => ['label' => 'Regione senza strutture: titolo', 'type' => 'line'],
                'holiday.empty_catalogue_region_body' => ['label' => 'Regione senza strutture: testo', 'type' => 'text'],
            ],
        ],

        'smartbox' => [
            'label' => 'Smartbox',
            'route' => 'smartbox',
            'blocks' => [
                'smartbox.title' => ['label' => 'Titolo', 'type' => 'line'],
                'smartbox.subtitle' => ['label' => 'Sottotitolo', 'type' => 'text'],
                'smartbox.empty_catalogue_title' => ['label' => 'Senza cofanetti: titolo', 'type' => 'line'],
                'smartbox.empty_catalogue_body' => ['label' => 'Senza cofanetti: testo', 'type' => 'text'],
            ],
        ],

        'contact' => [
            'label' => 'Contatti',
            'route' => 'contact',
            'blocks' => [
                'contact.heading' => ['label' => 'Titolo', 'type' => 'line'],
                'contact.intro' => ['label' => 'Testo introduttivo', 'type' => 'text'],
                'contact.info_heading' => ['label' => 'Titolo del riquadro contatti', 'type' => 'line'],
                'contact.thanks_heading' => ['label' => 'Dopo l\'invio: titolo', 'type' => 'line'],
                'contact.thanks_line_1' => ['label' => 'Dopo l\'invio: prima riga', 'type' => 'line'],
                'contact.thanks_line_2' => ['label' => 'Dopo l\'invio: seconda riga', 'type' => 'line'],
            ],
        ],
    ],
];
