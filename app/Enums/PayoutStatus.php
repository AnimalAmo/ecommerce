<?php

namespace App\Enums;

/** Ciclo di vita di una riga del registro dei rilasci. */
enum PayoutStatus: string
{
    case Pending = 'pending';
    case Released = 'released';
    case Reversed = 'reversed';
    case Failed = 'failed';
    /** Riga interamente di piattaforma: nessun partner da pagare. */
    case PlatformOnly = 'platform_only';
}
