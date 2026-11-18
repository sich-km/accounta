---
name: complete-branch
description: Codex workflow for Accounta version branches. Run `$complete-branch feature-v0.5` to review, test, stage and commit changes, push the version branch, and create or reuse a Pull Request targeting main. This skill ends after verifying the PR; it never merges the PR, switches to main, deletes a branch, or closes issues.
---

# Accounta: Complete Version Branch

For the Accounta repository, complete the current version branch by creating a PR **into `main`**. Do not use `develop`. This skill ends after PR creation and verification. Leave the current version branch checked out.

## Invocation

- `$complete-branch feature-v0.5`
- `$complete-branch feature-v1.0`
- `$complete-branch v0.5`

Accept one version `v<major>.<minor>[.<patch>]` or branch name `feature-v<major>.<minor>[.<patch>]`. Normalize `v0.5` to `feature-v0.5`, then require the normalized name to match the active branch exactly. Include an optional user-specified commit message or related issue numbers, if provided.

このスキルは、同じバージョンブランチでIssue追加・実装を重ね、利用者がリリースの区切りを指示したときに実行する。Issue件数から自動実行しない。このブランチをそのままmain向けPRのheadとし、別のリリース専用ブランチは作らない。

## 1. 操作前の確認

1. `git rev-parse --show-toplevel`、`git remote get-url origin` で `sich-km/accounta` と確認する。`git branch --show-current` が指定ブランチに**完全一致**しない場合は停止。勝手に切り替えない。
2. `git check-ref-format --branch <branch>` に加え、`feature-v<major>.<minor>[.<patch>]` 形式を検証する。`main`と`develop`では実行しない。
3. `git fetch origin main`、`gh --version`、`gh auth status`、`gh repo view --json nameWithOwner` を確認する。mainがない、GitHub CLIを使えない、対象リポジトリが違う場合は **commit/push前に停止**。
4. `git status --short --untracked-files=all`, `git diff --stat`, `git diff --cached --stat`, `git diff`, `git diff --cached`、必要なファイル内容を確認する。**ステージ対象を理解する**。
5. `.env`、秘密鍵・トークン・認証情報、会社の機密情報、明らかに無関係な変更や生成物があればステージせず停止し、ユーザーに確認する。未追跡も含め `git add -A` を無条件で行わない。
6. `git diff --check`, `git diff --cached --check` を確認する。
7. `AGENTS.md`, `docs/Accounta_開発プロセス.md`、テスト/CI設定を確認する。古い`develop`向けの規則が残る場合は、本スキルの`main`向け運用と矛盾することを明示する。

## 2. 実装・テスト・差分確認

1. `AGENTS.md` のテスト実行制限に従い、変更内容に適した検証を行う。自動テストは利用者の明示的な実行指示がある場合に実行する。指示がなく必要なテスト結果も確認できない場合は、テスト実行または省略の指示を求め、commit/push/PR作成前で停止する。失敗時も停止して結果を報告し、未実行を成功扱いにしない。
2. `git log --oneline origin/main..HEAD` と `git diff --stat origin/main...HEAD` でPRに含まれる変更を確認する。予期しないコミットやmainとの差分があれば停止し、勝手にrebase・mergeしない。
3. 今回のバージョンに関するIssueが複数あれば、PR本文に関連番号をまとめる。Issueが見つからない場合も勝手に作らない。
4. 対象バージョンのIssueと実際の差分を照合し、PRに含む対応と未対応Issueを区別する。全Issueの完了はPR作成の必須条件にしない。未対応IssueはPR本文へ残し、勝手にClose・別バージョンへ変更しない。

## 3. Stage・Commit・Push

1. 内容を確認した対象ファイルのみ `git add -- <paths...>` でstageする。ファイル削除も意図されたものだけ含める。全変更が安全と確認済みの場合のみ `git add -A -- .` を利用してよい。
2. `git diff --cached --name-status`, `git diff --cached --stat`, `git diff --cached --check` を再確認。想定外なら停止し、破棄・resetしない。
3. 変更があれば内容に合った `git commit -m <message>` でコミットする。コミットフックは無効化しない。未コミット差分がなく既存コミットがある場合は空コミットを作らない。
4. `origin/main...HEAD` にPR対象差分が無ければ停止する。コミット後の `git status --short --untracked-files=all` がクリーンでない場合は原因確認し、**stop without changing branches**。
5. `git push -u origin <branch>` で指定ブランチだけ通常pushする。force push、mainへの直接pushは禁止。

## 4. main向けPull Request

1. `gh pr list --head <branch> --state open --json number,url,baseRefName,title` 等で重複確認する。
2. すでに **base=`main`** のPRがあれば再利用し、そのURLを取得する。別baseを向くPRがあれば勝手に付け替えず停止する。
3. PRが無ければ `gh pr create --base main --head <branch> --title "[vX.Y] ..." --body-file <tempfile>` 等で作成する。本文に概要、主な変更、テスト結果、関連Issue（複数可）、未完了事項を記載する。意図しない自動クローズを避け、通常は `Refs #番号` とする。
4. `gh pr view <branch> --json number,url,baseRefName,headRefName,state` で実在確認する。
5. **PRは作成するが、マージしない**。Issueを閉じず、開発ブランチも削除しない。

## 5. Stop after verifying the PR

1. After `gh pr view` confirms the PR exists with base `main`, **stop**. Do not perform `git switch main`, `git pull` on main, `gh pr merge`, branch deletion, Issue closure, or release tagging.
2. Verify `git branch --show-current` still equals the input branch and `git status -sb` is clean. If a hook or another operation left changes, report them; do not stash, reset, discard, or commit again without checking.
3. If the PR was already open, report the existing PR URL; do not create a duplicate. If a PR or push action fails, explain exactly which steps completed, and do not claim success.
4. Remind the user that the PR needs review and merge separately. A later `$create-branch` of the next version must be based on `main` after the earlier PR has actually merged.

## Final report

- Branch name and current local branch.
- Commit hash (new or existing), remote push result.
- PR number/URL, head branch and base=`main`.
- Tests executed and outcomes; any checks not executed.
- Explicitly say: PR was **not merged**, local branch was **not switched to main**.

Perform actions only inside the repository checkout visible to Codex. Never imply a separate Windows checkout or other worktree was modified.
