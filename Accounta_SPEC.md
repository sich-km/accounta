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

v0.1の目的は次の3点に限定する。

```text
1. 予算・実績の明細データを入力してDBに保存する
2. 保存したデータをSQL学習に利用できるようにする
3. 保存したデータとマスタをExcelへ出力する
```

Excel上での分析やPower BIでの分析はAccountaの外で手動で行う。

---

# Part 1: v0.1 実装仕様

> **ここからが今回の実装対象。**
>
> Cursor は、このPart 1を実装すること。
>
> Part 2の将来構想は実装しないこと。

---

# 1. v0.1 技術構成

## 1.1 基本構成

以下を基本とする。

- Laravel 13
- PHP 8.3以上
- MySQL
- Composer
- Node.js / npm
- Blade
- Tailwind CSS
- Laravel Jetstream
- Jetstream Livewire Stack
- Laravel Excel (`maatwebsite/excel`)

SPAは作らない。

Vue / React / Inertia は使用しない。

Jetstream は **Livewire Stack** を使用する。

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

composer require maatwebsite/excel

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
- Laravel Excel 4.x は Laravel 13 / PHP 8.3以上に対応

Laravel Excel利用に必要なPHP Extensionがローカル環境に存在することを確認する。

主な要件:

```text
php_zip
php_xml
php_gd
php_iconv
php_simplexml
php_xmlreader
php_zlib
```

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
- ログイン状態保持
- プロフィールの表示名変更
- パスワード変更
- Jetstream標準のセッション管理

## 3.3 v0.1で無効にするもの

以下はv0.1では使用しない。

- メールアドレスによるログイン
- メールアドレス認証
- メールによるパスワードリセット
- Two-Factor Authentication
- API Token
- Jetstream Teams
- Terms / Privacy同意
- Profile Photo

特に、**メールアドレス認証は絶対に有効化しないこと。**

一般公開リリース時に改めて実装する。

## 3.4 Fortify / Jetstream カスタマイズ

Fortifyの認証識別子を `email` から `login_id` に変更する。

想定:

```php
'username' => 'login_id',
```

登録・プロフィール更新・Validationも `login_id` を基準に変更する。

Jetstream / Fortifyが標準で要求するemail Validationが残らないようにする。

`Features::emailVerification()` は無効。

`Features::resetPasswords()` もv0.1では無効。

Two Factor Authenticationも無効。

## 3.5 users テーブル

最低限以下を持つ。

| Column | Type / Rule | Description |
|---|---|---|
| id | bigint PK | 内部PK |
| organization_id | FK, nullable during creation if needed | 所属Organization |
| login_id | varchar, unique, not null | ログイン用ユーザーID |
| name | varchar, not null | 表示名 |
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
User作成
        ↓
users.organization_idへ設定
```

同じ `login_id` は登録できない。

---

# 4. Organization

v0.1では、1ユーザーは1つのOrganizationに所属する。

複数Organization切替は実装しない。

同一Organizationへ複数ユーザーを所属させることはDB上可能な構成とするが、ユーザー招待・権限管理画面はv0.1では作らない。

## 4.1 organizations テーブル

| Column | Type / Rule | Description |
|---|---|---|
| id | bigint PK | Primary Key |
| name | varchar, not null | 組織名 |
| type | varchar, not null | 組織種別 |
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

Organization編集画面はv0.1では必須ではない。

---

# 5. Department Master

予実データ入力に必要な部門マスタ。

## 5.1 departments テーブル

| Column | Type / Rule | Description |
|---|---|---|
| id | bigint PK | Primary Key |
| organization_id | FK, not null | Organization |
| code | varchar, not null | 部門コード |
| name | varchar, not null | 部門名 |
| is_active | boolean, default true | 使用可否 |
| created_at | timestamp | Laravel標準 |
| updated_at | timestamp | Laravel標準 |

同じOrganization内で `code` はUniqueとする。

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

---

# 6. Account Master

予実データ入力に必要な勘定科目マスタ。

## 6.1 accounts テーブル

| Column | Type / Rule | Description |
|---|---|---|
| id | bigint PK | Primary Key |
| organization_id | FK, not null | Organization |
| code | varchar, not null | 勘定科目コード |
| name | varchar, not null | 勘定科目名 |
| account_type | varchar, not null | 勘定科目区分 |
| is_active | boolean, default true | 使用可否 |
| created_at | timestamp | Laravel標準 |
| updated_at | timestamp | Laravel標準 |

同じOrganization内で `code` はUniqueとする。

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
| amount | decimal(18,2), not null | 金額 |
| memo | varchar(500), nullable | メモ |
| source | varchar, default `manual` | 登録元 |
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

### type

以下のみ許可する。

```text
budget
actual
```

### amount

0、正数、負数を許容する。

将来の調整仕訳や予算修正等を考慮し、負数を禁止しない。

### source

v0.1では原則:

```text
manual
```

のみ使用する。

Excel Import機能は存在しない。

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
- amount: required / numeric
- memo: nullable / max 500

ブラウザから他OrganizationのIDを直接送信しても登録できないこと。

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

並び順Default:

```text
period DESC
id DESC
```

削除時は確認を表示する。

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

# 11. Excel Export

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

### Sheet: Accounts

Columns:

```text
AccountCode
AccountName
AccountType
IsActive
```

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

出力ファイル名例:

```text
accounta_20260904_135900.xlsx
```

---

# 12. SQL学習との関係

Accounta自体にSQL EditorやSQL実行画面は作らない。

ユーザーはMySQLクライアント等からDBへ接続し、Accountaで作成したデータに対して手動でSQLを実行する。

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

/export/excel
```

Navigation例:

```text
Accounta
 ├─ Dashboard
 ├─ 予算・実績
 ├─ 部門マスタ
 ├─ 勘定科目マスタ
 └─ Excel出力
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

## Organization Isolation

- 他OrganizationのDepartmentを参照できない
- 他OrganizationのAccountを参照できない
- 他OrganizationのMonthlyAmountを参照できない
- 他OrganizationのMonthlyAmountを更新できない
- 他OrganizationのMonthlyAmountを削除できない
- Excel Exportに他Organizationのデータが含まれない

## Master

- Departmentを登録・編集・無効化できる
- Accountを登録・編集・無効化できる
- 同一Organization内のCode重複を拒否する

## Monthly Amount

- budgetを登録できる
- actualを登録できる
- 同一月・同一部門・同一科目・同一区分の複数明細を登録できる
- 編集できる
- 削除できる
- 他OrganizationのDepartment / Accountを指定できない

## Excel Export

- xlsxをダウンロードできる
- Amounts / Departments / Accounts の3 Sheetを出力する
- Amountsに明細データが含まれる
- Amountsに勘定科目名・部門名を混ぜない
- Master Sheetに名称が出力される

---

# 21. v0.1 Definition of Done

以下がすべて満たされた時点でv0.1完了とする。

- [ ] Laravel 13で起動できる
- [ ] Jetstream Livewireが導入されている
- [ ] login_id + passwordで認証できる
- [ ] emailなしでユーザー登録できる
- [ ] メール認証がOFF
- [ ] Password ResetがOFF
- [ ] Two-Factor AuthenticationがOFF
- [ ] Organizationがユーザー登録時に作成される
- [ ] Department Masterを登録・編集・無効化できる
- [ ] Account Masterを登録・編集・無効化できる
- [ ] Budget / Actual明細を登録できる
- [ ] Budget / Actual明細を一覧表示できる
- [ ] Budget / Actual明細を編集できる
- [ ] Budget / Actual明細を削除できる
- [ ] 同一条件の複数明細を登録できる
- [ ] Organization単位のデータ分離ができている
- [ ] Excel Workbookを出力できる
- [ ] ExcelにAmounts / Departments / Accountsの3 Sheetがある
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
7. ExcelはExportのみ
8. Excelは `Amounts / Departments / Accounts` の3 Sheet
9. Excel Importは作らない
10. Power BI機能は作らない
11. SQL Editorは作らない
12. 予実分析Dashboardは作らない
13. Part 2の機能は作らない
14. 実装完了後、Definition of Doneを1項目ずつ確認する
15. 不明点があっても、Part 2の機能を推測で先行実装しない

**v0.1は小さく、明確に完成させること。**
