<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->id();
              $table->string('room_id')->unique();
        $table->string('title')->nullable();
        $table->enum('status', ['idle','live','scheduled','ended'])->default('idle');
        $table->string('host_name')->nullable();
        $table->json('guest_list')->nullable();
        $table->string('pre_recorded_path')->nullable();
        $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
