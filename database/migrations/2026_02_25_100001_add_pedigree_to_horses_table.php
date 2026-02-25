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
        Schema::table('horses', function (Blueprint $table) {
            $table->string('sex')->nullable()->comment('性別');
            $table->string('coat_color')->nullable()->comment('毛色');
            $table->integer('birth_year')->nullable()->comment('生年');
            $table->string('sire')->nullable()->comment('父');
            $table->string('dam')->nullable()->comment('母');
            $table->string('sire_of_dam')->nullable()->comment('母父');
            $table->string('sire_sire')->nullable()->comment('父の父');
            $table->string('dam_dam')->nullable()->comment('母の母');
            $table->string('trainer_id')->nullable()->comment('調教師ID');
            $table->string('owner_id')->nullable()->comment('馬主ID');
            $table->string('breeder_id')->nullable()->comment('生産者ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horses', function (Blueprint $table) {
            $table->dropColumn([
                'sex',
                'coat_color',
                'birth_year',
                'sire',
                'dam',
                'sire_of_dam',
                'sire_sire',
                'dam_dam',
                'trainer_id',
                'owner_id',
                'breeder_id',
            ]);
        });
    }
};
