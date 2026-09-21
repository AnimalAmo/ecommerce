<?php

namespace App\Services\Admin\Catalog;

use RuntimeException;

/** Cancellazione rifiutata: la scheda ha prenotazioni future. Il messaggio è per la cliente. */
class CatalogItemLocked extends RuntimeException {}
