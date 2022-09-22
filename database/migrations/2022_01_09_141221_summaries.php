<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Summaries extends Migration
{
    public function up()
    {
        Schema::create('summaries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('education');
            $table->string('about_myself')->nullable();
            $table->bigInteger('id_user');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('summaries');
    }
}
