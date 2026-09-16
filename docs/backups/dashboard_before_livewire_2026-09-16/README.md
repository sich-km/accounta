# ダッシュボード Livewire 化前バックアップ

2026-09-16 時点で、ダッシュボードの各セクションを Livewire コンポーネントへ分割する直前のファイルを保存しています。

保存対象:

- `dashboard.blade.php`
- `web.php`
- `ProfitAndLossSummaryService.php`
- `DashboardTest.php`

元に戻す場合は、各ファイルを次の場所へ上書きしてください。

| バックアップ | 復元先 |
|---|---|
| `dashboard.blade.php` | `resources/views/dashboard.blade.php` |
| `web.php` | `routes/web.php` |
| `ProfitAndLossSummaryService.php` | `app/Services/ProfitAndLossSummaryService.php` |
| `DashboardTest.php` | `tests/Feature/DashboardTest.php` |

このバックアップは、作業開始時点の未コミット変更を含む内容です。

完全に Livewire 化前へ戻す場合は、上記4ファイルを復元したうえで、今回追加した次のファイルも削除してください。

- `app/Livewire/Dashboard/ProfitAndLossSummary.php`
- `app/Livewire/Dashboard/BalanceSheetSummary.php`
- `app/Livewire/Dashboard/BudgetActualSummary.php`
- `app/Services/FiscalYearService.php`
- `resources/views/livewire/dashboard/profit-and-loss-summary.blade.php`
- `resources/views/livewire/dashboard/balance-sheet-summary.blade.php`
- `resources/views/livewire/dashboard/budget-actual-summary.blade.php`
