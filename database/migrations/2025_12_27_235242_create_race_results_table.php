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
        Schema::create('race_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_info_id')->constrained('race_infos')->onDelete('cascade');
            $table->integer('order_of_finish');
            $table->integer('frame_number');
            $table->integer('horse_number');
            $table->string('horse_name');
            $table->string('sex');
            $table->integer('age');
            $table->string('jockey_weight');
            $table->string('jockey_name');
            $table->string('time');
            $table->string('margin');
            $table->string('pop');
            $table->string('odds');
            $table->string('last_3F');
            $table->string('pass');
            $table->string('horse_weight');
            $table->string('stable');
            $table->string('horse_id');
            $table->string('jockey_id');
            $table->string('trainer_id');
            $table->string('owner_id');
            $table->integer('position')->nullable();
            $table->integer('position_label')->nullable();
            $table->timestamps();

            // インデックスを追加
            $table->index('race_info_id');
            $table->index('horse_id');
            $table->index('jockey_id');
            $table->index('trainer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_results');
    }
};
