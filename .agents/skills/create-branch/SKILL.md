---
name: create-branch
description: AccountaのバージョンブランチをGitHubの最新mainから作成し、ローカルへ取得して追跡・切替する。$create-branch v0.5 のようにバージョン開発を開始するときに使う。
---

# Accounta: Create Version Branch

ブランチ名は `v<major>.<minor>[.<patch>]`（例: `v0.5`、`v1.0.1`）とする。入力名をそのまま使い、接頭辞を追加しない。
**GitHubでmainから作成 → fetch → ローカルの同名ブランチへ切替**の順序を守る。
同じバージョンにIssueを随時追加し、このブランチからmainへPRを作る。Issue単位や別のリリース専用ブランチは作らない。

## 呼び出し

`$create-branch v0.5`

省略・形式不正ならバージョンを確認する。このスキルはIssue・PR・タグを作成せず、mainへマージしない。

## 事前確認

1. `AGENTS.md` と `docs/Accounta_開発プロセス.md` を読み、現在の作業ツリーで操作する。
2. `git rev-parse --show-toplevel`、`git remote get-url origin` で対象が `sich-km/accounta` であることを確認する。
3. `git status --short --untracked-files=all` を確認し、未コミット変更があれば停止して報告する。自動stash・commit・reset・cleanは行わない。
4. `git branch --show-current` と `git check-ref-format --branch <version>` で現在位置と名前を確認する。入力は上記のバージョン形式にも一致させ、シェルコードとして評価しない。
5. `gh --version`、`gh auth status`、`gh repo view -R sich-km/accounta --json nameWithOwner` でCLI・認証・対象を確認する。取得失敗・権限不足なら後続処理を停止する。
6. GitHubの `refs/heads/<version>` とローカル同名ブランチの有無を確認する。APIの404だけを「未作成」と扱い、認証・通信エラーを未作成扱いにしない。
7. 既知の前バージョンPRが未マージで、その変更への依存がある場合は停止して報告する。勝手に別ブランチを基点にしない。

## GitHubで作成してからローカルへ取得

### リモートにない場合

1. ローカルにだけ同名ブランチがあれば、勝手にpush・削除せず状態を報告して方針を確認する。
2. `gh api repos/sich-km/accounta/git/ref/heads/main --jq .object.sha` でGitHubの最新mainのSHAを取得する。ローカルmainやタグを基点にしない。
3. `gh api --method POST repos/sich-km/accounta/git/refs -f ref=refs/heads/<version> -f sha=<main-sha>` でGitHubに作成する。ユーザーのcreate-branch依頼はこのリモート作成を含む。
4. APIで作成結果のrefとSHAを確認する。応答が不明な場合はリモートの有無を再確認し、既存ブランチを上書きしない。

### リモートに既存の場合

既存ブランチを再利用する。mainへリセットしたり、履歴を書き換えたりしない。

### 取得・切替

1. `git fetch origin refs/heads/<version>:refs/remotes/origin/<version>` で取得する。失敗したら停止する。
2. ローカルに未作成なら `git switch --track origin/<version>` で同名ブランチを作成・切替する。ローカル先行の `git switch -c` は使わない。
3. ローカルに既存なら `git switch <version>` し、upstreamを確認する。未設定なら `git branch --set-upstream-to=origin/<version> <version>` を設定する。異なるupstreamなら報告して停止する。
4. 既存ローカルがリモートより遅れているだけなら `git merge --ff-only origin/<version>` で揃える。ローカル先行・分岐の場合は自動push・rebase・resetをせず差分を報告する。

各変更操作の成功を確認してから次へ進む。途中で失敗しても、作成済みリモートブランチを自動削除しない。

## 結果確認

- `git branch --show-current`、`git status -sb`、`git log -1 --oneline`、upstreamとリモートrefを確認する。
- 新規の場合はGitHub作成時のmainのSHAと、リモート・ローカルのバージョンブランチのSHAが一致することを確認する。
- GitHubでの新規作成／再利用、現在のブランチ、基点、追跡先、作業ツリー状態を報告する。別worktreeを変更したと主張しない。
- 続けて `$create-issue <やること>` で対象バージョンにIssueを追加できることを案内する。
