<?php

namespace Tests\Feature\Admin\Newsletter;

use App\Models\Newsletter\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_or_a_partner_cannot_export(): void
    {
        $this->get(route('admin.newsletter.export'))->assertRedirect(route('admin.login'));

        $this->actingAsActivePartner();
        $this->get(route('admin.newsletter.export'))->assertForbidden();
    }

    public function test_the_export_carries_the_proof_of_consent_with_the_table_filters(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->confirmed()->create([
            'email' => 'luca@example.com',
            'consent_text' => 'Iscrivendomi accetto di ricevere la newsletter di AnimalAmo.',
            'consent_ip' => '198.51.100.7',
            'confirmation_ip' => '203.0.113.99',
        ]);
        NewsletterSubscriber::factory()->create(['email' => 'marta@example.com']);

        $response = $this->get(route('admin.newsletter.export', ['status' => 'confirmed']));

        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF".__('admin-newsletter.export.email').';', $csv);
        $this->assertStringContainsString('luca@example.com;Confermato;italiano;"piede del sito"', $csv);
        $this->assertStringContainsString('Iscrivendomi accetto di ricevere la newsletter di AnimalAmo.', $csv);
        $this->assertStringContainsString('198.51.100.7', $csv);
        $this->assertStringContainsString('203.0.113.99', $csv);
        $this->assertStringNotContainsString('marta@example.com', $csv);
    }

    public function test_a_malformed_filter_is_ignored(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->create(['email' => 'luca@example.com']);

        $csv = $this->get(route('admin.newsletter.export').'?status[]=confirmed')->assertOk()->streamedContent();

        $this->assertStringContainsString('luca@example.com', $csv);
    }

    /** Un indirizzo scritto da un visitatore non deve diventare una formula in Excel. */
    public function test_cells_that_excel_would_run_as_formulas_are_neutralised(): void
    {
        $this->actingAsSuperadmin();
        NewsletterSubscriber::factory()->create(['email' => '=cmd@example.com']);

        $csv = $this->get(route('admin.newsletter.export'))->streamedContent();

        $this->assertStringContainsString("'=cmd@example.com", $csv);
    }
}
