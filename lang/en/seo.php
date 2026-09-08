<?php

/*
 * Meta description per pagina, indicizzate sul NOME DELLA ROTTA (che è unico
 * per entrambe le lingue: cambia lo slug, non il nome). Le chiavi devono
 * combaciare una a una con lang/it/seo.php — lo verifica MetaTagsTest, perché
 * LangParityTest ha l'elenco dei file cablato e non conosce questo file.
 *
 * Non è una traduzione letterale dell'italiano: la description è copy che
 * finisce nello snippet di Google, quindi conta che suoni bene in inglese e
 * che stia nei 150-160 caratteri, non che ricalchi la frase italiana.
 */

return [
    'default' => 'AnimalAmo is the pet friendly travel portal: places that welcome dogs and cats, events, activities and gift boxes to enjoy side by side all across Italy.',

    'home' => 'Search and book pet friendly holidays in Italy: hotels and holiday homes that welcome dogs and cats, plus events, activities and gift boxes for you both.',

    'holiday' => 'Animal Holiday: pet friendly hotels, B&Bs, holiday homes and farm stays region by region, with the services your animal needs and instant online booking.',
    'holiday.region' => 'Every pet friendly place in the region: hotels, holiday homes and farm stays that welcome dogs and cats, with dedicated services and instant online booking.',

    'eventi' => 'Pet friendly events and activities in Italy: hikes, puppy yoga, meet-ups and days out in the open air to enjoy with your animal. Book your place online today.',

    'smartbox' => 'AnimalAmo Smartbox: gift boxes for pet friendly weekends and short stays, with experiences and services made for animals. The present for anyone who travels.',

    'news' => 'Animal Times: guides, tips and news about travelling with dogs and cats, from transport rules to your animal\'s wellbeing before, during and after the holiday.',
    'community' => 'Animal Network: the AnimalAmo community where you share photos, stories and travel tips with other owners of dogs, cats and every other kind of animal.',

    'about' => 'About us: AnimalAmo was born to make travelling with animals simple, connecting pet families with hand-picked places to stay, events and dedicated services.',
    'contact' => 'Write to AnimalAmo for help with bookings, gift boxes and events, or to list your own pet friendly place. We usually answer you within a few working hours.',
    'work-with-us' => 'Work with us: bring your accommodation, your events or your pet friendly services to AnimalAmo and reach the people travelling with dogs and cats in Italy.',

    'terms.customers' => 'Terms and conditions of the AnimalAmo service for customers: bookings, payments, cancellations, refunds and the rights of whoever buys stays and experiences.',
    'terms.suppliers' => 'General supplier terms for AnimalAmo partners: how to publish accommodation, events and gift boxes, commission, payouts and the duties of every partner.',
    'privacy' => 'AnimalAmo privacy policy: which data we collect, how we process it, how long we keep it and which rights you have over your own data while you use the site.',
];
