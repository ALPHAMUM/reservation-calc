<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_rates', function (Blueprint $table) {
            $table->id();
            $table->string('rate_code')->unique();
            $table->decimal('rate_value', 12, 2);
            $table->string('type'); // villa or suite
            $table->decimal('rate_extra', 12, 2);
            $table->string('description')->nullable();
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
        Schema::dropIfExists('tbl_rates');
    }
}
