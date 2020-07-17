<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp', function (Blueprint $table) {
            $table->id();
            $table->string('full_name_previous');
            $table->string('card_number_previous');
            $table->string('email_previous');
            $table->string('full_name');
            $table->string('card_number')->unique();
            $table->string('email');
            $table->enum('action', ['added', 'updated', 'restored']);
            $table->boolean('is_failed');
            $table->string('fail_reason');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temp');
    }
}
