---
name: create-branch
description: Accountaのバージョン別開発ブランチを最新mainから作成・切替する。Cursorで「/create-branch feature-v0.5」やバージョンブランチ作成を依頼されたときに使う。
---

# Accounta: Create Version Branch (Cursor)

実行前に、共通手順である [create-branch](../../../.agents/skills/create-branch/SKILL.md) を全文読み、その手順に従う。

- 共通手順内の「Codex」は現在のCursor Agent、「`$create-branch`」は「`/create-branch`」として扱う。他スキルの呼び出しも同様に `$` を `/` に読み替える。
- 操作対象はCursorで開いている実際のリポジトリ・作業ツリーとする。
- `AGENTS.md` と `docs/Accounta_開発プロセス.md` を確認する。未コミット変更は保持し、共通手順の停止条件を守る。

例: `/create-branch feature-v0.5`
