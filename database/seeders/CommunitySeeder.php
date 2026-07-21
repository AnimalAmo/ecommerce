<?php

namespace Database\Seeders;

use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostReply;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Community: i quattro post del mock XD più un riempimento generato.
 *
 * I post del mock restano testuali perché le schermate mobile sono misurate su
 * quei testi (altezze card, ritorni a capo); il resto è fake, così la lista, la
 * ricerca e i filtri hanno abbastanza materiale. Va dopo DemoUserSeeder: i post
 * "I miei post" appartengono a Giulia.
 */
class CommunitySeeder extends Seeder
{
    private const XD_POSTS = [
        [
            'title' => 'Consigli per una smartbox',
            'tag' => 'Avventura',
            'author_name' => 'Andrea',
            'body' => 'Buongiorno a tutti, vorrei sapere in base alle vostre esperienze, quali sono le smartbox più interessanti e più valide, vorrei regalarne una ad un’amica, ma non so scegliere, a lei piacciono molto le avventure e le piace passare tanto tempo immersa nella natura. Grazie per la risposta',
            'mine' => false,
            'replies' => [
                ['author_name' => 'Giulia', 'body' => 'Buongiorno Andrea, io ti consiglio tanto il “Weekend in montagna”, sono andata con mia sorella e devo dire che abbiamo apprezzato entrambe tutta l’organizzazione, poi credo che possa andare bene, visto che si passa molto tempo in mezzo ai boschi, quindi ti consiglio questa!'],
            ],
        ],
        [
            'title' => 'Consigli per una smartbox',
            'tag' => 'Domanda',
            'author_name' => 'Sofia',
            'body' => 'Ciaooo :) tra due settimane è il compleanno del mio ragazzo, vorrei fargli una sorpresa e regalargli una smartbox, qualcuno di voi l’ha già comprata in modalità “regalo”? Mi potete dire come funziona? Grazie mille a tutti per le risposteee',
            'mine' => false,
            'replies' => [
                ['author_name' => 'Matteo', 'body' => 'Ciao Sofia! Si io ne ho comprate due, intanto ottima idea regalo, in realtà è super semplice, ti basta aggiungerla al carrello con la modalità “regalo” e poi ti verranno chiesti alcuni dati easy del destinatario e se vuoi mandargliela per email dovrai inserire anche quella, spero di esserti stato utile!'],
            ],
        ],
        [
            'title' => 'Dubbi sugli eventi',
            'tag' => 'Benessere',
            'author_name' => 'Giulia',
            'body' => 'Buongiorno a tutti, vorrei sapere in base alle vostre esperienze, quali sono le smartbox più interessanti e più valide, vorrei regalarne una ad un’amica, ma non so scegliere, a lei piacciono molto le avventure e le piace passare tanto tempo immersa nella natura. Grazie per la risposta',
            'mine' => true,
            'replies' => [
                ['author_name' => 'Andrea', 'body' => 'Buongiorno Giulia, io ti consiglio tanto il “Weekend in montagna”, sono andato con mia sorella e devo dire che abbiamo apprezzato entrambi tutta l’organizzazione, poi credo che possa andare bene, visto che si passa molto tempo in mezzo ai boschi, quindi ti consiglio questa!'],
            ],
        ],
        [
            'title' => 'Consigli sulle attività',
            'tag' => 'Domanda',
            'author_name' => 'Giulia',
            'body' => 'Ciaooo :) tra due settimane è il compleanno del mio ragazzo, vorrei fargli una sorpresa e regalargli una smartbox, qualcuno di voi l’ha già comprata in modalità “regalo”? Mi potete dire come funziona? Grazie mille a tutti per le risposteee',
            'mine' => true,
            'replies' => [],
        ],
    ];

    public function run(): void
    {
        $giulia = User::where('email', 'giulia.rossi@gmail.com')->first();

        foreach (self::XD_POSTS as $row) {
            $post = CommunityPost::updateOrCreate(
                ['title' => $row['title'], 'author_name' => $row['author_name']],
                [
                    'user_id' => $row['mine'] ? $giulia?->id : null,
                    'tag' => $row['tag'],
                    'body' => $row['body'],
                ],
            );

            foreach ($row['replies'] as $reply) {
                $post->replies()->updateOrCreate(
                    ['author_name' => $reply['author_name']],
                    ['body' => $reply['body']],
                );
            }
        }

        // Riempimento fake solo al primo giro: ri-seedare non deve moltiplicare la lista.
        if (CommunityPost::count() > count(self::XD_POSTS)) {
            return;
        }

        CommunityPost::factory()->count(8)->create()->each(
            fn (CommunityPost $post) => CommunityPostReply::factory()
                ->count(fake()->numberBetween(0, 2))
                ->create(['community_post_id' => $post->id]),
        );
    }
}
