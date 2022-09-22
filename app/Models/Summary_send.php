<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Summary_send extends Model
{
    use HasFactory;

    protected $table='summaries_sended';

    public function vacancy(){
        return $this->hasOne(Vacancy::class,'id','id_vacancy');
    }

    protected $fillable = [
        'name',
        'surname',
        'education',
        'email',
        'phone',
        'about_myself',
        'id_user',
        'id_vacancy'
    ];
    protected $hidden = [
        'id_user',
        'id_vacancy'
    ];
}
