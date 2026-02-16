<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Society;
use Illuminate\Database\Eloquent\Builder;

/**
 * Phase 5B1: Role-based society visibility for project and user forms.
 * Used to load dropdown options and to validate society_id.
 */
class SocietyVisibilityHelper
{
    /**
     * Get query builder for societies the user is allowed to assign (e.g. in project form).
     * - Provincial: province_id = user's province + global (null)
     * - General (any context for project): all active societies
     * - Coordinator: all active societies
     * - Executor/Applicant: user's province + global
     *
     * @return Builder<Society>
     */
    public static function queryForProjectForm(?User $user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return Society::whereRaw('1 = 0');
        }

        $query = Society::active()->orderBy('name');

        if (in_array($user->role, ['admin', 'coordinator', 'general'])) {
            return $query;
        }

        if ($user->role === 'provincial') {
            return $query->where(function (Builder $q) use ($user) {
                $q->where('province_id', $user->province_id)
                  ->orWhereNull('province_id');
            });
        }

        if (in_array($user->role, ['executor', 'applicant'])) {
            return $query->where(function (Builder $q) use ($user) {
                $q->where('province_id', $user->province_id)
                  ->orWhereNull('province_id');
            });
        }

        return $query;
    }

    /**
     * Get collection of societies for dropdown (project create/edit).
     */
    public static function getSocietiesForProjectForm(?User $user = null)
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
