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

        return view('reports.monthly.ReportCommonForm', compact('project', 'user', 'amountSanctioned', 'amountForwarded', 'budgets', 'lastExpenses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // validation rules
        ]);

        DB::beginTransaction();

        try {
            $report = DPReport::create([
                'report_id' => Str::uuid()->toString(),
                'user_id' => Auth::id(),
                'project_id' => $request->input('project_id'),
                'project_title' => $request->input('project_title'),
                'project_type' => $request->input('project_type'),
                'place' => $request->input('place'),
                'society_name' => $request->input('society_name'),
                'commencement_month_year' => $request->input('commencement_month_year'),
                'in_charge' => $request->input('in_charge'),
                'total_beneficiaries' => $request->input('total_beneficiaries'),
                'report_month_year' => $request->input('report_month_year'),
                'goal' => $request->input('goal'),
                'account_period_start' => $request->input('account_period_start'),
                'account_period_end' => $request->input('account_period_end'),
                'amount_sanctioned_overview' => $request->input('amount_sanctioned_overview'),
                'amount_forwarded_overview' => $request->input('amount_forwarded_overview'),
                'amount_in_hand' => $request->input('amount_in_hand'),
                'total_balance_forwarded' => $request->input('total_balance_forwarded'),
                'status' => 'submitted',
            ]);

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

                foreach ($request->input("summary_activities.{$index}", []) as $activityIndex => $summaryActivity) {
                    DPActivity::create([
                        'activity_id' => Str::uuid()->toString(),
                        'objective_id' => $objective->objective_id,
                        'month' => $request->input("month.{$index}.{$activityIndex}"),
                        'summary_activities' => $summaryActivity,
                        'qualitative_quantitative_data' => $request->input("qualitative_quantitative_data.{$index}.{$activityIndex}"),
                        'intermediate_outcomes' => $request->input("intermediate_outcomes.{$index}.{$activityIndex}"),
                    ]);
                }
            }

            foreach ($request->input('particulars', []) as $index => $particular) {
                DPAccountDetail::create([
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
            }

            foreach ($request->file('photos', []) as $index => $photo) {
                $photoPath = $photo->store('photos', 'public');

                DPPhoto::create([
                    'photo_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'photo_path' => $photoPath,
                    'photo_name' => $photo->getClientOriginalName(),
                    'description' => $request->input("photo_descriptions.{$index}"),
                ]);
            }

            foreach ($request->input('date', []) as $index => $date) {
                DPOutlook::create([
                    'outlook_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'date' => $date,
                    'plan_next_month' => $request->input("plan_next_month.{$index}"),
                ]);
            }

            DB::commit();

            return redirect()->route('monthly.report.index')->with('success', 'Report submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing report', ['exception' => $e]);
            return redirect()->back()->withErrors('An error occurred while submitting the report. Please try again.');
        }
    }

    public function index()
    {
        $reports = DPReport::with('project', 'user')->get();
        return view('reports.monthly.index', compact('reports'));
    }

    public function show($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        return view('reports.monthly.show', compact('report'));
    }

    public function edit($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        return view('reports.monthly.edit', compact('report'));
    }

    public function update(Request $request, $report_id)
    {
        $request->validate([
            // validation rules
        ]);

        DB::beginTransaction();

        try {
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

            $report->objectives()->delete();
            $report->accountDetails()->delete();
            $report->photos()->delete();
            $report->outlooks()->delete();

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

                foreach ($request->input("summary_activities.{$index}", []) as $activityIndex => $summaryActivity) {
                    DPActivity::create([
                        'activity_id' => Str::uuid()->toString(),
                        'objective_id' => $objective->objective_id,
                        'month' => $request->input("month.{$index}.{$activityIndex}"),
                        'summary_activities' => $summaryActivity,
                        'qualitative_quantitative_data' => $request->input("qualitative_quantitative_data.{$index}.{$activityIndex}"),
                        'intermediate_outcomes' => $request->input("intermediate_outcomes.{$index}.{$activityIndex}"),
                    ]);
                }
            }

            foreach ($request->input('particulars', []) as $index => $particular) {
                DPAccountDetail::create([
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
            }

            foreach ($request->file('photos', []) as $index => $photo) {
                $photoPath = $photo->store('photos', 'public');

                DPPhoto::create([
                    'photo_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'photo_path' => $photoPath,
                    'photo_name' => $photo->getClientOriginalName(),
                    'description' => $request->input("photo_descriptions.{$index}"),
                ]);
            }

            foreach ($request->input('date', []) as $index => $date) {
                DPOutlook::create([
                    'outlook_id' => Str::uuid()->toString(),
                    'report_id' => $report->report_id,
                    'date' => $date,
                    'plan_next_month' => $request->input("plan_next_month.{$index}"),
                ]);
            }

            DB::commit();

            return redirect()->route('monthly.report.index')->with('success', 'Report updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating report', ['exception' => $e]);
            return redirect()->back()->withErrors('An error occurred while updating the report. Please try again.');
        }
    }

    public function review($report_id)
    {
        $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                          ->where('report_id', $report_id)
                          ->firstOrFail();
        return view('reports.monthly.review', compact('report'));
    }

    public function revert(Request $request, $report_id)
    {
        $report = DPReport::where('report_id', $report_id)->firstOrFail();
        $report->update([
            'status' => 'reverted',
            'revert_reason' => $request->input('revert_reason'),
        ]);

        return redirect()->route('monthly.report.index')->with('success', 'Report reverted successfully.');
    }
}
