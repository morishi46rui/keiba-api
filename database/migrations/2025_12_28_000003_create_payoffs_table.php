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
        Schema::create('payoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_info_id')->constrained('race_infos')->onDelete('cascade');
            $table->integer('ticket_type')->comment('馬券種類 (0:単勝, 1:複勝, 2:枠連, 3:馬連, 4:ワイド, 5:馬単, 6:三連複, 7:三連単)');
            $table->string('horse_number')->comment('馬番 (例: 1, 1-2, 1-2-3)');
            $table->string('payoff')->comment('払戻金 (例: 1,000円)');
            $table->string('favorite_order')->comment('人気順 (例: 1番人気)');
            $table->timestamps();

            // インデックス
            $table->index('race_info_id');
            $table->index(['race_info_id', 'ticket_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payoffs');
    }
};
