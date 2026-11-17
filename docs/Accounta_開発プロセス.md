# Accounta 開発プロセス

## 1. 基本方針

Accountaは個人開発・技術検証を前提とし、管理負担を最小限にする。構想は`docs/Accounta_サービス構想.md`および`docs/Accounta_機能構想.md`に、実装タスクはGitHub Issuesに管理する。実装済みの状態はコードとテストで確認し、機能一覧をMarkdownで二重管理しない。

## 2. Gitブランチ

`develop`は今後の開発フローで**使用しない**。GitHubの既存`develop`ブランチは、必要に応じて移行後に削除を判断し、Skillで勝手に削除しない。

| ブランチ | 用途 |
|---|---|
| `main` | 安定版・統合済みコード。直接コミット・pushしない。 |
| `feature-v0.5`、`feature-v0.6`など | **バージョン単位**の開発。複数Issue・改修を同じブランチで進める。 |

次のバージョンを開始する際は、原則として**前バージョンのPRがmainにマージ済み**であることを確認する。そうしないと新しいブランチへ前バージョンの変更が引き継がれない。

## 3. GitHub Issues

Issueは機能追加・改善・不具合などを必要時に登録する。**Issueごとの専用ブランチは作成しない**。

- タイトル: `[v0.5] ダッシュボードのB/SをChart.jsで表示する` のように、対象バージョンと対応内容を記載する。
- 本文: 目的、対応内容、完了条件。コードから判定できない要件や設計判断の理由はIssue/PRに残す。
- 現在のバージョンが未決定なら、Issue登録時に明示する。GitHub Milestonesは必要になったときだけ使う。
- Issueは個別に作成・更新できるが、コードをmainへ統合する単位はバージョン別PRとする。

## 4. Codexの基本操作

```text
$create-branch feature-v0.5
$create-issue ダッシュボードのB/SをChart.jsで表示する
（Codexに機能実装や修正を依頼し、必要に応じてコミットする）
$create-issue 固定資産一覧を改善する
（同じfeature-v0.5ブランチで続けて開発）
$complete-branch feature-v0.5
```

- `$create-branch` は作業状態を確認し、**最新mainを基点**としてバージョンブランチを作成・切替する。
- `$create-issue` は現在の`feature-vX.Y`から対象バージョンを判定してIssueを作る。ブランチ変更はしない。
- `$complete-branch` checks changes and tests, stages and commits approved files, pushes the version branch, and creates a PR targeting `main`. It stops after PR verification and leaves the local version branch checked out.
- `$complete-branch` does not merge the PR, close issues, or switch branches.
- PRを確認・マージした後、関連IssueをClosedにし、次バージョンを開始する。ブランチ削除は必要に応じて手動で行う。

## 5. リリース・品質

mainへの統合前に、適切なテストとPR差分確認を行う。会計処理の貸借一致・金額精度・集計整合性、会社・組織データの分離、認可の確認を重視する。正式なリリースを識別する場合はmainの対象コミットにGitタグ（例:`v0.5.0`）を付ける。タグ作成はSkillで自動実行しない。

Git操作前には未コミット変更・秘密情報・対象リポジトリを確認し、force push、無断のreset、作業変更の破棄、PRの無断マージをしない。
