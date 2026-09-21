<?php

namespace App\Models\Content;

use App\Models\User;
use App\Services\Content\ContentBlockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Testo di una pagina pubblica riscritto dal pannello: sovrascrive, lingua per
 * lingua, la chiave del file lingua indicata in `key`. La riga esiste solo dove
 * la cliente ha scritto qualcosa; altrove resta il file (vedi cms()).
 *
 * Le chiavi ammesse sono quelle del registro config/admin-content.php.
 */
class ContentBlock extends Model
{
    use HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['value'];

    /** @var list<string> */
    protected $fillable = ['key', 'value', 'updated_by'];

    protected static function booted(): void
    {
        // Una sola mappa in cache per tutti i blocchi: qualsiasi scrittura la invalida.
        static::saved(fn () => app(ContentBlockService::class)->forget());
        static::deleted(fn () => app(ContentBlockService::class)->forget());
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
