<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListJobClub extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('list_job_club', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('added_by');
            $table->integer('patient_id');
            $table->string('job_club');

            $table->integer('location_services');
            $table->integer('type_diagnosis_id');
	        $table->string('category_services');	// 0->Assistant/supervision or External 1->Clinical work 
            $table->integer('services_id')->nullable();
            $table->integer('code_id')->nullable();
            $table->integer('sub_code_id')->nullable();
            $table->integer('complexity_services')->nullable();
            $table->integer('outcome')->nullable();
            $table->string('medication_des',2500)->nullable();
            $table->enum('status', [0, 1, 2])->default(1);
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
        Schema::dropIfExists('list_job_club');
    }
}
