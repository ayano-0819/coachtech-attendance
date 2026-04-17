<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorrectionRequestBreaksTable extends Migration
{

    public function up()
    {
        Schema::create('correction_request_breaks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('correction_request_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('requested_break_start_at')->nullable();
            $table->dateTime('requested_break_end_at')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('correction_request_breaks');
    }
}
