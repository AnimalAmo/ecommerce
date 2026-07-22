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
}
