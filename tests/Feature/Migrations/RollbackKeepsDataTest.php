<?php

namespace Tests\Feature\Migrations;

use App\Models\Article\Article;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Models\User;
use App\Services\Content\ArticleService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * `migrate:rollback --force` è la reazione standard a un deploy andato
 * storto, e le migration del pannello stanno tutte nello stesso batch.
 * Tornare indietro non deve costare i consensi newsletter raccolti da luglio
 * né le copertine caricate dalla cliente.
 */
class RollbackKeepsDataTest extends TestCase
{
    use RefreshDatabase;

    private const NEWSLETTER_PROOF = 'database/migrations/2026_09_19_230001_add_confirmation_proof_and_delivery_to_newsletter.php';

    private const NEWSLETTER_TABLES = 'database/migrations/2026_09_19_100005_create_newsletter_tables.php';

    private const MEDIA_TABLE = 'database/migrations/2026_09_19_220001_create_media_table.php';

    /**
     * La up() della 230001 spegne `users.newsletter` per chi non ha
     * confermato: la down() lo riaccende per chi è ancora in lista, prima
     * che il vecchio flag torni a essere l'unica traccia dell'iscrizione.
     */
    public function test_rolling_back_the_confirmation_proof_gives_the_flag_back_to_who_is_on_the_list(): void
    {
        $legacy = $this->user();
        NewsletterSubscriber::factory()->legacy()->create(['user_id' => $legacy->id, 'email' => $legacy->email]);

        $confirmed = $this->user(newsletter: true);
        NewsletterSubscriber::factory()->confirmed()->create(['user_id' => $confirmed->id, 'email' => $confirmed->email]);

        $unsubscribed = $this->user();
        NewsletterSubscriber::factory()->unsubscribed()->create(['user_id' => $unsubscribed->id, 'email' => $unsubscribed->email]);

        $bounced = $this->user();
        NewsletterSubscriber::factory()->bounced()->create(['user_id' => $bounced->id, 'email' => $bounced->email]);

        $never = $this->user();
        NewsletterSubscriber::factory()->create(); // dal piede del sito, senza account

        (require base_path(self::NEWSLETTER_PROOF))->down();

        $this->assertTrue($legacy->refresh()->newsletter, 'contatto legacy in attesa perso');
        $this->assertTrue($confirmed->refresh()->newsletter);
        $this->assertFalse($unsubscribed->refresh()->newsletter);
        $this->assertFalse($bounced->refresh()->newsletter);
        $this->assertFalse($never->refresh()->newsletter);
        $this->assertFalse(Schema::hasColumn('newsletter_subscribers', 'confirmation_ip'));
    }

    public function test_rolling_back_the_newsletter_tables_refuses_to_throw_away_subscribers(): void
    {
        NewsletterSubscriber::factory()->legacy()->create();

        $refusal = $this->rollBack(self::NEWSLETTER_TABLES);

        $this->assertNotNull($refusal, 'rollback eseguito: iscritti buttati');
        $this->assertStringContainsString('newsletter_subscribers', $refusal->getMessage());
        $this->assertStringNotContainsString('admin.', $refusal->getMessage(), 'messaggio non tradotto');
        $this->assertTrue(Schema::hasTable('newsletter_campaigns'));
        $this->assertTrue(Schema::hasTable('newsletter_campaign_recipients'));
        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_rolling_back_empty_newsletter_tables_still_drops_them(): void
    {
        $this->assertNull($this->rollBack(self::NEWSLETTER_TABLES));

        $this->assertFalse(Schema::hasTable('newsletter_subscribers'));
        $this->assertFalse(Schema::hasTable('newsletter_campaigns'));
        $this->assertFalse(Schema::hasTable('newsletter_campaign_recipients'));
    }

    public function test_rolling_back_the_media_table_refuses_to_orphan_the_covers(): void
    {
        Storage::fake('public');
        $article = Article::create([
            'slug' => 'viaggiare-con-il-tuo-animale',
            'title' => ['it' => 'Viaggiare'],
            'body' => ['it' => '<p>Testo.</p>'],
        ]);
        app(ArticleService::class)->importSeedCover($article);

        $refusal = $this->rollBack(self::MEDIA_TABLE);

        $this->assertNotNull($refusal, 'rollback eseguito: copertine perse');
        $this->assertStringContainsString('media', $refusal->getMessage());
        $this->assertTrue(Schema::hasTable('media'));
        $this->assertTrue($article->refresh()->hasMedia(Article::COVER));
    }

    public function test_rolling_back_an_empty_media_table_still_drops_it(): void
    {
        $this->assertNull($this->rollBack(self::MEDIA_TABLE));

        $this->assertFalse(Schema::hasTable('media'));
    }

    private function rollBack(string $migration): ?RuntimeException
    {
        try {
            (require base_path($migration))->down();
        } catch (QueryException $error) {
            throw $error; // un errore del database non è un rifiuto
        } catch (RuntimeException $refusal) {
            return $refusal;
        }

        return null;
    }

    private function user(bool $newsletter = false): User
    {
        return User::factory()->create(['newsletter' => $newsletter]);
    }
}
