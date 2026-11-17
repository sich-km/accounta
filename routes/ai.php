<?php

use App\Mcp\Servers\AccountaServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::middleware('throttle:mcp-oauth-register')->group(function (): void {
    Mcp::oauthRoutes();
});

Mcp::web('/mcp/accounta', AccountaServer::class)
    ->middleware(['auth:api', 'throttle:mcp']);
