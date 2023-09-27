<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScreenModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('screen_modules', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('added_by');
            $table->string('module_code');
            $table->string('module_name');
            $table->string('module_short_name');
            $table->tinyInteger('module_order');
            $table->enum('module_status', [0, 1])->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('screen_modules');
    }
}
