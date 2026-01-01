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
        Schema::create('race_infos', function (Blueprint $table) {
            $table->id();
            $table->string('race_name')->nullable();
            $table->string('surface')->nullable();
            $table->integer('distance')->nullable();
            $table->string('weather')->nullable();
            $table->string('surface_state')->nullable();
            $table->time('race_start')->nullable();
            $table->integer('race_number');
            $table->date('date');
            $table->string('place_detail');
            $table->text('race_class')->nullable();
            $table->timestamps();

            // インデックスを追加
            $table->index('date');
            $table->index(['id', 'date']);
            $table->unique(['date', 'race_number', 'place_detail'], 'race_info_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_infos');
    }
};
