<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Society;
use Illuminate\Database\Eloquent\Builder;

/**
 * Province-based society visibility for project and user forms.
 * Users see only societies in their own province (admin/general with no province_id see all).
 */
class SocietyVisibilityHelper
{
    /**
     * Get query builder for societies the user is allowed to assign (e.g. in project form).
     * Province isolation: if user has province_id, only societies in that province; else all active (admin/general).
     *
     * @return Builder<Society>
     */
    public static function queryForProjectForm(?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return Society::whereRaw('1 = 0');
        }

        $query = Society::active()->orderBy('name');

        if ($user->province_id === null) {
            return $query;
        }

        return $query->where('province_id', $user->province_id);
    }

    /**
     * Get collection of societies for dropdown (project create/edit).
     */
    public static function getSocietiesForProjectForm(?User $user = null)
    {
        return self::queryForProjectForm($user)->get();
    }

    /**
     * Get societies in the user's province (for project form dropdown).
     * Same as getSocietiesForProjectForm; alias for clarity.
     */
    public static function getAllowedSocieties(?User $user = null)
    {
        return self::queryForProjectForm($user)->get();
    }

    /**
     * Get allowed society IDs for validation (same scope as queryForProjectForm).
     */
    public static function getAllowedSocietyIds(?User $user = null): array
    {
        return self::queryForProjectForm($user)->pluck('id')->toArray();
    }
}
