<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrexnodesRequestLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trexnodes_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable();
            $table->longText('request')->nullable();
            $table->longText('response')->nullable();
            $table->longText('result')->nullable();
            $table->integer('status')->nullable();
            $table->longText('message')->nullable();
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
        Schema::dropIfExists('trexnodes_request_logs');
    }
}
