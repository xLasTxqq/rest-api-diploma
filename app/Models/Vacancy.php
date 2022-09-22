<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacancy extends Model
{
    use HasFactory;

    protected $table = 'vacancies';

    public function summary()
    {
        return $this->hasMany(Summary_send::class, 'id_vacancy', 'id');
    }

    protected $fillable = [
        'name',
        'employer',
        'area',
        'salary',
        'experience',
        'specialization',
        'description',
        'contacts',
        'schedule',
    ];
    public $timestamps = false;
    protected $casts = [
        'employer' => 'json',
        'area' => 'json',
        'salary' => 'json',
        'experience' => 'json',
        'specialization' => 'json',
        'contacts' => 'json',
        'schedule' => 'json',
    ];
}
