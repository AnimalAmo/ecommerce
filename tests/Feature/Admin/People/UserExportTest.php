<?php

namespace Tests\Feature\Admin\People;

use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_export_is_an_excel_friendly_csv(): void
    {
        $this->actingAsSuperadmin();

        $user = User::factory()->create(['first_name' => 'Niccolò', 'last_name' => 'Rossi', 'email' => 'nicco@example.com']);
        Order::factory()->paid()->for($user)->create(['total_cents' => 123450]);

        $response = $this->get(route('admin.users.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Nome;Cognome;Email', $csv);
        $this->assertStringContainsString('Niccolò;Rossi;nicco@example.com;Cliente', $csv);
        $this->assertStringContainsString(';1;1234,50;Attivo', $csv);
    }

    public function test_the_export_honours_the_filters_and_neutralises_formulas(): void
    {
        $this->actingAsSuperadmin();

        User::factory()->create(['first_name' => '=HYPERLINK("x")', 'last_name' => 'Furbo']);
        User::factory()->inactive()->create(['first_name' => 'Spento', 'last_name' => 'Disattivo']);

        $csv = $this->get(route('admin.users.export', ['status' => 'active']))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Spento', $csv);
    }

    public function test_a_guest_cannot_export(): void
    {
        $this->get(route('admin.users.export'))->assertRedirect(route('admin.login'));
    }
}
