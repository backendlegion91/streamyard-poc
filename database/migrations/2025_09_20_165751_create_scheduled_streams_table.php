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
        Schema::create('scheduled_streams', function (Blueprint $table) {
            $table->id();
                $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
    $table->timestamp('scheduled_at');
    $table->enum('status', ['pending', 'live', 'done'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_streams');
    }
};
