<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Summary extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'surname',
        'education',
        'email',
        'phone',
        'about_myself',
        'updated_at',
        'id_user'
    ];
    protected $hidden = [
        'id_user'
    ];

}
