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
        Schema::create('lap_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_info_id')->constrained('race_infos')->onDelete('cascade');
            $table->integer('furlong_no')->comment('ハロン数 (1, 2, 3, ...)');
            $table->string('lap_time')->comment('ラップタイム (例: 12.3)');
            $table->timestamps();

            // インデックス
            $table->index('race_info_id');
            $table->index(['race_info_id', 'furlong_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lap_times');
    }
};
