---
name: create-issue
description: Accountaの開発依頼を、現在のバージョンブランチに基づくGitHub Issueとして登録する。Cursorで「/create-issue 対応内容」やIssue作成を依頼されたときに使う。
---

# Accounta: Create GitHub Issue (Cursor)

実行前に、共通手順である [create-issue](../../../.agents/skills/create-issue/SKILL.md) を全文読み、その手順に従う。

- 共通手順内の「Codex」は現在のCursor Agent、「`$create-issue`」は「`/create-issue`」として扱う。他スキルの呼び出しも同様に `$` を `/` に読み替える。
- 操作対象はCursorで開いている実際のリポジトリ・作業ツリーとする。GitHub CLIの認証・外部書き込みはCursorの権限設定に従う。
- タイトルは `[vX.Y] 対応内容` とし、重複確認後に `sich-km/accounta` へ登録する。バージョン不明時は共通手順に従って確認する。
- ブランチ切替、コード変更、コミットは行わない。
- 先に `/create-branch v0.5` でバージョンを開始し、現在のバージョンへやることを随時追加する。登録後に実装を自動開始しない。バージョンだけの入力では、Issue内容を尋ねる。

例: `/create-issue ダッシュボードのB/SをChart.jsで表示する`
