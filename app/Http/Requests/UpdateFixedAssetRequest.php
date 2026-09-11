<?php

namespace App\Http\Requests;

use App\Models\FixedAsset;

class UpdateFixedAssetRequest extends FixedAssetRequest
{
    protected function fixedAsset(): ?FixedAsset
    {
        return FixedAsset::query()
            ->forOrganization($this->user()->organization_id)
            ->find($this->route('fixed_asset'));
    }
}
