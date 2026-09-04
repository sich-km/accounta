<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        $input['name'] = Str::of($input['name'] ?? '')->trim()->toString();

        Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
        ])->validateWithBag('updateProfileInformation');

        $user->update([
            'name' => $input['name'],
        ]);
    }
}
