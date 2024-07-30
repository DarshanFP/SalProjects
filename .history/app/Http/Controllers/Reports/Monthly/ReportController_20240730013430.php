<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Monthly\DPObjective;
use App\Models\Reports\Monthly\DPActivity;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\Reports\Monthly\DPPhoto;
use App\Models\Reports\Monthly\DPOutlook;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class ReportController extends Controller
{
    public function create($project_id)
    {
        Log::info('Entering create method', ['project_id' => $project_id]);

        $project = Project::where('project_id', $project_id)->firstOrFail();
        Log::info('Project retrieved successfully', ['project' => $project]);

        $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
        Log::info('Retrieved highest phase for the project', ['highestPhase' => $highestPhase]);

        $budgets = ProjectBudget::where('project_id', $project->project_id)
                                ->where('phase', $highestPhase)
                                ->get();
        Log::info('Budgets retrieved for the highest phase', ['budgets' => $budgets]);

        $amountSanctioned = $project->amount_sanctioned ?? 0.00;
        $amountForwarded = $project->amount_forwarded ?? 0.00;
        Log::info('Sanctioned and forwarded amounts', [
            'amountSanctioned' => $amountSanctioned,
            'amountForwarded' => $amountForwarded
        ]);

        $lastExpenses = collect();

        $lastReport = DPReport::where('project_id', $project->project_id)
                              ->orderBy('created_at', 'desc')
                              ->first();

        if ($lastReport) {
            $lastExpenses = DPAccountDetail::where('report_id', $lastReport->report_id)
                                           ->get()
                                           ->keyBy('particulars')
                                           ->map(function ($item) {
                                               return $item->total_expenses;
                                           });
            Log::info('Last expenses retrieved', ['lastExpenses' => $lastExpenses]);
        } else {
            Log::info('No last report found, lastExpenses remains empty');
        }

        $user = Auth::user();

        return view('reports.monthly.ReportAll', compact('project', 'user', 'amountSanctioned', 'amountForwarded', 'budgets', 'lastExpenses'));
    }

    public function store(Request $request)
{
    Log::info('Store method called with data:', ['data' => $request->all()]);

    // Validate the incoming request data
    $validatedData = $request->validate([
        'project_id' => 'required|string|max:255',
        'project_title' => 'nullable|string|max:255',
        'project_type' => 'nullable|string|max:255', // Validate project_type
        'place' => 'nullable|string|max:255',
        'society_name' => 'nullable|string|max:255',
        'commencement_month_year' => 'nullable|date',
        'in_charge' => 'nullable|string|max:255',
        'total_beneficiaries' => 'required|integer',
        'report_month' => 'required|integer|between:1,12',
        'report_year' => 'required|integer',
        'goal' => 'nullable|string',
        'account_period_start' => 'nullable|date', // Validate account period start
        'account_period_end' => 'nullable|date', // Validate account period end
        'photos' => 'nullable|array',
        'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
        'photo_descriptions' => 'nullable|array',
        'photo_descriptions.*' => 'nullable|string|max:255'
    ]);

    // Check authentication and set user_id
    $user_id = auth()->check() ? auth()->id() : null;

    // Generate report_id
    $project_id = $validatedData['project_id'];
    $max_suffix = DPReport::where('report_id', 'LIKE', "{$project_id}-%")
                          ->get()
                          ->map(function ($report) {
                              return intval(explode('-', $report->report_id)[1]);
                          })
                          ->max() ?? 0;
    $new_suffix = str_pad($max_suffix + 1, 2, '0', STR_PAD_LEFT);
    $report_id = "{$project_id}-{$new_suffix}";

    // Create the report_month_year from report_month and report_year
    $report_month_year = Carbon::createFromDate($validatedData['report_year'], $validatedData['report_month'], 1);

    // Create the report
    $report = DPReport::create([
        'report_id' => $report_id,
        'project_id' => $project_id,
        'user_id' => $user_id,
        'project_title' => $validatedData['project_title'],
        'project_type' => $validatedData['project_type'],
        'place' => $validatedData['place'],
        'society_name' => $validatedData['society_name'],
        'commencement_month_year' => $validatedData['commencement_month_year'],
        'in_charge' => $validatedData['in_charge'],
        'total_beneficiaries' => $validatedData['total_beneficiaries'],
        'report_month_year' => $report_month_year,
        'goal' => $validatedData['goal'],
        'account_period_start' => $validatedData['account_period_start'],
        'account_period_end' => $validatedData['account_period_end']
    ]);

    if ($report) {
        Log::info('Report created successfully', ['report_id' => $report->id]);
    } else {
        Log::error('Failed to create report');
        return back()->withErrors(['msg' => 'Failed to create report']);
    }

    // Handle photos and descriptions if provided
    if ($request->hasFile('photos')) {
        foreach ($request->file('photos') as $index => $file) {
            $path = $file->store('ReportImages/Quarterly', 'public');
            $photoData = [
                'report_id' => $report->id,
                'path' => $path,
                'description' => $request->photo_descriptions[$index] ?? ''
            ];
            $photo = DPPhoto::create($photoData);
            Log::info('Photo uploaded', ['photo_id' => $photo->id]);
        }
    }

    return redirect()->route('reports.monthly.index')->with('success', 'Report submitted successfully.');
}





    public function index()
    {
        Log::info('Entering index method');

        $reports = DPReport::with('project', 'user')->get();
        Log::info('Reports retrieved', ['reports' => $reports]);

        return view('reports.monthly.index', compact('reports'));
    }

    public function show($report_id)
    {
        Log::info('Entering show method', ['report_id' => $report_id]);

        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        Log::info('Report retrieved', ['report' => $report]);

        return view('reports.monthly.show', compact('report'));
    }

    public function edit($report_id)
    {
        Log::info('Entering edit method', ['report_id' => $report_id]);

        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        Log::info('Report retrieved for editing', ['report' => $report]);

        return view('reports.monthly.edit', compact('report'));
    }

    public function update(Request $request, $report_id)
    {
        Log::info('Entering update method', ['report_id' => $report_id, 'request' => $request->all()]);

        $request->validate([
            // validation rules
        ]);

        DB::beginTransaction();

        try {
            Log::info('Starting transaction for updating report');

            $report = DPReport::where('report_id', $report_id)->firstOrFail();
            $report->update([
                'total_beneficiaries' => $request->input('total_beneficiaries'),
                'report_month_year' => $request->input('report_month_year'),
                'goal' => $request->input('goal'),
                'account_period_start' => $request->input('account_period_start'),
                'account_period_end' => $request->input('account_period_end'),
                'amount_sanctioned_overview' => $request->input('amount_sanctioned_overview'),
                'amount_forwarded_overview' => $request->input('amount_forwarded_overview'),
                'amount_in_hand' => $request->input('amount_in_hand'),
                'total_balance_forwarded' => $request->input('total_balance_forwarded'),
                'status' => 'updated',
            ]);
            Log::info('Report updated', ['report' => $report]);

            $report->objectives()->delete();
            $report->accountDetails()->delete();
            $report->photos()->delete();
            $report->outlooks()->delete();
            Log::info('Old objectives, account details, photos, and outlooks deleted');

            foreach ($request->input('objective', []) as $index => $objective) {
                $objective = DPObjective::create([
                    'objective_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'objective' => $objective,
                    'expected_outcome' => $request->input("expected_outcome.{$index}"),
                    'not_happened' => $request->input("not_happened.{$index}"),
                    'why_not_happened' => $request->input("why_not_happened.{$index}"),
                    'changes' => $request->input("changes.{$index}"),
                    'why_changes' => $request->input("why_changes.{$index}"),
                    'lessons_learnt' => $request->input("lessons_learnt.{$index}"),
                    'todo_lessons_learnt' => $request->input("todo_lessons_learnt.{$index}"),
                ]);
                Log::info('Objective created', ['objective' => $objective]);

                foreach ($request->input("summary_activities.{$index}", []) as $activityIndex => $summaryActivity) {
                    $activity = DPActivity::create([
                        'activity_id' => Str::uuid()->toString(),
                        'objective_id' => $objective->objective_id,
                        'month' => $request->input("month.{$index}.{$activityIndex}"),
                        'summary_activities' => $summaryActivity,
                        'qualitative_quantitative_data' => $request->input("qualitative_quantitative_data.{$index}.{$activityIndex}"),
                        'intermediate_outcomes' => $request->input("intermediate_outcomes.{$index}.{$activityIndex}"),
                    ]);
                    Log::info('Activity created', ['activity' => $activity]);
                }
            }

            foreach ($request->input('particulars', []) as $index => $particular) {
                $accountDetail = DPAccountDetail::create([
                    'account_detail_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'particulars' => $particular,
                    'amount_forwarded' => $request->input("amount_forwarded.{$index}"),
                    'amount_sanctioned' => $request->input("amount_sanctioned.{$index}"),
                    'total_amount' => $request->input("total_amount.{$index}"),
                    'expenses_last_month' => $request->input("expenses_last_month.{$index}"),
                    'expenses_this_month' => $request->input("expenses_this_month.{$index}"),
                    'total_expenses' => $request->input("total_expenses.{$index}"),
                    'balance_amount' => $request->input("balance_amount.{$index}"),
                ]);
                Log::info('Account detail created', ['accountDetail' => $accountDetail]);
            }

            foreach ($request->file('photos', []) as $index => $photo) {
                $photoPath = $photo->store('photos', 'public');
                $dpPhoto = DPPhoto::create([
                    'photo_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'photo_path' => $photoPath,
                    'photo_name' => $photo->getClientOriginalName(),
                    'description' => $request->input("photo_descriptions.{$index}"),
                ]);
                Log::info('Photo uploaded and created', ['dpPhoto' => $dpPhoto]);
            }

            foreach ($request->input('date', []) as $index => $date) {
                $outlook = DPOutlook::create([
                    'outlook_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'date' => $date,
                    'plan_next_month' => $request->input("plan_next_month.{$index}"),
                ]);
                Log::info('Outlook created', ['outlook' => $outlook]);
            }

            DB::commit();
            Log::info('Transaction committed successfully');

            return redirect()->route('monthly.report.index')->with('success', 'Report updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating report', ['exception' => $e]);
            return redirect()->back()->withErrors('An error occurred while updating the report. Please try again.');
        }
    }

    public function review($report_id)
    {
        Log::info('Entering review method', ['report_id' => $report_id]);

        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        Log::info('Report retrieved for review', ['report' => $report]);

        return view('reports.monthly.review', compact('report'));
    }

    public function revert(Request $request, $report_id)
    {
        Log::info('Entering revert method', ['report_id' => $report_id, 'request' => $request->all()]);

        $report = DPReport::where('report_id', $report_id)->firstOrFail();
        $report->update([
            'status' => 'reverted',
            'revert_reason' => $request->input('revert_reason'),
        ]);
        Log::info('Report reverted', ['report' => $report]);

        return redirect()->route('monthly.report.index')->with('success', 'Report reverted successfully.');
    }


}
