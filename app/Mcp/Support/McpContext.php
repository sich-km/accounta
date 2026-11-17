<?php

namespace App\Mcp\Support;

use App\Models\Organization;
use App\Models\User;
use Laravel\Mcp\Request;

class McpContext
{
    public function organization(Request $request): ?Organization
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        $organization = $user->organization;

        if (! $organization instanceof Organization) {
            return null;
        }

        $organization->loadMissing('company');

        return $organization->company === null ? null : $organization;
    }
}
