---
name: create-branch
description: Accountaのバージョン別開発ブランチをGitHubの最新mainからリモートに作成し、ローカルへ取得・切替する。Cursorで「/create-branch v0.5」やバージョンブランチ作成を依頼されたときに使う。
---

# Accounta: Create Version Branch (Cursor)

実行前に、共通手順である [create-branch](../../../.agents/skills/create-branch/SKILL.md) を全文読み、その手順に従う。

- 共通手順内の「Codex」は現在のCursor Agent、「`$create-branch`」は「`/create-branch`」として扱う。他スキルの呼び出しも同様に `$` を `/` に読み替える。
- 操作対象はCursorで開いている実際のリポジトリ・作業ツリーとする。
- GitHubで `v0.5` を作成してからfetchし、ローカルの同名ブランチを `origin/v0.5` の追跡付きで作成・切替する。ローカル先行で作らない。
- `AGENTS.md` と `docs/Accounta_開発プロセス.md` を確認する。未コミット変更は保持し、共通手順の停止条件を守る。

最初に `/create-branch v0.5` で `v0.5` を開始し、その後 `/create-issue <やること>` でIssueを随時追加する。開始時点でIssueがなくてもよい。

例: `/create-branch v0.5`
