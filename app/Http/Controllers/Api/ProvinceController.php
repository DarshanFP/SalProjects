<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for Province Management
 *
 * Provides API endpoints for province-related operations.
 */
class ProvinceController extends Controller
{
    /**
     * List all provinces.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Province::query();

            // Optionally filter by active status
            if ($request->has('active') && $request->boolean('active')) {
                $query->active();
            }

            // Optionally include relationships
            if ($request->has('include')) {
                $includes = explode(',', $request->get('include'));

                if (in_array('coordinator', $includes)) {
                    $query->with('coordinator:id,name,email');
                }

                if (in_array('centers', $includes)) {
                    $query->with('centers:id,province_id,name,is_active');
                }

                if (in_array('centers_count', $includes)) {
                    $query->withCount('centers');
                }
            }

            $provinces = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $provinces,
                'count' => $provinces->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch provinces',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get centers for a specific province.
     *
     * @param int $id Province ID
     * @param Request $request
     * @return JsonResponse
     */
    public function centers(int $id, Request $request): JsonResponse
    {
        try {
            $province = Province::findOrFail($id);

            $query = $province->centers();

            // Optionally filter by active status
            if ($request->has('active') && $request->boolean('active')) {
                $query->active();
            }

            $centers = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'province' => [
                    'id' => $province->id,
                    'name' => $province->name,
                ],
                'data' => $centers,
                'count' => $centers->count(),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Province not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch centers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
