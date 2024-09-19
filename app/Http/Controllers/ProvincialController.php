<?php
namespace App\Http\Controllers;

use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class ProvincialController extends Controller
{
    // Access to provincials only.
    public function __construct()
    {
        $this->middleware(['auth', 'role:provincial']);
    }

    // Index page for provincial
    public function ProvincialDashboard(Request $request)
    {
        $provincial = auth()->user();

        // Fetch reports for executors under the provincial
        $reportsQuery = DPReport::whereHas('user', function ($query) use ($provincial) {
            $query->where('parent_id', $provincial->id);
        });

        // Apply filtering if provided in the request
        if ($request->filled('place')) {
            $reportsQuery->where('place', $request->place);
        }
        if ($request->filled('user_id')) {
            $reportsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('project_type')) {
            $reportsQuery->where('project_type', $request->project_type);
        }

        $reports = $reportsQuery->get();

        // Fetch unique places and users for filters
        $places = DPReport::distinct()->pluck('place');
        $users = User::where('parent_id', $provincial->id)->get();

        return view('provincial.index', compact('reports', 'places', 'users'));
    }

    // public function showReport($id)
    // {
    //     $report = DPReport::with([
    //         'user.parent',
    //         'objectives.activities',
    //         'accountDetails',
    //         'photos',
    //         'outlooks',
    //         'annexures',
    //         'rqis_age_profile',
    //         'rqst_trainee_profile',
    //         'rqwd_inmate_profile',
    //         'comments.user' // Load comments with associated user
    //     ])->findOrFail($id);

    //     return view('provincial.show_report', compact('report'));
    // }
    public function showMonthlyReport($report_id)
    {
        $report = DPReport::with([
            'user.parent',
            'objectives.activities',
            'accountDetails',
            'photos',
            'outlooks',
            'annexures',
            'rqis_age_profile',
            'rqst_trainee_profile',
            'rqwd_inmate_profile',
            'comments.user'
        ])->where('report_id', $report_id)->firstOrFail();

        $provincial = auth()->user();

        // Authorization check: Ensure the report belongs to an executor under this provincial
        if ($report->user->parent_id !== $provincial->id) {
            abort(403, 'Unauthorized');
        }

        return view('reports.monthly.show', compact('report'));
    }

    // Show Create Executor form
    public function CreateExecutor()
    {
        $provincial = auth()->user();
        $province = strtoupper($provincial->province);

        // Define the mapping of provinces to their centers
        $centersMap = [
            'VIJAYAWADA' => [
                'Ajitsingh Nagar', 'Nunna', 'Jaggayyapeta', 'Beed', 'Mangalagiri',
                'S.A.Peta', 'Thiruvur', 'Chakan', 'Megalaya', 'Rajavaram',
                'Avanigadda', 'Darjeeling', 'Sarvajan Sneha Charitable Trust, Vijayawada'
            ],
            'VISAKHAPATNAM' => [
                'Arilova', 'Malkapuram', 'Madugula', 'Rajam', 'Kapileswarapuram',
                'Erukonda', 'Navajara, Jharkhand', 'Jalaripeta',
                'Wilhelm Meyer’s Developmental Society, Visakhapatnam.',
                'Edavalli', 'Megalaya', 'Nalgonda', 'Shanthi Niwas, Madugula',
                'Malkapuram College', 'Malkapuram Hospital', 'Arilova School',
                'Morning Star, Eluru'
            ],
            'BANGALORE' => [
                'Prajyothi Welfare Centre', 'Gadag', 'Kurnool', 'Madurai',
                'Madhavaram', 'Belgaum', 'Kadirepalli', 'Munambam', 'Kuderu'
            ],
        ];

        // Get the centers for the current provincial's province
        $centers = $centersMap[$province] ?? [];

        return view('provincial.createExecutor', compact('centers'));
    }

    // Store Executor

    public function StoreExecutor(Request $request)
    {
        try {
            // Log the incoming request data
            Log::info('Attempting to store a new executor', ['request_data' => $request->all()]);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:255',
                'society_name' => 'required|string|max:255',
                'center' => 'nullable|string|max:255',
                'address' => 'nullable|string',
            ]);

            // Log post-validation data
            Log::info('Validation successful', ['validated_data' => $validatedData]);

            $provincial = auth()->user();
            // Log the details of the authenticated user
            Log::info('Authenticated provincial details', ['provincial' => $provincial]);

            $executor = User::create([
                'name' => $validatedData['name'],
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'phone' => $validatedData['phone'],
                'society_name' => $validatedData['society_name'],
                'province' => $provincial->province,
                'center' => $validatedData['center'],
                'address' => $validatedData['address'],
                'role' => 'executor',
                'status' => 'active',
                'parent_id' => $provincial->id,
            ]);

            // Log the successful creation of the executor
            if ($executor) {
                Log::info('Executor created successfully', ['executor_id' => $executor->id]);
                $executor->assignRole('executor');
            } else {
                // Log failure to create executor
                Log::error('Failed to create executor');
            }

            return redirect()->route('provincial.createExecutor')->with('success', 'Executor created successfully.');
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Error storing executor', ['error' => $e->getMessage()]);
            return back()->withErrors('Failed to create executor: ' . $e->getMessage());
        }
    }



    // List of Executors
    public function listExecutors()
    {
        $provincial = auth()->user();
        $executors = User::where('parent_id', $provincial->id)->where('role', 'executor')->get();

        return view('provincial.executors', compact('executors'));
    }

    // Edit Executor
    public function editExecutor($id)
{
    $executor = User::findOrFail($id);
    $provincial = auth()->user();
    $province = strtoupper($provincial->province);

    // Define the mapping of provinces to their centers
    $centersMap = [
        'VIJAYAWADA' => [
            'Ajitsingh Nagar', 'Nunna', 'Jaggayyapeta', 'Beed', 'Mangalagiri',
            'S.A.Peta', 'Thiruvur', 'Chakan', 'Megalaya', 'Rajavaram',
            'Avanigadda', 'Darjeeling', 'Sarvajan Sneha Charitable Trust, Vijayawada'
        ],
        'VISAKHAPATNAM' => [
            'Arilova', 'Malkapuram', 'Madugula', 'Rajam', 'Kapileswarapuram',
            'Erukonda', 'Navajara, Jharkhand', 'Jalaripeta',
            'Wilhelm Meyer’s Developmental Society, Visakhapatnam.',
            'Edavalli', 'Megalaya', 'Nalgonda', 'Shanthi Niwas, Madugula',
            'Malkapuram College', 'Malkapuram Hospital', 'Arilova School',
            'Morning Star, Eluru'
        ],
        'BANGALORE' => [
            'Prajyothi Welfare Centre', 'Gadag', 'Kurnool', 'Madurai',
            'Madhavaram', 'Belgaum', 'Kadirepalli', 'Munambam', 'Kuderu'
        ],
    ];

    // Get the centers for the current provincial's province
    $centers = $centersMap[$province] ?? [];

    return view('provincial.editExecutor', compact('executor', 'centers'));
}


    // Update Executor
    public function updateExecutor(Request $request, $id)
    {
        $executor = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $executor->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $executor->id,
            'phone' => 'nullable|string|max:255',
            'society_name' => 'required|string|max:255',
            'center' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $executor->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'society_name' => $request->society_name,
            'center' => $request->center,
            'address' => $request->address,
            'status' => $request->status,
        ]);

        return redirect()->route('provincial.executors')->with('success', 'Executor updated successfully.');
    }

    // Reset Executor Password
    public function resetExecutorPassword(Request $request, $id)
    {
        $executor = User::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $executor->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('provincial.executors')->with('success', 'Executor password reset successfully.');
    }
}
