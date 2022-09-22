<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SummariesSended extends Migration
{
    public function up()
    {
        Schema::create('summaries_sended', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('email');
            $table->string('phone');
            $table->string('education');
            $table->string('status')->default('Отправлена');
            $table->string('about_myself')->nullable();
            $table->bigInteger('id_vacancy');
            $table->bigInteger('id_user');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('summaries_sended');
    }
}
