<?php

namespace App\Livewire\Concerns;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;

trait RedirectsAfterAuth
{
    /**
     * Epilogo comune post-autenticazione (login, login partner, registrazione):
     * rigenera la sessione, chiude la modale e ricarica la pagina corrente così
     * l'header passa allo stato autenticato.
     */
    protected function finishAuthentication(string $modalName): void
    {
        session()->regenerate();

        Flux::modal($modalName)->close();

        $this->redirect($this->authRedirectUrl());
    }

    /**
     * URL di ritorno dopo il login: la pagina corrente (nel round-trip Livewire
     * il Referer è la pagina ospite), con fallback sicuro sulla home.
     *
     * Eccezione: il partner attivo va nella sua area. Tornando "dov'era"
     * restava sulla pagina pubblica da cui aveva aperto la modale, e il login
     * sembrava non essere andato a buon fine. Il controllo su is_active non è
     * pignoleria: senza, un partner sospeso finirebbe sul 403 di
     * EnsureActivePartner invece che su una pagina qualsiasi.
     */
    protected function authRedirectUrl(): string
    {
        $user = Auth::user();

        if ($user !== null && $user->is_active && $user->hasRole('partner')) {
            return route('partner.dashboard');
        }

        $previous = url()->previous();
        $root = url('/');

        // Confronto con lo slash finale: senza, "https://host.evil.io" passerebbe il prefisso (open redirect).
        return $previous === $root || str_starts_with($previous, $root.'/')
            ? $previous
            : route('home');
    }
}
