<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationScTargetsTable extends Migration
{
    public function up()
    {
        Schema::create('reservation_sc_targets', function (Blueprint $table) {
            $table->id();
            $table->string('property', 20);
            $table->string('res_no', 50);
            $table->unsignedSmallInteger('occupant_idx');
            $table->string('mode', 10);
            $table->timestamps();

            $table->unique(['property', 'res_no', 'occupant_idx']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservation_sc_targets');
    }
}
