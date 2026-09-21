<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Admin\AdminAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Primo accesso al pannello: promuove (o crea) un account amministratore.
 *
 * Non stampa mai una password. Un account nuovo nasce con una password
 * casuale che nessuno conosce, e riceve la mail per sceglierne una — la
 * stessa del "Password dimenticata" del pannello.
 */
class MakeSuperadminCommand extends Command
{
    protected $signature = 'animalamo:make-superadmin
        {email : Indirizzo dell\'amministratore}
        {--first-name= : Nome, solo per un account nuovo}
        {--last-name= : Cognome, solo per un account nuovo}
        {--no-mail : Non inviare la mail per scegliere la password}';

    protected $description = 'Dà a un account l\'accesso al pannello di amministrazione (lo crea se non esiste)';

    public function handle(AdminAuthService $auth): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error("Indirizzo non valido: {$email}");

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        $created = $user === null;

        $user ??= User::query()->create([
            'email' => $email,
            'first_name' => (string) ($this->option('first-name') ?? Str::before($email, '@')),
            'last_name' => (string) ($this->option('last-name') ?? ''),
            'password' => Str::password(40),
            'is_active' => true,
        ]);

        if (! $user->is_active) {
            $user->forceFill(['is_active' => true])->save();
        }

        $user->assignRole('superadmin');

        $this->info(($created ? 'Creato' : 'Promosso')." l'amministratore {$email}.");

        if (! $this->option('no-mail')) {
            // Subito, non dopo la risposta come dal form: qui non c'è una
            // risposta da proteggere, e a comando finito la mail deve essere partita.
            $auth->mailResetLink($email);
            $this->line('Inviata la mail per scegliere la password ('.route('admin.password.request').').');
        }

        return self::SUCCESS;
    }
}
