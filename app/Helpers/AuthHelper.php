<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use App\Models\Flat;

class AuthHelper
{
    /**
     * Get the flat associated with the current user's token.
     */
    public static function flat()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        // Get current access token
        $token = $user->token(); // works with Passport

        if (!$token) {
            return null;
        }

        // If flat_id is stored in oauth_access_tokens
        $flatId = $token->flat_id ?? null;

        return $flatId ? Flat::find($flatId) : null;
    }
}
