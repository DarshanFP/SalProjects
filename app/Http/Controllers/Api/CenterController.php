<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for Center Management
 *
 * Provides API endpoints for center-related operations.
 */
class CenterController extends Controller
{
    /**
     * List all centers.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Center::query();

            // Optionally filter by active status
            if ($request->has('active') && $request->boolean('active')) {
                $query->active();
            }

            // Optionally filter by province_id
            if ($request->has('province_id')) {
                $query->byProvince($request->integer('province_id'));
            }

            // Optionally include relationships
            if ($request->has('include')) {
                $includes = explode(',', $request->get('include'));

                if (in_array('province', $includes)) {
                    $query->with('province:id,name');
                }
            }

            $centers = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $centers,
                'count' => $centers->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch centers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get centers by province ID.
     *
     * @param int $provinceId Province ID
     * @param Request $request
     * @return JsonResponse
     */
    public function byProvince(int $provinceId, Request $request): JsonResponse
    {
        try {
            $province = Province::findOrFail($provinceId);

            $query = Center::byProvince($provinceId);

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
