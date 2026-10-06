<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Communaute extends Model
{
    protected $fillable = [
        'nom',
        'ville',
    ];
}