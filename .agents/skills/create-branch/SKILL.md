---
name: create-branch
description: Accountaの次の開発バージョン用Gitブランチを準備するCodex用スキル。`$create-branch feature-v0.5`のように呼び出されたら、作業ツリーとリポジトリを確認し、最新のmainを基点にバージョン単位のブランチを作成・切替する。Issue単位ではブランチを作らず、同じバージョン内の複数Issueや自由な開発作業を一つのブランチで進める。
---

# Accounta: Create Version Branch

Accountaは **`main` + バージョン別開発ブランチ** で運用する。`develop` は使用しない。Git操作はCodexが現在開いている実際の作業ツリーに対して行う。

## 呼び出し

- `$create-branch feature-v0.5`
- `$create-branch feature-v0.6`

引数は `feature-v<major>.<minor>[.<patch>]` 形式のブランチ名を一つ受け取る。例: `feature-v0.5`、`feature-v1.0`、`feature-v1.0.1`。省略・不正な場合は確認する。Issue番号はブランチ名に含めない。

## 操作前の安全確認

1. `git rev-parse --show-toplevel` と `git remote get-url origin` を確認する。操作対象は `sich-km/accounta`。別リポジトリや不明なリモートなら停止する。
2. `git status --short --untracked-files=all` で未コミット変更・未追跡ファイルがないことを確認する。あれば停止し、**自動 stash / reset / clean / commit をしない**。
3. `git branch --show-current` と `git check-ref-format --branch <branch>` で状態と名前を確認する。ブランチ名は上記の形式にも合致させる。シェル引数は適切に引用し、ユーザー入力をコマンドとして評価しない。
4. `git fetch origin main` でリモートのmainを取得する。失敗したら停止する。`git branch --list <branch>` と `git ls-remote --heads origin <branch>` で既存か確認する。
5. `AGENTS.md` および `docs/Accounta_開発プロセス.md` があれば確認する。現行の本スキルとの矛盾があれば報告する。旧`develop`運用に自動的に戻さない。

## ブランチ作成・切替

- **すでに指定ブランチ上**: 作り直さず状態確認して終了。
- **ローカルに既存ブランチあり**: `git switch <branch>`。自動リセット・リベース・マージをしない。
- **originだけに既存ブランチあり**: `git fetch origin <branch>`、追跡設定付きで `git switch --track origin/<branch>`。履歴は書き換えない。
- **どちらにもない新規ブランチ**:
  1. `git switch main`。別worktreeで使われているなど失敗時は停止。
  2. `git status --short --untracked-files=all` を再確認。
  3. `git rev-list --count origin/main..main` が0であることを確認する。ローカルmainだけにコミットがある場合は停止し、勝手に変更しない。
  4. `git pull --ff-only origin main` で最新mainに更新する。fast-forwardできなければ停止。
  5. `git switch -c <branch>` で作成・切替。**pushはしない**。

## 作成時の留意事項

- 同じバージョン内の複数Issueをこのブランチで実装できる。Issue作成時は `$create-issue <内容>` を使い、現在のブランチ名からバージョンを付与する。
- **直前バージョンのPRが未マージなら、新しいブランチをmainから作るとその開発内容を引き継がない**。新規ブランチ作成前に既知の未マージPRがあれば確認し、連続リリースとして先行版に依存する場合は作成を止めて報告する。無断で未マージの開発ブランチを基点にしない。
- `git branch --show-current`, `git status -sb`, `git log -1 --oneline` で結果を検証し、ブランチ名・新規か既存か・基点・作業ツリー状態を報告する。
- main、他ブランチ、Issueを勝手に書き換えない。作業ツリーが別worktreeやクラウドなら、ユーザーのWindowsローカル環境が同時に切り替わると主張しない。
