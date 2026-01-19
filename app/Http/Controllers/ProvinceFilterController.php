<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Province;

class ProvinceFilterController extends Controller
{
    /**
     * Update province filter for general users
     * Stores selected province IDs in session
     */
    public function updateFilter(Request $request)
    {
        $user = Auth::user();

        // Only allow for general users
        if ($user->role !== 'general') {
            return response()->json([
                'success' => false,
                'message' => 'Province filter is only available for general users.'
            ], 403);
        }

        $selectedProvinceIds = $request->input('province_ids', []);

        // Validate that all selected provinces are managed by this user
        $managedProvinces = $user->managedProvinces()->pluck('provinces.id')->toArray();

        // If "all" is selected or empty array, use all managed provinces
        if (empty($selectedProvinceIds) || in_array('all', $selectedProvinceIds)) {
            $selectedProvinceIds = $managedProvinces;
            $filterAll = true;
        } else {
            // Filter to only include managed provinces and convert to integers
            $selectedProvinceIds = array_map('intval', array_intersect($selectedProvinceIds, $managedProvinces));
            $filterAll = false;
        }

        // Store in session
        Session::put('province_filter_ids', $selectedProvinceIds);
        Session::put('province_filter_all', $filterAll);

        return response()->json([
            'success' => true,
            'message' => 'Province filter updated successfully.',
            'selected_count' => count($selectedProvinceIds),
            'total_count' => count($managedProvinces)
        ]);
    }

    /**
     * Get current province filter
     */
    public function getFilter()
    {
        $user = Auth::user();

        if ($user->role !== 'general') {
            return response()->json([
                'success' => false,
                'message' => 'Province filter is only available for general users.'
            ], 403);
        }

        $managedProvinces = $user->managedProvinces()->with('provincialUsers')->get();
        $selectedIds = Session::get('province_filter_ids', []);
        $filterAll = Session::get('province_filter_all', true);

        // If no filter set or "all" selected, use all managed provinces
        if (empty($selectedIds) || $filterAll) {
            $selectedIds = $managedProvinces->pluck('id')->toArray();
        }

        // Ensure selectedIds are integers for comparison
        $selectedIds = array_map('intval', $selectedIds);

        return response()->json([
            'success' => true,
            'managed_provinces' => $managedProvinces->map(function($province) use ($selectedIds) {
                return [
                    'id' => $province->id,
                    'name' => $province->name,
                    'selected' => in_array($province->id, $selectedIds)
                ];
            }),
            'selected_ids' => $selectedIds,
            'filter_all' => $filterAll || empty($selectedIds),
            'selected_count' => count($selectedIds),
            'total_count' => $managedProvinces->count()
        ]);
    }

    /**
     * Clear province filter (show all provinces)
     */
    public function clearFilter()
    {
        $user = Auth::user();

        if ($user->role !== 'general') {
            return response()->json([
                'success' => false,
                'message' => 'Province filter is only available for general users.'
            ], 403);
        }

        Session::forget('province_filter_ids');
        Session::put('province_filter_all', true);

        return response()->json([
            'success' => true,
            'message' => 'Province filter cleared. Showing all provinces.'
        ]);
    }
}
