<?php

namespace App\Livewire\Concerns;

use Flux\Flux;

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
     */
    protected function authRedirectUrl(): string
    {
        $previous = url()->previous();
        $root = url('/');

        // Confronto con lo slash finale: senza, "https://host.evil.io" passerebbe il prefisso (open redirect).
        return $previous === $root || str_starts_with($previous, $root.'/')
            ? $previous
            : route('home');
    }
}
