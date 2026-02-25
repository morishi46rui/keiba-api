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
        Schema::create('shutubas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_info_id')->constrained('race_infos')->cascadeOnDelete();
            $table->integer('frame_number')->comment('枠番');
            $table->integer('horse_number')->comment('馬番');
            $table->string('horse_name')->comment('馬名');
            $table->string('horse_id')->nullable()->comment('馬ID');
            $table->string('sex')->nullable()->comment('性別');
            $table->integer('age')->nullable()->comment('年齢');
            $table->string('jockey_weight')->nullable()->comment('斤量');
            $table->string('jockey_name')->nullable()->comment('騎手名');
            $table->string('jockey_id')->nullable()->comment('騎手ID');
            $table->string('trainer_name')->nullable()->comment('調教師名');
            $table->string('trainer_id')->nullable()->comment('調教師ID');
            $table->string('horse_weight')->nullable()->comment('馬体重');
            $table->string('odds')->nullable()->comment('オッズ');
            $table->string('pop')->nullable()->comment('人気');
            $table->timestamps();

            $table->index('race_info_id');
            $table->index('horse_id');
            $table->index('jockey_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shutubas');
    }
};
