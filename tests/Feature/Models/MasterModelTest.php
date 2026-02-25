<?php

namespace Tests\Feature\Models;

use App\Models\Breeder;
use App\Models\Horse;
use App\Models\Jockey;
use App\Models\Owner;
use App\Models\Trainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MasterModelTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // AC5: マイグレーションが正常に実行できる
    // ========================================

    public function test_horsesテーブルが作成される(): void
    {
        $this->assertTrue(Schema::hasTable('horses'));
    }

    public function test_jockeysテーブルが作成される(): void
    {
        $this->assertTrue(Schema::hasTable('jockeys'));
    }

    public function test_trainersテーブルが作成される(): void
    {
        $this->assertTrue(Schema::hasTable('trainers'));
    }

    public function test_ownersテーブルが作成される(): void
    {
        $this->assertTrue(Schema::hasTable('owners'));
    }

    public function test_breedersテーブルが作成される(): void
    {
        $this->assertTrue(Schema::hasTable('breeders'));
    }

    // ========================================
    // AC6: 各マスターテーブルはnetkeiba_id(string, unique)カラムを持つ
    // ========================================

    /**
     * @dataProvider masterTableProvider
     */
    public function test_マスターテーブルにnetkeiba_idカラムが存在する(string $table): void
    {
        $this->assertTrue(Schema::hasColumn($table, 'netkeiba_id'));
    }

    /**
     * @dataProvider masterTableProvider
     */
    public function test_マスターテーブルにnameカラムが存在する(string $table): void
    {
        $this->assertTrue(Schema::hasColumn($table, 'name'));
    }

    /**
     * @dataProvider masterTableProvider
     */
    public function test_マスターテーブルにtimestampsカラムが存在する(string $table): void
    {
        $this->assertTrue(Schema::hasColumns($table, ['created_at', 'updated_at']));
    }

    public static function masterTableProvider(): array
    {
        return [
            'horses' => ['horses'],
            'jockeys' => ['jockeys'],
            'trainers' => ['trainers'],
            'owners' => ['owners'],
            'breeders' => ['breeders'],
        ];
    }

    // ========================================
    // AC7: Horse::findOrCreateByUniqueKey() の動作
    // ========================================

    public function test_horse_findOrCreateByUniqueKeyで新規レコードが作成される(): void
    {
        $horse = Horse::findOrCreateByUniqueKey([
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
        ]);

        $this->assertInstanceOf(Horse::class, $horse);
        $this->assertDatabaseHas('horses', [
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
        ]);
    }

    public function test_horse_findOrCreateByUniqueKeyで既存レコードが返される(): void
    {
        Horse::create([
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
        ]);

        $horse = Horse::findOrCreateByUniqueKey([
            'netkeiba_id' => '2019104308',
            'name' => '別の名前',
        ]);

        $this->assertEquals('テスト馬', $horse->name);
        $this->assertDatabaseCount('horses', 1);
    }

    public function test_horse_findOrCreateByUniqueKeyで重複レコードが作成されない(): void
    {
        Horse::findOrCreateByUniqueKey([
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
        ]);

        Horse::findOrCreateByUniqueKey([
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
        ]);

        $this->assertDatabaseCount('horses', 1);
    }

    // ========================================
    // AC7: Jockey::findOrCreateByUniqueKey() の動作
    // ========================================

    public function test_jockey_findOrCreateByUniqueKeyで新規作成と既存返却が正しく動作する(): void
    {
        $jockey = Jockey::findOrCreateByUniqueKey([
            'netkeiba_id' => '05339',
            'name' => 'テスト騎手',
        ]);

        $this->assertInstanceOf(Jockey::class, $jockey);
        $this->assertDatabaseHas('jockeys', [
            'netkeiba_id' => '05339',
            'name' => 'テスト騎手',
        ]);

        // 同一netkeiba_idで再度呼び出し
        $existing = Jockey::findOrCreateByUniqueKey([
            'netkeiba_id' => '05339',
            'name' => '別の名前',
        ]);

        $this->assertEquals($jockey->id, $existing->id);
        $this->assertDatabaseCount('jockeys', 1);
    }

    // ========================================
    // AC7: Trainer::findOrCreateByUniqueKey() の動作
    // ========================================

    public function test_trainer_findOrCreateByUniqueKeyで新規作成と既存返却が正しく動作する(): void
    {
        $trainer = Trainer::findOrCreateByUniqueKey([
            'netkeiba_id' => '01088',
            'name' => 'テスト調教師',
        ]);

        $this->assertInstanceOf(Trainer::class, $trainer);
        $this->assertDatabaseHas('trainers', [
            'netkeiba_id' => '01088',
            'name' => 'テスト調教師',
        ]);

        $existing = Trainer::findOrCreateByUniqueKey([
            'netkeiba_id' => '01088',
            'name' => '別の名前',
        ]);

        $this->assertEquals($trainer->id, $existing->id);
        $this->assertDatabaseCount('trainers', 1);
    }

    // ========================================
    // AC7: Owner::findOrCreateByUniqueKey() の動作
    // ========================================

    public function test_owner_findOrCreateByUniqueKeyで新規作成と既存返却が正しく動作する(): void
    {
        $owner = Owner::findOrCreateByUniqueKey([
            'netkeiba_id' => '040568',
            'name' => 'テスト馬主',
        ]);

        $this->assertInstanceOf(Owner::class, $owner);
        $this->assertDatabaseHas('owners', [
            'netkeiba_id' => '040568',
            'name' => 'テスト馬主',
        ]);

        $existing = Owner::findOrCreateByUniqueKey([
            'netkeiba_id' => '040568',
            'name' => '別の名前',
        ]);

        $this->assertEquals($owner->id, $existing->id);
        $this->assertDatabaseCount('owners', 1);
    }

    // ========================================
    // AC7: Breeder::findOrCreateByUniqueKey() の動作
    // ========================================

    public function test_breeder_findOrCreateByUniqueKeyで新規作成と既存返却が正しく動作する(): void
    {
        $breeder = Breeder::findOrCreateByUniqueKey([
            'netkeiba_id' => '352527',
            'name' => 'テスト牧場',
        ]);

        $this->assertInstanceOf(Breeder::class, $breeder);
        $this->assertDatabaseHas('breeders', [
            'netkeiba_id' => '352527',
            'name' => 'テスト牧場',
        ]);

        $existing = Breeder::findOrCreateByUniqueKey([
            'netkeiba_id' => '352527',
            'name' => '別の名前',
        ]);

        $this->assertEquals($breeder->id, $existing->id);
        $this->assertDatabaseCount('breeders', 1);
    }

    // ========================================
    // 境界値テスト
    // ========================================

    public function test_horse_findOrCreateByUniqueKeyでnameがnullの場合も作成できる(): void
    {
        $horse = Horse::findOrCreateByUniqueKey([
            'netkeiba_id' => '2019104308',
        ]);

        $this->assertInstanceOf(Horse::class, $horse);
        $this->assertNull($horse->name);
        $this->assertDatabaseHas('horses', [
            'netkeiba_id' => '2019104308',
        ]);
    }
}
