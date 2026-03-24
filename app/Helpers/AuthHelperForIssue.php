<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use App\Models\Flat;

class AuthHelperForIssue
{
    /**
     * Get the flat associated with the current user's token.
     */
    public static function department()
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
        $department_id = $token->department_id ?? null;

        return $department_id ? $department_id : null;
    }
}
