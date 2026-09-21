<?php

namespace App\Services\Newsletter\Exceptions;

use RuntimeException;

/** "Invia a tutti" rifiutato: il messaggio, già tradotto, va mostrato così com'è. */
class CampaignNotLaunchable extends RuntimeException {}
