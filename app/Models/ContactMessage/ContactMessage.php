<?php

namespace App\Models\ContactMessage;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'reason',
        'message',
    ];

    protected function casts(): array
    {
        return [
            // "Segna come lavorata" / "Archivia" del pannello (Contatti e candidature).
            'handled_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
