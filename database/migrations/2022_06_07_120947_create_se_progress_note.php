<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeProgressNote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('se_progress_note', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('patient_mrn_id');
            $table->integer('patient_id');
            $table->integer('added_by');
            $table->string('name');
            $table->string('mrn');
            $table->date('date');
            $table->time('time');
            $table->string('staff_name');
            $table->string('activity_type');
            $table->string('employment_status');
            $table->string('progress_note');
            $table->string('management_plan');
            $table->integer('location_service');
            $table->integer('diagnosis_type');
            $table->string('service_category');
            $table->integer('services_id')->nullable();
            $table->integer('code_id')->nullable();
            $table->integer('sub_code_id')->nullable();
            $table->integer('complexity_service');
            $table->integer('outcome');
            $table->string('medication')->nullable();
            $table->enum('status', [0, 1,2])->default(1);
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
        Schema::dropIfExists('se_progress_note');
    }
}
