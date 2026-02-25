<?php

namespace Tests\Feature\Models;

use App\Models\RaceInfo;
use App\Models\Shutuba;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShutubaModelTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // マイグレーション: shutubasテーブルの存在確認
    // ========================================

    public function test_shutubasテーブルが作成される(): void
    {
        $this->assertTrue(Schema::hasTable('shutubas'));
    }

    // ========================================
    // マイグレーション: shutubasテーブルのカラム確認
    // ========================================

    public function test_shutubasテーブルに必要なカラムが存在する(): void
    {
        $expectedColumns = [
            'id',
            'race_info_id',
            'frame_number',
            'horse_number',
            'horse_name',
            'horse_id',
            'sex',
            'age',
            'jockey_weight',
            'jockey_name',
            'jockey_id',
            'trainer_name',
            'trainer_id',
            'horse_weight',
            'odds',
            'pop',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('shutubas', $column),
                "カラム '{$column}' がshutubsテーブルに存在しません"
            );
        }
    }

    // ========================================
    // リレーション: raceInfoとのbelongsTo
    // ========================================

    public function test_shutubaはraceInfoにbelongsToで紐づく(): void
    {
        $raceInfo = RaceInfo::create([
            'race_name' => 'テストレース',
            'race_number' => 1,
            'date' => '2024-01-06',
            'place_detail' => '1回中山1日',
        ]);

        $shutuba = Shutuba::create([
            'race_info_id' => $raceInfo->id,
            'frame_number' => 1,
            'horse_number' => 1,
            'horse_name' => 'テスト馬',
        ]);

        $this->assertInstanceOf(RaceInfo::class, $shutuba->raceInfo);
        $this->assertEquals($raceInfo->id, $shutuba->raceInfo->id);
    }

    // ========================================
    // fillable: 複数代入可能な属性の確認
    // ========================================

    public function test_fillableで定義された属性が正しく保存される(): void
    {
        $raceInfo = RaceInfo::create([
            'race_name' => 'テストレース',
            'race_number' => 1,
            'date' => '2024-01-06',
            'place_detail' => '1回中山1日',
        ]);

        $shutuba = Shutuba::create([
            'race_info_id' => $raceInfo->id,
            'frame_number' => 3,
            'horse_number' => 5,
            'horse_name' => 'テスト馬名',
            'horse_id' => '2019104308',
            'sex' => '牡',
            'age' => 3,
            'jockey_weight' => '57.0',
            'jockey_name' => 'テスト騎手',
            'jockey_id' => '05339',
            'trainer_name' => 'テスト調教師',
            'trainer_id' => '01088',
            'horse_weight' => '480(+4)',
            'odds' => '2.5',
            'pop' => '1',
        ]);

        $this->assertDatabaseHas('shutubas', [
            'race_info_id' => $raceInfo->id,
            'frame_number' => 3,
            'horse_number' => 5,
            'horse_name' => 'テスト馬名',
            'horse_id' => '2019104308',
            'sex' => '牡',
            'age' => 3,
            'jockey_weight' => '57.0',
            'jockey_name' => 'テスト騎手',
            'jockey_id' => '05339',
            'trainer_name' => 'テスト調教師',
            'trainer_id' => '01088',
            'horse_weight' => '480(+4)',
            'odds' => '2.5',
            'pop' => '1',
        ]);
    }

    // ========================================
    // キャスト: 整数型キャストの確認
    // ========================================

    public function test_キャストが正しく適用される(): void
    {
        $raceInfo = RaceInfo::create([
            'race_name' => 'テストレース',
            'race_number' => 1,
            'date' => '2024-01-06',
            'place_detail' => '1回中山1日',
        ]);

        $shutuba = Shutuba::create([
            'race_info_id' => $raceInfo->id,
            'frame_number' => 3,
            'horse_number' => 5,
            'horse_name' => 'テスト馬',
            'age' => 3,
        ]);

        $shutuba->refresh();

        $this->assertIsInt($shutuba->race_info_id);
        $this->assertIsInt($shutuba->frame_number);
        $this->assertIsInt($shutuba->horse_number);
        $this->assertIsInt($shutuba->age);
    }

    // ========================================
    // カスケード削除: RaceInfo削除時にShutubaも削除
    // ========================================

    public function test_raceInfo削除時にshutubsが連動して削除される(): void
    {
        $raceInfo = RaceInfo::create([
            'race_name' => 'テストレース',
            'race_number' => 1,
            'date' => '2024-01-06',
            'place_detail' => '1回中山1日',
        ]);

        Shutuba::create([
            'race_info_id' => $raceInfo->id,
            'frame_number' => 1,
            'horse_number' => 1,
            'horse_name' => 'テスト馬1',
        ]);

        Shutuba::create([
            'race_info_id' => $raceInfo->id,
            'frame_number' => 2,
            'horse_number' => 2,
            'horse_name' => 'テスト馬2',
        ]);

        $this->assertDatabaseCount('shutubas', 2);

        $raceInfo->delete();

        $this->assertDatabaseCount('shutubas', 0);
    }
}
