<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Vacancies extends Migration
{

    public function up()
    {
        Schema::create('Vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('employer');//name,id,logo_urls->original
            $table->json('area');//name,id
            $table->json('salary')->nullable();//to,from,currency
            $table->json('experience');//name id
            $table->longText('description');
            $table->json('specialization');//name id
            $table->json('contacts')->nullable();//name,email,phones or null
            $table->json('schedule');//id=fullDay name=Полный день
            $table->timestampsTz();

            //Подробный вывод
            //name
            //salary->from salary->to salary->currency
            //employer->name
            //employer->logo_urls->90,240,original
            //area->name
            //experience->name Опыт
            //schedule->name График
            //description Описание
        });
    }

    public function down()
    {
        Schema::dropIfExists('Vacancies');
    }
}
