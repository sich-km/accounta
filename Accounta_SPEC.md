# Accounta 開発仕様書 / Cursor 実装指示書

> Project: **Accounta（アカウンタ）**
>
> このファイルは、Accounta の仕様書であると同時に、Cursor 等のAIコーディングエージェントへ読み込ませる実装指示書として使用する。
>
> **最重要ルール:**  
> 本ファイルの「Part 1: v0.1 実装仕様」に記載された内容だけを今回実装すること。  
> 「Part 2: 将来構想」は将来の設計方針を共有するための参考情報であり、**今回の実装対象ではない**。  
> 明示的な追加指示がない限り、将来構想に記載されたテーブル・画面・機能・APIを先回りして実装してはならない。

---

# 0. Accounta の目的

Accounta は、最終的には以下の2つの用途を持つシンプルな会計アプリを目指す。

1. 個人事業主・マイクロ法人・小規模組織が利用できる会計アプリ
2. 日商簿記や税理士試験の学習者が、実際の会計データを操作して実務感覚を身につけられるアプリ

将来的には、以下の流れを一貫して扱えるようにする。

```text
取引
 ↓
仕訳
 ↓
総勘定元帳
 ↓
試算表
 ↓
P/L・B/S・C/F
 ↓
予実管理・経営管理
```

ただし、**v0.1では上記の会計機能全体は作らない。**

v0.1の目的は次の2点に限定する。

```text
1. 予算・実績の明細データを入力してDBに保存する
2. 保存したデータをSQL学習に利用できるようにする
```

保存したデータとマスタのExcel出力はv0.2で実装する。v0.1ではDBクライアントからデータを参照してSQLを学習する。

---

# Part 1: v0.1 実装仕様

> **ここからが今回の実装対象。**
>
> Cursor は、このPart 1を実装すること。
>
> Part 2の将来構想は実装しないこと。
>
> ただし「11. Excel Export」は確定済みのv0.2仕様であり、v0.1では実装しない。

---

# 1. v0.1 技術構成

## 1.1 基本構成

以下を基本とする。

- Laravel 13
- PHP 8.3以上
- MySQL 8.4 LTS
- Composer
- Node.js / npm
- Blade
- Tailwind CSS
- Laravel Jetstream
- Jetstream Livewire Stack

SPAは作らない。

Vue / React / Inertia は使用しない。

Jetstream は **Livewire Stack** を使用する。

## 1.2 実行環境・サポート範囲

- v0.1の正式サポートDBと完了判定環境は **MySQL 8.4 LTS** とする。
- 当面のローカル開発ではPostgreSQLを使用してよい。ただし、DB固有のSQL・型・関数へ依存せず、最終的にMySQL 8.4でMigrationとFeature Testが通ることを必須とする。
- v0.1はローカルまたはアクセス制限された非公開環境での利用を前提とし、一般公開運用は対象外とする。
- アプリケーションのTimezoneは `Asia/Tokyo`、UI Localeは日本語とする。

SQL学習目的でDBへ直接接続できるのは、DB全体を閲覧できることを理解した信頼済みの開発者・所有者に限る。
LaravelのOrganization分離はWebアプリケーション経由のアクセスに対する境界であり、DB認証情報を持つ利用者に対する分離を保証しない。共有環境ではアプリ利用者へDB認証情報を公開しない。

---

# 2. 新規プロジェクト作成

新規Laravel 13プロジェクトとして作成する。

Repository / Project name:

```text
accounta
```

基本的なセットアップ例:

```bash
composer create-project laravel/laravel accounta
cd accounta

composer require laravel/jetstream
php artisan jetstream:install livewire

npm install
npm run build
```

DB接続情報を `.env` に設定後、Migrationを実行する。

```bash
php artisan migrate
```

## 2.1 Package Version方針

`composer.json` にパッケージバージョンを手作業で固定するのではなく、原則として `composer require` に互換バージョンを解決させる。

2026年9月時点の前提:

- Laravel Jetstream 5.x は Laravel 13 に対応

---

# 3. v0.1 認証仕様

## 3.1 方針

Laravel Jetstreamを使用するが、標準のメールアドレスログインをそのまま使用しない。

v0.1では、

```text
ユーザーID + パスワード
```

でログインする。

画面上の表記は **「ユーザーID」** とする。

DB上のカラム名は、Laravelの外部キー `user_id` と混同しないよう、

```text
login_id
```

とする。

## 3.2 v0.1で有効にするもの

- ユーザー登録
- ユーザーID + パスワードによるログイン
- ログアウト
- Jetstream / Fortify標準のRemember Meによるログイン状態保持
- プロフィールの表示名変更
- パスワード変更
- Jetstream標準のセッション管理

`login_id` は登録後に変更できない。プロフィールで変更できるのは表示名 `name` のみとする。

## 3.3 v0.1で無効にするもの

以下はv0.1では使用しない。

- メールアドレスによるログイン
- メールアドレス認証
- メールによるパスワードリセット
- Two-Factor Authentication
- Passkeys
- API Token
- Jetstream Teams
- Terms / Privacy同意
- Profile Photo
- アカウント削除

特に、**メールアドレス認証は絶対に有効化しないこと。**

一般公開リリース時に改めて実装する。

## 3.4 Fortify / Jetstream カスタマイズ

Fortifyの認証識別子を `email` から `login_id` に変更する。

想定:

```php
'username' => 'login_id',
'lowercase_usernames' => true,
```

登録・ログインのValidationは `login_id` を基準に変更する。プロフィール更新では `login_id` を変更しない。

Jetstream / Fortifyが標準で要求するemail Validationが残らないようにする。

Fortifyで有効にするFeatureは以下だけとする。

```text
Features::registration()
Features::updateProfileInformation()
Features::updatePasswords()
```

Email Verification、Password Reset、Two-Factor Authentication、Passkeysは無効とし、対応する画面・Routeも提供しない。Web画面は `web` GuardのSession認証を使用し、API RouteやToken発行機能は提供しない。

## 3.5 users テーブル

最低限以下を持つ。

| Column | Type / Rule | Description |
|---|---|---|
| id | bigint PK | 内部PK |
| organization_id | FK, not null | 所属Organization |
| login_id | varchar(50), unique, not null | ログイン用ユーザーID |
| name | varchar(100), not null | 表示名 |
| email | varchar, nullable | 将来利用。v0.1ではUIから入力させない |
| email_verified_at | timestamp, nullable | v0.1では常に未使用 |
| password | varchar, not null | Hash済みPassword |
| remember_token | nullable | Laravel標準 |
| created_at | timestamp | Laravel標準 |
| updated_at | timestamp | Laravel標準 |

### Registration画面

v0.1の新規ユーザー登録では以下を入力する。

```text
ユーザーID
表示名
組織名
パスワード
パスワード確認
```

メールアドレスは入力させない。

登録処理はDB Transaction内で行う。

```text
Organization作成
        ↓
作成したOrganization IDを指定してUser作成
```

登録時の入力規則:

- `login_id`: trim後3～50文字。ASCII英小文字・数字・ピリオド・アンダースコア・ハイフンのみ。先頭は英小文字または数字
- `login_id`: 保存・検索前に小文字へ正規化し、大文字小文字を区別せずUnique
- `name`: trim後1～100文字
- `organization_name`: trim後1～100文字。同名Organizationは許可
- `password`: Jetstream / FortifyのPassword Ruleを使用し、確認入力を必須とする

同じ `login_id` は登録できない。登録失敗時はOrganizationとUserの両方をRollbackし、OrganizationなしのUserやUserなしのOrganizationを残さない。リクエストから `organization_id`、Organizationの `type`、`fiscal_year_start_month` を受け取らない。

---

# 4. Organization

v0.1では、1ユーザーは1つのOrganizationに所属する。

複数Organization切替は実装しない。

同一Organizationへ複数ユーザーを所属させることはDB上可能な構成とするが、ユーザー招待・権限管理画面はv0.1では作らない。

## 4.1 organizations テーブル

| Column | Type / Rule | Description |
|---|---|---|
| id | bigint PK | Primary Key |
| name | varchar(100), not null | 組織名 |
| type | varchar(32), not null | 組織種別 |
| fiscal_year_start_month | tinyint unsigned, default 1 | 会計年度開始月 |
| created_at | timestamp | Laravel標準 |
| updated_at | timestamp | Laravel標準 |

type候補:

```text
company
sole_proprietor
learning
organization
```

v0.1では、登録時は `company` をDefaultとしてよい。

v0.1では登録時に `type = company`、`fiscal_year_start_month = 1` をサーバー側で設定する。

Organizationの一覧・編集・削除・切替画面は実装しない。

---

# 5. Department Master

予実データ入力に必要な部門マスタ。

## 5.1 departments テーブル

| Column | Type / Rule | Description |
|---|---|---|
| id | bigint PK | Primary Key |
| organization_id | FK, not null | Organization |
| code | varchar(32), not null | 部門コード |
| name | varchar(100), not null | 部門名 |
| is_active | boolean, default true | 使用可否 |
| created_at | timestamp | Laravel標準 |
| updated_at | timestamp | Laravel標準 |

同じOrganization内で `code` はUniqueとする。

`code` はtrim後に大文字へ正規化し、1～32文字のASCII英大文字・数字・アンダースコア・ハイフンのみ許可する。先頭は英大文字または数字とする。名称はtrim後1～100文字とする。

例:

```text
D001 営業部
D002 開発部
D003 管理部
```

## 5.2 部門マスタ画面

v0.1では以下を実装する。

- 一覧
- 新規登録
- 編集
- 有効 / 無効切替

原則としてHard Deleteは行わない。

予実データから参照済みの部門も `is_active = false` で履歴を保持する。

予実入力画面では、有効な部門だけを選択可能とする。

部門の削除機能と `deleted_at` は作らない。無効化と再有効化のみを提供する。Organization削除およびDepartment削除に対する外部キー動作は `RESTRICT` とする。

---

# 6. Account Master

予実データ入力に必要な勘定科目マスタ。

## 6.1 accounts テーブル

| Column | Type / Rule | Description |
|---|---|---|
| id | bigint PK | Primary Key |
| organization_id | FK, not null | Organization |
| code | varchar(32), not null | 勘定科目コード |
| name | varchar(100), not null | 勘定科目名 |
| account_type | varchar(16), not null | 勘定科目区分 |
| is_active | boolean, default true | 使用可否 |
| created_at | timestamp | Laravel標準 |
| updated_at | timestamp | Laravel標準 |

同じOrganization内で `code` はUniqueとする。

`code` はtrim後に大文字へ正規化し、1～32文字のASCII英大文字・数字・アンダースコア・ハイフンのみ許可する。先頭は英大文字または数字とする。名称はtrim後1～100文字とする。

account_type:

```text
asset
liability
equity
revenue
expense
```

この分類は将来のP/L・B/S作成を想定して保持するが、v0.1では財務諸表を作成しない。

例:

```text
A001 売上高         revenue
A002 人件費         expense
A003 外注費         expense
A004 クラウド利用料 expense
A005 旅費交通費     expense
```

## 6.2 勘定科目マスタ画面

v0.1では以下を実装する。

- 一覧
- 新規登録
- 編集
- 有効 / 無効切替

原則としてHard Deleteは行わない。

予実入力画面では、有効な勘定科目だけを選択可能とする。

勘定科目の削除機能と `deleted_at` は作らない。無効化と再有効化のみを提供する。Organization削除およびAccount削除に対する外部キー動作は `RESTRICT` とする。

---

# 7. 予算・実績データ

v0.1の中心機能。

## 7.1 monthly_amounts テーブル

| Column | Type / Rule | Description |
|---|---|---|
| id | bigint PK | Primary Key |
| organization_id | FK, not null | Organization |
| department_id | FK, not null | Department |
| account_id | FK, not null | Account |
| period | date, not null | 対象年月。DB上は月初日として保存 |
| type | varchar, not null | `budget` / `actual` |
| amount | decimal(15,2), not null | 金額 |
| memo | varchar(500), nullable | メモ |
| source | varchar(32), not null, default `manual` | 登録元 |
| created_at | timestamp | Laravel標準 |
| updated_at | timestamp | Laravel標準 |

### period

UIでは:

```text
YYYY-MM
```

で入力する。

DBには対象月の1日として保存する。

例:

```text
UI: 2026-04
DB: 2026-04-01
```

入力は厳密な `YYYY-MM` 形式とし、`1900-01`～`9999-12` の範囲だけを許可する。1桁月、日を含む値、不正な月は拒否する。DBでは必ず月初日であることを保証する。

### type

以下のみ許可する。

```text
budget
actual
```

### amount

0、正数、負数を許容する。

将来の調整仕訳や予算修正等を考慮し、負数を禁止しない。

整数部13桁、小数部2桁までを許可し、範囲は `-9,999,999,999,999.99`～`9,999,999,999,999.99` とする。小数3桁以上を丸めず拒否する。カンマ、通貨記号、指数表記を含む入力は拒否する。

### source

v0.1では原則:

```text
manual
```

のみ使用する。

Excel Import機能は存在しない。

`source` は画面やリクエストから受け取らず、サーバー側で常に `manual` を設定する。

### Tenant整合性と外部キー

- `monthly_amounts.organization_id` はリクエストから受け取らず、ログインユーザーから設定する。
- DepartmentとAccountは同じOrganizationに属するものだけを関連付けられるよう、ValidationとDB制約の両方で保証する。
- `departments` と `accounts` には、複合外部キーの参照先となる `UNIQUE (organization_id, id)` を設定する。
- DB制約では `(organization_id, department_id)` と `(organization_id, account_id)` の組み合わせを複合外部キーで保証する。
- Organization、Department、Accountの削除に対する外部キー動作は `RESTRICT` とする。
- MonthlyAmountの削除は物理削除とし、Soft Deleteは使用しない。

---

# 8. 予算・実績入力画面

## 8.1 入力項目

以下を入力する。

```text
対象年月
部門
勘定科目
区分（予算 / 実績）
金額
メモ
```

画面イメージ:

```text
Accounta
────────────────────────

対象年月
[ 2026-04 ]

部門
[ 開発部 ▼ ]

勘定科目
[ クラウド利用料 ▼ ]

区分
(●) 予算
( ) 実績

金額
[ 800000 ]

メモ
[ Azure利用料など ]

                      [ 登録 ]
```

## 8.2 Validation

- period: required
- department_id: required / current organization内 / active
- account_id: required / current organization内 / active
- type: required / `budget` or `actual`
- amount: required / 上記の桁数・形式・範囲を満たす10進数
- memo: nullable / max 500

ブラウザから他OrganizationのIDを直接送信しても登録できないこと。

新規登録および関連先を変更する更新では、有効なDepartment / Accountだけを選択できる。既存明細が無効化済みマスタを参照している場合は、現在値を「無効」と表示したうえで維持でき、金額・メモ等を更新できる。別マスタへ変更するときは変更先が有効でなければならない。

---

# 9. 予算・実績一覧画面

登録した明細を一覧表示する。

例:

| 年月 | 部門 | 勘定科目 | 区分 | 金額 | メモ | 操作 |
|---|---|---|---|---:|---|---|
| 2026/04 | 開発部 | 人件費 | 予算 | 5,000,000 |  | 編集 / 削除 |
| 2026/04 | 開発部 | 人件費 | 実績 | 5,200,000 |  | 編集 / 削除 |
| 2026/04 | 開発部 | クラウド利用料 | 予算 | 800,000 | Azure | 編集 / 削除 |

v0.1では以下を実装する。

- Create
- Read
- Update
- Delete
- Pagination

v0.1では検索・絞り込み・任意の並び替え・一覧上の集計は実装しない。1ページ20件固定とする。

並び順Default:

```text
period DESC
id DESC
```

削除時は確認を表示する。

削除は対象明細だけを物理削除する。独立した詳細表示画面は作らず、一覧から編集・削除を行う。

---

# 10. 明細データとして保存する

Accountaでは、予算・実績を集計値だけで保存しない。

同一の

```text
対象年月
部門
勘定科目
区分
```

について、複数明細を登録可能とする。

例:

```text
2026-04 開発部 外注費 budget 500000
2026-04 開発部 外注費 budget 300000
```

この場合、予算合計は800000となる。

DB上で明細を保持し、集計はSQLやExcelで行う。

これはv0.1の重要な設計方針である。

Unique Constraintで1レコードに制限してはならない。

---

# 11. Excel Export（v0.2 実装仕様）

> この章は次バージョンv0.2の確定仕様であり、現在のv0.1では実装しない。
>
> v0.1では `maatwebsite/excel` をインストールせず、Excel出力画面・Route・テストも作成しない。

## 11.1 方針

Accountaでは **Excel出力のみ** を実装する。

以下は実装しない。

```text
Excel Import
CSV Import
Excel内の自動集計
Excel Formula生成
Pivot Table自動生成
Power BI連携
Power BI API
```

Excelの勉強は、Accountaから出力したExcelをユーザーがExcelで直接加工して行う。

Power BIも、出力データをユーザーがPower BIへ手動で読み込んで学習する。

## 11.2 Package

Laravel Excel:

```text
maatwebsite/excel
```

を使用する。

## 11.3 Export Workbook

1つの `.xlsx` ファイルに以下の3 Sheetを出力する。

```text
Amounts
Departments
Accounts
```

Sheetは上記の順序で固定し、他のSheetを含めない。各Sheetの1行目をヘッダ、2行目以降をデータとし、タイトル行・空行・集計行・Table・Formula・Pivot Tableを追加しない。データが0件でも3 Sheetとヘッダ行は必ず出力する。

### Sheet: Amounts

**マスタ名称は意図的に含めず、コードを使ってXLOOKUP等を練習できるようにする。**

Columns:

```text
Period
Year
Month
DepartmentCode
AccountCode
Type
Amount
Memo
```

Cell Type / Format:

- `Period`: 対象月1日のExcel日付セル。表示形式は `yyyy-mm`
- `Year`, `Month`: 整数の数値セル
- `DepartmentCode`, `AccountCode`, `Type`, `Memo`: 文字列セル
- `Amount`: 数値セル。表示形式は `#,##0.00`
- `Memo` がnullの場合は空セル。日本語・改行・最大500文字を保持する

例:

| Period | Year | Month | DepartmentCode | AccountCode | Type | Amount | Memo |
|---|---:|---:|---|---|---|---:|---|
| 2026-04 | 2026 | 4 | D002 | A002 | budget | 5000000 |  |
| 2026-04 | 2026 | 4 | D002 | A002 | actual | 5200000 |  |

### Sheet: Departments

Columns:

```text
DepartmentCode
DepartmentName
IsActive
```

`DepartmentCode`, `DepartmentName` は文字列セル、`IsActive` はBooleanセルとする。

### Sheet: Accounts

Columns:

```text
AccountCode
AccountName
AccountType
IsActive
```

`AccountCode`, `AccountName`, `AccountType` は文字列セル、`IsActive` はBooleanセルとする。

## 11.4 Exportの目的

出力したWorkbookを使って、Accountaの外で手動で以下を学習する。

```text
SUMIFS
XLOOKUP
IF
IFERROR
Pivot Table
条件付き書式
Power Query
Power Pivot
Power BI
```

Accounta側はこれらの処理を実装しない。

## 11.5 Export対象

ログインユーザーが所属するOrganizationのデータだけを出力する。

他Organizationのデータが混ざってはならない。

- Amounts: 対象Organizationの全明細。期間・区分・マスタの有効状態による絞り込みは行わない
- Departments / Accounts: 対象Organizationの有効・無効を含む全マスタ
- 無効マスタを参照する過去明細もAmountsへ出力し、対応するマスタも各Master Sheetへ出力する

出力順:

```text
Amounts: period DESC, id DESC
Departments: code ASC, id ASC
Accounts: code ASC, id ASC
```

参照済みマスタのコード・名称・区分を変更した場合、過去明細の出力には最新のマスタ値を使用する。v0.2ではマスタ値の履歴Snapshotを持たない。

出力ファイル名例:

```text
accounta_20260904_135900.xlsx
```

ファイル名の日時はダウンロード開始時点の `Asia/Tokyo` とする。

## 11.6 Download / Security

- 認証必須の `GET /export/excel` で同期ダウンロードする
- HTTP 200、xlsxのContent-Type、attachmentのContent-Dispositionを返す
- キュー、進捗表示、出力履歴、サーバーへのファイル保存は実装しない
- Organization IDをリクエストから受け取らず、認証ユーザーから決定する
- コード・名称・区分・メモ等の文字列は明示的に文字列セルとして書き込み、`=`, `+`, `-`, `@` 等で始まってもFormulaとして評価させない
- Workbook内にFormula型セルを生成しない

---

# 12. SQL学習との関係

Accounta自体にSQL EditorやSQL実行画面は作らない。

信頼済みの開発者・所有者は対象DBに対応するクライアントからローカルまたは非公開環境のDBへ接続し、Accountaで作成したデータに対して手動でSQLを実行する。

SQL学習用のDB接続設定・SQL Editor・Organization別DBの作成はAccountaの機能として実装しない。共有環境でアプリ利用者へDB認証情報を渡さない。

学習対象例:

```sql
SELECT
WHERE
ORDER BY
SUM
COUNT
AVG
GROUP BY
JOIN
CASE
DISTINCT
```

例:

```sql
SELECT
    department_id,
    account_id,
    SUM(amount) AS total_amount
FROM monthly_amounts
WHERE type = 'actual'
GROUP BY
    department_id,
    account_id;
```

Excelでは、同じデータに対してSUMIFS・XLOOKUP・Pivot Table等を使って同等の分析を行う。

Accountaにはそのための分析UIを追加しない。

---

# 13. Multi-Tenant Data Isolation

v0.1でもOrganization単位のデータ分離は必須とする。

以下のテーブルは必ず `organization_id` でスコープする。

```text
departments
accounts
monthly_amounts
```

Controllerから単純に以下のような取得をしてはならない。

```php
MonthlyAmount::findOrFail($id);
```

必ずログインユーザーのOrganizationに限定する。

概念例:

```php
MonthlyAmount::query()
    ->where('organization_id', auth()->user()->organization_id)
    ->findOrFail($id);
```

Department / Accountについても同様。

Form RequestやPolicy等を利用し、他OrganizationのIDを改ざんして参照・更新・削除できないようにする。

他OrganizationのResource IDをURLへ指定した場合は、Resourceの存在を開示しないため404を返す。これは編集画面表示・更新・無効化・再有効化・削除に共通して適用する。

---

# 14. Eloquent Relationships

最低限以下を定義する。

```text
Organization
 ├─ hasMany Users
 ├─ hasMany Departments
 ├─ hasMany Accounts
 └─ hasMany MonthlyAmounts

User
 └─ belongsTo Organization

Department
 ├─ belongsTo Organization
 └─ hasMany MonthlyAmounts

Account
 ├─ belongsTo Organization
 └─ hasMany MonthlyAmounts

MonthlyAmount
 ├─ belongsTo Organization
 ├─ belongsTo Department
 └─ belongsTo Account
```

---

# 15. Controller / Request / Code Structure

Laravel標準の責務分離を優先する。

例:

```text
app/
├─ Exports/
│  ├─ AccountaWorkbookExport.php
│  ├─ AmountsSheetExport.php
│  ├─ DepartmentsSheetExport.php
│  └─ AccountsSheetExport.php
│
├─ Http/
│  ├─ Controllers/
│  │  ├─ DepartmentController.php
│  │  ├─ AccountController.php
│  │  ├─ MonthlyAmountController.php
│  │  └─ ExcelExportController.php
│  │
│  └─ Requests/
│     ├─ StoreDepartmentRequest.php
│     ├─ UpdateDepartmentRequest.php
│     ├─ StoreAccountRequest.php
│     ├─ UpdateAccountRequest.php
│     ├─ StoreMonthlyAmountRequest.php
│     └─ UpdateMonthlyAmountRequest.php
│
└─ Models/
   ├─ User.php
   ├─ Organization.php
   ├─ Department.php
   ├─ Account.php
   └─ MonthlyAmount.php
```

Business LogicをBladeへ直接記述しない。

Controllerに大量のValidationを直接書かず、可能な範囲でForm Requestを使用する。

---

# 16. Routes / Screen Structure

v0.1の主な画面:

```text
/login
/register

/dashboard

/departments
/departments/create
/departments/{department}/edit

/accounts
/accounts/create
/accounts/{account}/edit

/amounts
/amounts/create
/amounts/{amount}/edit
```

Route範囲:

- Organization: 登録時の作成とDashboardでの表示のみ
- Department / Account: index, create, store, edit, update, active/inactive切替。show, destroyは作らない
- MonthlyAmount: index, create, store, edit, update, destroy。独立したshowは作らない
- API Routeは作らない

Navigation例:

```text
Accounta
 ├─ Dashboard
 ├─ 予算・実績
 ├─ 部門マスタ
 └─ 勘定科目マスタ
```

---

# 17. Dashboard

v0.1のDashboardは簡素でよい。

最低限:

- Accountaタイトル
- ログインユーザー名
- Organization名
- 各機能へのリンク

グラフ、KPI、予実分析は作らない。

---

# 18. UI方針

v0.1では機能性を優先する。

- Jetstream / Tailwindの既存デザインを最大限利用する
- レスポンシブ対応
- 日本語UI
- 過度なアニメーション不要
- ダッシュボードのグラフ不要
- SPA化不要
- JavaScriptの独自実装は最小限

---

# 19. Seeder / Factory

開発・テスト用にSeeder / Factoryを作成してよい。

サンプル例:

Departments:

```text
D001 営業部
D002 開発部
D003 管理部
```

Accounts:

```text
A001 売上高         revenue
A002 人件費         expense
A003 外注費         expense
A004 クラウド利用料 expense
A005 旅費交通費     expense
```

ただし、本番環境向けの固定ユーザーID・固定パスワードをRepositoryへ埋め込まない。

---

# 20. Testing

少なくともFeature Testで以下を確認する。

## Authentication

- login_idで登録できる
- login_idがUnique
- login_id + passwordでログインできる
- emailを入力しなくても登録できる
- メール認証を要求されない
- メールアドレスではログインしない
- login_idを小文字へ正規化し、大文字小文字を区別せず重複拒否する
- login_idに許可されない文字・長さを拒否する
- login_idをプロフィールから変更できない
- Password Reset / Two-Factor Authentication / PasskeysのRouteが存在しない
- Account Delete機能が存在しない
- 登録失敗時にOrganizationとUserがRollbackされる

## Organization Isolation

- 他OrganizationのDepartmentを参照できない
- 他OrganizationのAccountを参照できない
- 他OrganizationのMonthlyAmountを参照できない
- 他OrganizationのMonthlyAmountを更新できない
- 他OrganizationのMonthlyAmountを削除できない

## Master

- Departmentを登録・編集・無効化できる
- Accountを登録・編集・無効化できる
- 同一Organization内のCode重複を拒否する
- Codeを大文字へ正規化する
- 無効化したMasterを再有効化できる
- 無効化したMasterを参照する既存明細が保持される

## Monthly Amount

- budgetを登録できる
- actualを登録できる
- 同一月・同一部門・同一科目・同一区分の複数明細を登録できる
- 編集できる
- 削除できる
- 他OrganizationのDepartment / Accountを指定できない
- periodを厳密なYYYY-MMで受け取り、月初日として保存する
- amountの桁数・小数桁・形式・範囲を検証する
- 無効Masterを新規明細へ割り当てられない
- 無効Masterを参照する既存明細の金額・メモを更新できる

---

# 21. v0.1 Definition of Done

以下がすべて満たされた時点でv0.1完了とする。

- [ ] Laravel 13で起動できる
- [ ] MySQL 8.4でMigrationとFeature Testが通る
- [ ] Jetstream Livewireが導入されている
- [ ] login_id + passwordで認証できる
- [ ] emailなしでユーザー登録できる
- [ ] メール認証がOFF
- [ ] Password ResetがOFF
- [ ] Two-Factor AuthenticationがOFF
- [ ] PasskeysがOFF
- [ ] Account DeleteがOFF
- [ ] Organizationがユーザー登録時に作成される
- [ ] Department Masterを登録・編集・無効化できる
- [ ] Account Masterを登録・編集・無効化できる
- [ ] Budget / Actual明細を登録できる
- [ ] Budget / Actual明細を一覧表示できる
- [ ] Budget / Actual明細を編集できる
- [ ] Budget / Actual明細を削除できる
- [ ] 同一条件の複数明細を登録できる
- [ ] Organization単位のデータ分離ができている
- [ ] Laravel Excelが未導入で、Excel Export機能が存在しない
- [ ] Excel Import機能が存在しない
- [ ] Power BI連携機能が存在しない
- [ ] Accounta内に予実分析Dashboardを作っていない
- [ ] Feature Testが通る

---

# 22. v0.1で実装してはいけないもの

以下は、Cursorが「便利そうだから」という理由で先回りして追加してはならない。

```text
仕訳入力
仕訳帳
総勘定元帳
試算表
P/L
B/S
C/F
自動仕訳
銀行明細Import
Excel Import
CSV Import
Power BI連携
分析Dashboard
予実グラフ
AI機能
税務申告
消費税申告
固定資産管理
複数Organization切替
Role / Permission
User Invitation
API
Mobile App
Passkeys
Account Delete
```

---

# Part 2: 将来構想

> **ここから先はv0.1の実装対象外。**
>
> CursorはこのPart 2を読み、将来の拡張性を壊さない範囲でv0.1を設計すること。
>
> ただし、以下の機能・テーブル・画面を今回作成してはならない。

---

# F1. Accounting Core

将来的にAccountaを本格的な会計アプリへ拡張する。

中心となる流れ:

```text
取引
 ↓
仕訳
 ↓
仕訳帳
 ↓
総勘定元帳
 ↓
試算表
 ↓
P/L / B/S / C/F
```

---

# F2. Journal Entry

将来追加予定:

```text
journal_entries
journal_entry_lines
```

概念例:

```text
2026-04-01

借方
クラウド利用料 100,000

貸方
普通預金       100,000
```

必須ルール:

```text
借方合計 = 貸方合計
```

---

# F3. General Ledger / Trial Balance

仕訳から以下を生成する。

```text
仕訳帳
 ↓
総勘定元帳
 ↓
残高試算表
```

将来的には、実績値を手入力するのではなく仕訳から生成する。

概念:

```text
journal_entry_lines
        ↓
    SQL集計
        ↓
      Actual
```

v0.1の `monthly_amounts.actual` は、初期学習・予実入力用途として存在する。

将来は仕訳ベースの実績集計との関係を再設計する。

---

# F4. Financial Statements

試算表を基に以下を生成する。

```text
P/L
B/S
C/F
```

勘定科目マスタへ将来追加を検討する属性:

```text
statement_type
display_group
display_order
```

---

# F5. Budget Management

将来的には予算管理を拡張する可能性がある。

候補:

```text
年度予算
月次予算
修正予算
Forecast
Actual
差異
差異率
差異理由
```

ただし、ExcelやPower BIの学習機能をAccounta内部に再現することが目的ではない。

---

# F6. Learning Mode

簿記学習者向けモード。

例:

```text
4月10日、備品300,000円を普通預金から購入した。
```

ユーザー入力:

```text
借方 [          ] [        円]
貸方 [          ] [        円]
```

回答例:

```text
備品      300,000
    / 普通預金 300,000
```

さらに将来的には財務諸表への影響を表示する。

```text
B/S
備品       +300,000
普通預金   -300,000

P/L
影響なし

C/F
投資CF     -300,000
```

対象候補:

- 日商簿記3級
- 日商簿記2級
- 日商簿記1級
- 税理士試験 簿記論
- 税理士試験 財務諸表論

---

# F7. Public Release Authentication

一般公開する段階で、認証を強化する。

候補:

- emailカラム必須化
- メールアドレス認証ON
- メールによるパスワードリセット
- 必要に応じてlogin_id / emailの双方でログイン
- Two-Factor Authentication
- Terms / Privacy
- Abuse Prevention
- Rate Limit強化

これらはv0.1では実装しない。

---

# F8. Multi User / Organization

将来、企業や部署で利用する場合に拡張する。

候補:

```text
Organization
 ├─ Owner
 ├─ Admin
 ├─ Accountant
 └─ Viewer
```

将来的には、

- User Invitation
- Role
- Permission
- Userが複数Organizationへ所属
- Organization切替
- Audit Log

等を検討する。

v0.1では `users.organization_id` によるシンプルな所属関係を使用する。

---

# F9. Future Accounting Functions

将来候補:

- 月次決算
- 年次決算
- 固定資産管理
- 減価償却
- 部門別損益
- プロジェクト別損益
- 前年比較
- KPI
- 銀行明細取込
- 自動仕訳
- AIによる勘定科目候補
- 監査ログ
- API

税務申告機能は法令対応・制度改正対応が必要となるため、会計機能とは別に慎重に判断する。

---

# F10. Accounta 将来全体像

```text
                         Accounta
                            │
           ┌────────────────┼────────────────┐
           │                │                │
       Accounting        Planning         Learning
           │                │                │
          仕訳              予算             問題
           │                │                │
          元帳              実績            仕訳練習
           │                │                │
         試算表             差異        財務諸表への影響
           │
      P/L / B/S / C/F
```

Excel / Power BIはAccounta内部の機能として再現せず、必要なデータをAccountaから出力して外部ツールで利用する方針を基本とする。

---

# Cursorへの最終指示

実装開始前に必ずこのファイル全体を読むこと。

その上で:

1. **Part 1だけを実装する**
2. Laravel標準の設計・命名規約を優先する
3. JetstreamはLivewire Stackを使う
4. ログインは `login_id + password`
5. email認証・password reset・2FAはOFF
6. データは必ずOrganizationで分離する
7. Excel Exportはv0.2まで作らない
8. Excel Importは作らない
9. Power BI機能は作らない
10. SQL Editorは作らない
11. 予実分析Dashboardは作らない
12. Part 2の機能は作らない
13. 実装完了後、Definition of Doneを1項目ずつ確認する
14. 不明点があっても、Part 2の機能を推測で先行実装しない

**v0.1は小さく、明確に完成させること。**
