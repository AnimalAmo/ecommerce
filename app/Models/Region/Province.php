<?php

namespace App\Models\Region;

use Database\Seeders\ProvinceSeeder;
use Illuminate\Database\Eloquent\Model;

/**
 * Provincia italiana (sigla + nome, dataset condiviso matsuri/storica). Dati di
 * riferimento per le select "Provincia" dei form partner; seed in
 * {@see ProvinceSeeder} da locations-data/provinces.json.
 */
class Province extends Model
{
    protected $fillable = [
        'short_name',
        'name',
    ];
}
