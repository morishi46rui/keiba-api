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
        Schema::create('corner_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_info_id')->constrained('race_infos')->onDelete('cascade');
            $table->integer('corner_number')->comment('コーナー番号 (1-4)');
            $table->text('position_text')->comment('通過順位テキスト (例: 1,2,3-4,5)');
            $table->timestamps();

            // インデックス
            $table->index('race_info_id');
            $table->index(['race_info_id', 'corner_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corner_positions');
    }
};
