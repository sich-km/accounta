---
name: complete-branch
description: Accountaのバージョンブランチを確認・コミット・pushし、main向けPRを作成する。Cursorで「/complete-branch feature-v0.5」などと依頼されたときに使う。PR確認後に終了し、マージは行わない。
---

# Accounta: Complete Version Branch (Cursor)

実行前に、共通手順である [complete-branch](../../../.agents/skills/complete-branch/SKILL.md) を全文読み、その手順に従う。

- 共通手順内の「Codex」は現在のCursor Agent、「`$complete-branch`」は「`/complete-branch`」として扱う。他スキルの呼び出しも同様に `$` を `/` に読み替える。
- 操作対象はCursorで開いている実際のリポジトリ・作業ツリーとする。GitHub CLIの認証・外部書き込みはCursorの権限設定に従う。
- `AGENTS.md` のテスト実行制限を優先する。テスト実行が未承認なら、共通手順で必要な検証について実行指示を求め、コミット・push・PR作成前で停止する。
- PRのbaseは `main`。PRの存在を確認したら終了し、現在のバージョンブランチを維持する。PRマージ、mainへの切替、ブランチ削除、IssueのCloseは行わない。

例: `/complete-branch feature-v0.5`
