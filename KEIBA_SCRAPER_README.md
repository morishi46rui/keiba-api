# 競馬スクレイピング機能

## 概要

netkeiba.com から競馬のレース情報をスクレイピングし、データベースに保存する機能です。

## 実装内容

### 1. データベース構造

#### race_infos テーブル

レース基本情報を保存するテーブル

| カラム名      | 型        | 説明               |
| ------------- | --------- | ------------------ |
| id            | bigint    | 主キー (自動採番)  |
| race_name     | string    | レース名           |
| surface       | string    | 馬場 (芝/ダート等) |
| distance      | integer   | 距離 (メートル)    |
| weather       | string    | 天候               |
| surface_state | string    | 馬場状態           |
| race_start    | time      | レース発走時刻     |
| race_number   | integer   | レース番号         |
| date          | date      | 開催日             |
| place_detail  | string    | 開催場所詳細       |
| race_class    | text      | レースクラス       |
| created_at    | timestamp | 作成日時           |
| updated_at    | timestamp | 更新日時           |

**インデックス:**

- `date` - 日付検索用
- `(id, date)` - 複合検索用
- `(date, race_number, place_detail)` - ユニーク制約

#### race_results テーブル

レース結果を保存するテーブル

| カラム名        | 型                 | 説明                            |
| --------------- | ------------------ | ------------------------------- |
| id              | bigint             | 主キー (自動採番)               |
| race_info_id    | bigint             | race_infos テーブルへの外部キー |
| order_of_finish | integer            | 着順                            |
| frame_number    | integer            | 枠番                            |
| horse_number    | integer            | 馬番                            |
| horse_name      | string             | 馬名                            |
| sex             | string             | 性別 (牡/牝/セ)                 |
| age             | integer            | 年齢                            |
| jockey_weight   | string             | 斤量                            |
| jockey_name     | string             | 騎手名                          |
| time            | string             | タイム                          |
| margin          | string             | 着差                            |
| pop             | string             | 人気                            |
| odds            | string             | オッズ                          |
| last_3F         | string             | 上がり 3F                       |
| pass            | string             | 通過順                          |
| horse_weight    | string             | 馬体重                          |
| stable          | string             | 厩舎                            |
| horse_id        | string             | 馬 ID                           |
| jockey_id       | string             | 騎手 ID                         |
| trainer_id      | string             | 調教師 ID                       |
| owner_id        | string             | 馬主 ID                         |
| position        | integer (nullable) | 着順 (数値)                     |
| position_label  | integer (nullable) | 着順ラベル                      |
| created_at      | timestamp          | 作成日時                        |
| updated_at      | timestamp          | 更新日時                        |

**インデックス:**

- `race_info_id` - レース情報との結合用
- `horse_id` - 馬検索用
- `jockey_id` - 騎手検索用
- `trainer_id` - 調教師検索用

### 2. Eloquent モデル

#### RaceInfo モデル

[app/Models/RaceInfo.php](app/Models/RaceInfo.php)

主な機能:

- race_results との 1 対多リレーション
- `findOrCreateByUniqueKey()` メソッドでユニークキーによる検索/作成

#### RaceResult モデル

[app/Models/RaceResult.php](app/Models/RaceResult.php)

主な機能:

- race_info との多対 1 リレーション

### 3. ユーティリティクラス

#### KeibaUtil クラス

[app/Services/KeibaUtil.php](app/Services/KeibaUtil.php)

Scala の`Util`オブジェクトを PHP に移植したクラスです。

主な機能:

- `normalizeWeather()` - 天候の正規化 (晴/曇/雨/小雨/雪 → それ以外は "他")
- `normalizeSurface()` - 馬場の正規化 (芝/ダート → それ以外は "他")
- `normalizeSex()` - 性別の正規化 (牡/牝/セ → それ以外は "他")
- `normalizeCourse()` - コースの正規化 (直線/右/左/外)
- `normalizeMargin()` - 着差の正規化 (ハナ/クビ/アタマ)
- `ticketTypeToInt()` - 馬券種類を文字列から番号に変換
- `classToInt()` - クラスを文字列から番号に変換
- `normalizeClass()` - クラス表記の正規化 (新表記 → 旧表記)
- `parsePosition()` - 着順文字列を分解 (着順とラベルに分離)
- `parseTrackCode()` - レース ID から競馬場コードを抽出
- `getTrackName()` - 競馬場コードから競馬場名を取得
- `buildRaceUrl()` - レース ID から URL を構築
- `isValidRaceId()` - レース ID のバリデーション (12 桁数字)

### 4. スクレイピングサービス

#### RaceScraperService クラス

[app/Services/RaceScraperService.php](app/Services/RaceScraperService.php)

Scala の`RaceScraper`を PHP に移植したクラスです。

主な機能:

- `scrapeAll()` - 全てのレース HTML をスクレイピング
- `scrapeYear(string $year)` - 特定年のレースをスクレイピング
- `scrapeYearMonth(string $year, string $month)` - 特定年月のレースをスクレイピング

動作仕様:

1. `storage/app/url/race/YYYY/MM/race_url.txt` から URL を読み込み
2. 各 URL から HTML を取得
3. `storage/app/html/race_html/YYYY/MM/DD/競馬場名/RACE_ID.html` に保存
4. 既存ファイルはスキップ
5. ダウンロード間隔は 3 秒

### 5. Artisan コマンド

#### keiba:scrape コマンド

[app/Console/Commands/KeibaScrapeCommand.php](app/Console/Commands/KeibaScrapeCommand.php)

Scala の`Main.scala`のコマンドライン引数処理を Artisan コマンドに移植しました。

## 使用方法

### 環境設定

`.env`ファイルに以下の設定を追加:

```env
NETKEIBA_EMAIL=your-email@example.com
NETKEIBA_PASSWORD=your-password
```

### マイグレーション実行

```bash
docker-compose exec app php artisan migrate
```

### コマンド実行

#### 全てのレースをスクレイピング

```bash
docker-compose exec app php artisan keiba:scrape scrapehtml
```

#### 特定年のレースをスクレイピング

```bash
docker-compose exec app php artisan keiba:scrape scrapehtml:2024
```

#### 特定年月のレースをスクレイピング

```bash
docker-compose exec app php artisan keiba:scrape scrapehtml:2024:01
```

#### レース URL を表示

```bash
docker-compose exec app php artisan keiba:scrape raceurl:202505040701
```

## Scala 版との対応表

| Scala                   | PHP                              |
| ----------------------- | -------------------------------- |
| `object Util`           | `class KeibaUtil`                |
| `object RaceScraper`    | `class RaceScraperService`       |
| `case class RaceInfo`   | `class RaceInfo extends Model`   |
| `case class RaceResult` | `class RaceResult extends Model` |
| `object RaceInfoDao`    | `RaceInfo` モデルのメソッド      |
| `Main.main(args)`       | `KeibaScrapeCommand`             |
| ScalikeJDBC             | Eloquent ORM                     |
| SQLite                  | PostgreSQL 18                    |

## データ抽出機能

### RaceExtractorService クラス

[app/Domains/RaceExtractorService.php](app/Domains/RaceExtractorService.php)

Scala の`RowExtractor`を PHP に移植したクラスです。

主な機能:

- `extract()` - 全てのレース HTML からデータを抽出
- `extract(string $year)` - 特定年のレース HTML からデータを抽出
- `extract(string $year, string $month)` - 特定年月のレース HTML からデータを抽出
- `extractFromHtml(string $filePath)` - 単一の HTML ファイルからデータを抽出

抽出されるデータ:

- レース基本情報 (RaceInfo)
- レース結果 (RaceResult)
- コーナー通過順位 (CornerPosition)
- ラップタイム (LapTime)
- 払戻金 (Payoff)

### 追加モデル

#### CornerPosition モデル

[app/Models/CornerPosition.php](app/Models/CornerPosition.php)

コーナー通過順位を保存するモデル

| カラム名      | 型        | 説明                             |
| ------------- | --------- | -------------------------------- |
| id            | bigint    | 主キー (自動採番)                |
| race_info_id  | bigint    | race_infos テーブルへの外部キー  |
| corner_number | int       | コーナー番号 (1-4)               |
| position_text | text      | 通過順位テキスト (例: 1,2,3-4,5) |
| created_at    | timestamp | 作成日時                         |
| updated_at    | timestamp | 更新日時                         |

#### LapTime モデル

[app/Models/LapTime.php](app/Models/LapTime.php)

ラップタイムを保存するモデル

| カラム名     | 型        | 説明                            |
| ------------ | --------- | ------------------------------- |
| id           | bigint    | 主キー (自動採番)               |
| race_info_id | bigint    | race_infos テーブルへの外部キー |
| furlong_no   | int       | ハロン数 (1, 2, 3, ...)         |
| lap_time     | string    | ラップタイム (例: 12.3)         |
| created_at   | timestamp | 作成日時                        |
| updated_at   | timestamp | 更新日時                        |

#### Payoff モデル

[app/Models/Payoff.php](app/Models/Payoff.php)

払戻金を保存するモデル

| カラム名       | 型        | 説明                                                                            |
| -------------- | --------- | ------------------------------------------------------------------------------- |
| id             | bigint    | 主キー (自動採番)                                                               |
| race_info_id   | bigint    | race_infos テーブルへの外部キー                                                 |
| ticket_type    | int       | 馬券種類 (0:単勝, 1:複勝, 2:枠連, 3:馬連, 4:ワイド, 5:馬単, 6:三連複, 7:三連単) |
| horse_number   | string    | 馬番 (例: 1, 1-2, 1-2-3)                                                        |
| payoff         | string    | 払戻金 (例: 1,000 円)                                                           |
| favorite_order | string    | 人気順 (例: 1 番人気)                                                           |
| created_at     | timestamp | 作成日時                                                                        |
| updated_at     | timestamp | 更新日時                                                                        |

### keiba:extract コマンド

[app/Console/Commands/KeibaExtractCommand.php](app/Console/Commands/KeibaExtractCommand.php)

HTML ファイルからレースデータを抽出して DB に保存するコマンド

#### 全てのレースデータを抽出

```bash
docker-compose exec app php artisan keiba:extract
```

#### 特定年のレースデータを抽出

```bash
docker-compose exec app php artisan keiba:extract extract:2022
```

#### 特定年月のレースデータを抽出

```bash
docker-compose exec app php artisan keiba:extract extract:2022:03
```

## 馬情報スクレイピング・抽出機能

### HorseScraperService クラス

[app/Domains/HorseScraperService.php](app/Domains/HorseScraperService.php)

馬の個別ページHTMLをダウンロードするサービスです。

主な機能:

- `scrapeAll()` - race_results テーブルから全馬IDを取得し、HTMLをダウンロード
- `scrapeByHorseIds(array $horseIds)` - 指定した馬IDのHTMLをダウンロード

動作仕様:

1. `race_results` テーブルからユニークな `horse_id` を収集
2. `https://db.netkeiba.com/horse/{horse_id}/` からHTMLを取得
3. `storage/app/html/horse_html/{horse_id}.html` に保存（フラット構造）
4. 既存ファイルはスキップ
5. ダウンロード間隔は3秒

### HorseExtractorService クラス

[app/Domains/HorseExtractorService.php](app/Domains/HorseExtractorService.php)

馬HTMLから血統情報・基本情報を抽出してDBに保存するサービスです。

主な機能:

- `extract()` - 全ての馬HTMLから血統データを抽出
- `extractFromHtml(string $filePath)` - 単一のHTMLファイルからデータを抽出

抽出されるデータ:

- 性別、毛色、生年
- 父、母、母父、父の父、母の母
- 調教師ID、馬主ID、生産者ID

### keiba:horse-scrape コマンド

[app/Console/Commands/KeibaHorseScrapeCommand.php](app/Console/Commands/KeibaHorseScrapeCommand.php)

#### 全ての馬HTMLをスクレイピング

```bash
docker-compose exec app php artisan keiba:horse-scrape scrapehtml
```

#### 特定馬のHTMLをスクレイピング

```bash
docker-compose exec app php artisan keiba:horse-scrape scrapehtml:2019104308
```

### keiba:horse-extract コマンド

[app/Console/Commands/KeibaHorseExtractCommand.php](app/Console/Commands/KeibaHorseExtractCommand.php)

#### 全ての馬HTMLから血統データを抽出

```bash
docker-compose exec app php artisan keiba:horse-extract
```

### horses テーブル（血統カラム追加）

| カラム名    | 型                 | 説明     |
| ----------- | ------------------ | -------- |
| sex         | string (nullable)  | 性別     |
| coat_color  | string (nullable)  | 毛色     |
| birth_year  | integer (nullable) | 生年     |
| sire        | string (nullable)  | 父       |
| dam         | string (nullable)  | 母       |
| sire_of_dam | string (nullable)  | 母父     |
| sire_sire   | string (nullable)  | 父の父   |
| dam_dam     | string (nullable)  | 母の母   |
| trainer_id  | string (nullable)  | 調教師ID |
| owner_id    | string (nullable)  | 馬主ID   |
| breeder_id  | string (nullable)  | 生産者ID |

## 出馬表スクレイピング・抽出機能

### ShutubaScraperService クラス

[app/Domains/ShutubaScraperService.php](app/Domains/ShutubaScraperService.php)

出馬表HTMLをダウンロードするサービスです。

主な機能:

- `scrapeAll()` - 全ての出馬表HTMLをスクレイピング
- `scrapeYear(string $year)` - 特定年の出馬表をスクレイピング
- `scrapeYearMonth(string $year, string $month)` - 特定年月の出馬表をスクレイピング

動作仕様:

1. `storage/app/url/race/YYYY/MM/race_url.txt` からURLを読み込み
2. `https://race.netkeiba.com/race/shutuba.html?race_id={race_id}` からHTMLを取得
3. `storage/app/html/shutuba_html/YYYY/MM/DD/競馬場名/{race_id}.html` に保存
4. 既存ファイルはスキップ
5. ダウンロード間隔は3秒

### ShutubaExtractorService クラス

[app/Domains/ShutubaExtractorService.php](app/Domains/ShutubaExtractorService.php)

出馬表HTMLからデータを抽出してDBに保存するサービスです。

主な機能:

- `extract()` - 全ての出馬表HTMLからデータを抽出
- `extract(string $year)` - 特定年の出馬表HTMLからデータを抽出
- `extract(string $year, string $month)` - 特定年月の出馬表HTMLからデータを抽出
- `extractFromHtml(string $filePath)` - 単一のHTMLファイルからデータを抽出

抽出されるデータ:

- 枠番、馬番、馬名、horse_id
- 性別、年齢、斤量
- 騎手名、jockey_id
- 調教師名、trainer_id
- 馬体重、オッズ、人気

### Shutuba モデル

[app/Models/Shutuba.php](app/Models/Shutuba.php)

出馬表データを保存するモデル

- race_info との多対1リレーション

### shutubas テーブル

| カラム名      | 型                 | 説明                            |
| ------------- | ------------------ | ------------------------------- |
| id            | bigint             | 主キー (自動採番)               |
| race_info_id  | bigint             | race_infos テーブルへの外部キー |
| frame_number  | integer            | 枠番                            |
| horse_number  | integer            | 馬番                            |
| horse_name    | string             | 馬名                            |
| horse_id      | string (nullable)  | 馬ID                            |
| sex           | string (nullable)  | 性別                            |
| age           | integer (nullable) | 年齢                            |
| jockey_weight | string (nullable)  | 斤量                            |
| jockey_name   | string (nullable)  | 騎手名                          |
| jockey_id     | string (nullable)  | 騎手ID                          |
| trainer_name  | string (nullable)  | 調教師名                        |
| trainer_id    | string (nullable)  | 調教師ID                        |
| horse_weight  | string (nullable)  | 馬体重                          |
| odds          | string (nullable)  | オッズ                          |
| pop           | string (nullable)  | 人気                            |
| created_at    | timestamp          | 作成日時                        |
| updated_at    | timestamp          | 更新日時                        |

**インデックス:**

- `race_info_id` - レース情報との結合用
- `horse_id` - 馬検索用
- `jockey_id` - 騎手検索用

### keiba:shutuba-scrape コマンド

[app/Console/Commands/KeibaShutubaScraperCommand.php](app/Console/Commands/KeibaShutubaScraperCommand.php)

#### 全ての出馬表をスクレイピング

```bash
docker-compose exec app php artisan keiba:shutuba-scrape scrapehtml
```

#### 特定年の出馬表をスクレイピング

```bash
docker-compose exec app php artisan keiba:shutuba-scrape scrapehtml:2024
```

#### 特定年月の出馬表をスクレイピング

```bash
docker-compose exec app php artisan keiba:shutuba-scrape scrapehtml:2024:01
```

### keiba:shutuba-extract コマンド

[app/Console/Commands/KeibaShutubaExtractCommand.php](app/Console/Commands/KeibaShutubaExtractCommand.php)

#### 全ての出馬表データを抽出

```bash
docker-compose exec app php artisan keiba:shutuba-extract
```

#### 特定年の出馬表データを抽出

```bash
docker-compose exec app php artisan keiba:shutuba-extract extract:2022
```

#### 特定年月の出馬表データを抽出

```bash
docker-compose exec app php artisan keiba:shutuba-extract extract:2022:03
```

## 実装していない機能 (今後の拡張)

以下の機能はまだ実装されていません。必要に応じて追加実装してください。

### DAO/Model 系

- `Jockey` (騎手)
- `Trainer` (調教師)
- `Owner` (馬主)
- `Breeder` (生産者)

### その他の機能

- ログイン機能 (現在は簡易的な HTTP リクエストのみ)
- Selenium/Panthter 等のブラウザ自動化 (JavaScript レンダリングが必要な場合)

## 注意事項

1. **スクレイピングのマナー**
    - サーバーに負荷をかけないよう、リクエスト間隔を 3 秒に設定しています
    - 過度なスクレイピングは避けてください

2. **認証情報**
    - `.env`ファイルの認証情報は絶対に Git にコミットしないでください
    - `.env.example`にサンプルのみ記載してください

3. **ログイン機能**
    - 現在の実装は簡易的な HTTP リクエストのみです
    - JavaScript が必要なページの場合、Selenium/Panthter などの導入が必要です

4. **ストレージ**
    - レースHTML ファイルは `storage/app/html/race_html/` に保存されます
    - 出馬表HTML ファイルは `storage/app/html/shutuba_html/` に保存されます
    - 馬情報HTML ファイルは `storage/app/html/horse_html/` に保存されます
    - URL ファイルは `storage/app/url/race/` に配置してください

## テスト

```bash
docker-compose exec app php artisan test
```

## ライセンス

このプロジェクトのライセンスについては、元のプロジェクトのライセンスに従ってください。
