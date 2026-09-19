<?php

namespace App\Services\Admin\People;

use App\Models\User;
use RuntimeException;

/**
 * Attiva / disattiva un account dal pannello. `is_active` è il flag che già
 * governa l'area partner (EnsureActivePartner, login partner): un partner
 * disattivato non entra più nella sua area. Un account anonimizzato resta
 * disattivato per sempre, e i superadmin non si toccano da qui.
 */
class UserAccountStatus
{
    public function setActive(User $user, bool $active): void
    {
        if ($user->anonymized_at !== null) {
            throw new RuntimeException('Un account anonimizzato non si può riattivare.');
        }

        if ($user->hasRole('superadmin')) {
            throw new RuntimeException('Gli amministratori del pannello non si gestiscono da qui.');
        }

        $user->forceFill(['is_active' => $active])->save();
    }
}
