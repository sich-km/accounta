<?php

namespace App\Http\Requests;

use App\Models\FixedAsset;

class StoreFixedAssetRequest extends FixedAssetRequest
{
    protected function fixedAsset(): ?FixedAsset
    {
        return null;
    }
}
