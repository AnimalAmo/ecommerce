<?php

namespace App\Livewire\Content;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

class Community extends Component
{
    /** Tab attiva ("tutti" | "miei"); deep-linkabile via ?tab=miei (stesso pattern di EventDetail). */
    #[Url(except: 'tutti')]
    public string $tab = 'tutti';

    public const TABS = ['tutti', 'miei'];

    /** Ricerca live (toolbar): filtra i post per titolo/body. */
    public string $search = '';

    /** Testo del composer nella hero. */
    public string $composerBody = '';

    /** Tag selezionati nel composer (flux:checkbox.group variant pills, multi-selezione; default XD "Avventura"). */
    public array $composerTags = ['Avventura'];

    /**
     * Filtri tipologia attivi (chips sotto la toolbar, artboard "Community – filtro").
     * Su desktop ci arrivano da addFilter/removeFilter, su mobile dal wire:model del
     * pannello "Filtri community": va quindi ripulito anche in ingresso.
     */
    public array $activeFilters = [];

    /** Bozze di risposta per id post (input pill in fondo a ogni card). */
    public array $replyDrafts = [];

    /** Lista post in-memory (const + post pubblicati dal composer); niente DB. */
    public array $posts = [];

    /** Tipologie/tag disponibili (chips composer + menu "Filtra tipologia"). */
    public const TAGS = ['Avventura', 'Soggiorno', 'Benessere', 'Eventi', 'Attività', 'Strutture', 'Servizi'];

    /** Colore chip del tag sulla card (palette per tag da direttiva; "Domanda" grigio XD, grigio come fallback). */
    public const TAG_COLORS = [
        'Avventura' => '#3E72FF',
        'Soggiorno' => '#8DE0FF',
        'Benessere' => '#8DABFF',
        'Eventi' => '#C59FFD',
        'Attività' => '#8E53E6',
        'Strutture' => '#FF9F3E',
        'Servizi' => '#FFE13E',
        'Domanda' => '#555555',
    ];

    /**
     * Post campione come da XD (statici come nelle pagine sorelle;
     * struttura pronta per essere sostituita da un backend reale).
     */
    public const POSTS = [
        [
            'id' => 1,
            'title' => 'Consigli per una smartbox',
            'tag' => 'Avventura',
            'tagColor' => '#3E72FF',
            'author' => 'Andrea',
            'body' => 'Buongiorno a tutti, vorrei sapere in base alle vostre esperienze, quali sono le smartbox più interessanti e più valide, vorrei regalarne una ad un’amica, ma non so scegliere, a lei piacciono molto le avventure e le piace passare tanto tempo immersa nella natura. Grazie per la risposta',
            'replies' => [
                ['author' => 'Giulia', 'body' => 'Buongiorno Andrea, io ti consiglio tanto il “Weekend in montagna”, sono andata con mia sorella e devo dire che abbiamo apprezzato entrambe tutta l’organizzazione, poi credo che possa andare bene, visto che si passa molto tempo in mezzo ai boschi, quindi ti consiglio questa!'],
            ],
            'mine' => false,
        ],
        [
            'id' => 2,
            'title' => 'Consigli per una smartbox',
            'tag' => 'Domanda',
            'tagColor' => '#555555',
            'author' => 'Sofia',
            'body' => 'Ciaooo :) tra due settimane è il compleanno del mio ragazzo, vorrei fargli una sorpresa e regalargli una smartbox, qualcuno di voi l’ha già comprata in modalità “regalo”? Mi potete dire come funziona? Grazie mille a tutti per le risposteee',
            'replies' => [
                ['author' => 'Matteo', 'body' => 'Ciao Sofia! Si io ne ho comprate due, intanto ottima idea regalo, in realtà è super semplice, ti basta aggiungerla al carrello con la modalità “regalo” e poi ti verranno chiesti alcuni dati easy del destinatario e se vuoi mandargliela per email dovrai inserire anche quella, spero di esserti stato utile!'],
            ],
            'mine' => false,
        ],
    ];

    /**
     * Post del tab "I miei post" come da XD (artboard "Community – I miei post"):
     * titoli e tag propri, corpi e risposte riusati dai campioni (XD riusa gli stessi testi).
     */
    public const MY_POSTS = [
        [
            'id' => 3,
            'title' => 'Dubbi sugli eventi',
            'tag' => 'Benessere',
            'tagColor' => '#8DABFF',
            'author' => 'Tommaso (Io)',
            'body' => self::POSTS[0]['body'],
            'replies' => self::POSTS[0]['replies'],
            'mine' => true,
        ],
        [
            'id' => 4,
            'title' => 'Consigli sulle attività',
            'tag' => 'Domanda',
            'tagColor' => '#555555',
            'author' => 'Tommaso (Io)',
            'body' => self::POSTS[1]['body'],
            'replies' => self::POSTS[1]['replies'],
            'mine' => true,
        ],
    ];

    public function mount(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'tutti';
        }

        $this->posts = array_merge(self::POSTS, self::MY_POSTS);
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
        }
    }

    /** Pubblica dal composer: prepend in-memory alla lista "Tutti i post". Solo utenti loggati (ospite → login). */
    public function publish(): void
    {
        if (! Auth::check()) {
            Flux::modal('login')->show();

            return;
        }

        $body = trim($this->composerBody);

        if ($body === '') {
            return;
        }

        // Il post prende il primo tag selezionato valido (multi-selezione nel composer).
        $selected = array_values(array_intersect($this->composerTags, self::TAGS));
        $tag = $selected[0] ?? 'Avventura';

        // TODO: backend reale — per ora il post vive solo in memoria per la durata del componente.
        array_unshift($this->posts, [
            'id' => max(array_column($this->posts, 'id')) + 1,
            // Titolo = prima riga del testo, troncata.
            'title' => Str::limit(trim(Str::before($body, "\n")), 60, '…'),
            'tag' => $tag,
            'tagColor' => self::TAG_COLORS[$tag] ?? '#555555',
            'author' => Auth::user()->name,
            'body' => $body,
            'replies' => [],
            'mine' => true,
            // I post pubblicati appaiono anche in "Tutti i post" (i campioni mine=true solo in "I miei post").
            'published' => true,
        ]);

        $this->composerBody = '';
        $this->composerTags = ['Avventura'];

        // Composer mobile: chiude il pannello a tutta pagina. close() su un <dialog> non
        // aperto è un no-op, quindi da desktop non ha effetti (a differenza di show()).
        Flux::modal('scrivi-domanda')->close();
    }

    /** Il pannello filtri mobile scrive direttamente l'array: tiene solo i tag noti. */
    public function updatedActiveFilters(): void
    {
        $this->activeFilters = array_values(array_intersect(self::TAGS, $this->activeFilters));
    }

    /** Selezione dal menu "Filtra tipologia" → aggiunge la chip filtro attivo. */
    public function addFilter(string $tag): void
    {
        if (in_array($tag, self::TAGS, true) && ! in_array($tag, $this->activeFilters, true)) {
            $this->activeFilters[] = $tag;
        }
    }

    /** La × sulla chip rimuove il filtro. */
    public function removeFilter(string $tag): void
    {
        $this->activeFilters = array_values(array_diff($this->activeFilters, [$tag]));
    }

    /** Appende una risposta in-memory alla card e svuota l'input. Solo utenti loggati (ospite → login). */
    public function reply(int $postId): void
    {
        if (! Auth::check()) {
            Flux::modal('login')->show();

            return;
        }

        $body = trim($this->replyDrafts[$postId] ?? '');

        if ($body === '') {
            return;
        }

        // TODO: backend reale.
        foreach ($this->posts as &$post) {
            if ($post['id'] === $postId) {
                $post['replies'][] = ['author' => Auth::user()->name, 'body' => $body];
                break;
            }
        }
        unset($post);

        unset($this->replyDrafts[$postId]);
    }

    public function render()
    {
        $needle = trim($this->search);

        $visiblePosts = collect($this->posts)
            // Tab: "miei" = post dell'utente; "tutti" = campioni non-miei + post appena pubblicati.
            ->filter(fn (array $post): bool => $this->tab === 'miei'
                ? $post['mine']
                : (! $post['mine'] || ! empty($post['published'])))
            ->when($needle !== '', fn ($posts) => $posts->filter(
                fn (array $post): bool => mb_stripos($post['title'], $needle) !== false
                    || mb_stripos($post['body'], $needle) !== false,
            ))
            ->when($this->activeFilters !== [], fn ($posts) => $posts->filter(
                fn (array $post): bool => in_array($post['tag'], $this->activeFilters, true),
            ))
            ->values()
            ->all();

        return view('livewire.content.community', [
            'visiblePosts' => $visiblePosts,
            'tags' => self::TAGS,
        ])->title(__('community.page_title'));
    }
}
