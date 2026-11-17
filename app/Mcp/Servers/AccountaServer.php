<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetJournalEntriesTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Accounta')]
#[Version('0.4.0')]
#[Instructions('このサーバーは参照専用です。公開Toolは、接続Userが所属するOrganizationの仕訳帳を参照するものだけです。任意SQLやデータの登録・更新・削除はできません。証憑はメタデータだけを返し、ファイル本体、保存Path、Storage Disk名は返しません。')]
class AccountaServer extends Server
{
    protected array $tools = [
        GetJournalEntriesTool::class,
    ];
}
