<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorrectionRequestsTable extends Migration
{
    
    public function up()
    {
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('attendance_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('requested_clock_in_at');
            $table->dateTime('requested_clock_out_at');

            $table->text('requested_note');

            $table->unsignedTinyInteger('status')->default(0);

            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('correction_requests');
    }
}
