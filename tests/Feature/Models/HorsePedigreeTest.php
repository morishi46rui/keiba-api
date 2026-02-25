<?php

namespace Tests\Feature\Models;

use App\Models\Horse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HorsePedigreeTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // マイグレーション: 血統カラムの存在確認
    // ========================================

    public function test_horsesテーブルに血統カラムが存在する(): void
    {
        $pedigreeColumns = [
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
        ];

        foreach ($pedigreeColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('horses', $column),
                "カラム '{$column}' がhorsesテーブルに存在しません"
            );
        }
    }

    // ========================================
    // fillable: 血統カラムの複数代入
    // ========================================

    public function test_血統カラムがfillableに含まれている(): void
    {
        $horse = Horse::create([
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
            'sex' => '牡',
            'coat_color' => '栗毛',
            'birth_year' => 2019,
            'sire' => 'ディープインパクト',
            'dam' => 'テスト母馬',
            'sire_of_dam' => 'テスト母父',
            'sire_sire' => 'サンデーサイレンス',
            'dam_dam' => 'テスト母母',
            'trainer_id' => '01088',
            'owner_id' => '040568',
            'breeder_id' => '352527',
        ]);

        $this->assertDatabaseHas('horses', [
            'netkeiba_id' => '2019104308',
            'sex' => '牡',
            'coat_color' => '栗毛',
            'birth_year' => 2019,
            'sire' => 'ディープインパクト',
            'dam' => 'テスト母馬',
            'sire_of_dam' => 'テスト母父',
            'sire_sire' => 'サンデーサイレンス',
            'dam_dam' => 'テスト母母',
            'trainer_id' => '01088',
            'owner_id' => '040568',
            'breeder_id' => '352527',
        ]);
    }

    // ========================================
    // 血統カラムのnullable確認
    // ========================================

    public function test_血統カラムはnullableである(): void
    {
        $horse = Horse::create([
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
        ]);

        $horse->refresh();

        $this->assertNull($horse->sex);
        $this->assertNull($horse->coat_color);
        $this->assertNull($horse->birth_year);
        $this->assertNull($horse->sire);
        $this->assertNull($horse->dam);
        $this->assertNull($horse->sire_of_dam);
        $this->assertNull($horse->sire_sire);
        $this->assertNull($horse->dam_dam);
        $this->assertNull($horse->trainer_id);
        $this->assertNull($horse->owner_id);
        $this->assertNull($horse->breeder_id);
    }

    // ========================================
    // fillでの更新確認
    // ========================================

    public function test_fillで血統情報を更新できる(): void
    {
        $horse = Horse::create([
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
        ]);

        $horse->fill([
            'sex' => '牝',
            'coat_color' => '鹿毛',
            'birth_year' => 2020,
            'sire' => 'キングカメハメハ',
            'dam' => 'テスト母馬',
        ]);
        $horse->save();

        $horse->refresh();

        $this->assertEquals('牝', $horse->sex);
        $this->assertEquals('鹿毛', $horse->coat_color);
        $this->assertEquals(2020, $horse->birth_year);
        $this->assertEquals('キングカメハメハ', $horse->sire);
        $this->assertEquals('テスト母馬', $horse->dam);
    }

    // ========================================
    // findOrCreateByUniqueKeyで血統情報込みの作成
    // ========================================

    public function test_findOrCreateByUniqueKeyで血統情報込みで新規作成できる(): void
    {
        $horse = Horse::findOrCreateByUniqueKey([
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
            'sex' => '牡',
            'sire' => 'ディープインパクト',
        ]);

        $this->assertInstanceOf(Horse::class, $horse);
        $this->assertDatabaseHas('horses', [
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
            'sex' => '牡',
            'sire' => 'ディープインパクト',
        ]);
    }
}
