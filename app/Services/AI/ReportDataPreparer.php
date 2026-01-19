<?php

namespace App\Services\AI;

use App\Models\Reports\Monthly\DPReport;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ReportDataPreparer
{
    /**
     * Prepare a single report for AI analysis
     *
     * @param DPReport $report
     * @return array
     */
    public static function prepareReportForAnalysis(DPReport $report): array
    {
        // Load relationships
        $report->load([
            'objectives.activities',
            'accountDetails',
            'photos',
            'outlooks',
            'project'
        ]);

        $objectives = [];
        foreach ($report->objectives as $objective) {
            $activities = [];
            foreach ($objective->activities as $activity) {
                $activities[] = [
                    'activity' => $activity->activity,
                    'summary' => $activity->summary_activities,
                    'qualitative_quantitative_data' => $activity->qualitative_quantitative_data,
                    'intermediate_outcomes' => $activity->intermediate_outcomes,
                ];
            }

            $objectives[] = [
                'objective' => $objective->objective,
                'expected_outcome' => $objective->expected_outcome,
                'not_happened' => $objective->not_happened,
                'why_not_happened' => $objective->why_not_happened,
                'changes' => $objective->changes,
                'why_changes' => $objective->why_changes,
                'lessons_learnt' => $objective->lessons_learnt,
                'todo_lessons_learnt' => $objective->todo_lessons_learnt,
                'activities' => $activities,
            ];
        }

        // Calculate budget totals
        $totalExpenses = $report->accountDetails->sum('expenses');
        $totalBudget = $report->accountDetails->sum('budget');

        $photos = [];
        foreach ($report->photos as $photo) {
            $photos[] = [
                'description' => $photo->description ?? '',
                'caption' => $photo->caption ?? '',
            ];
        }

        $outlooks = [];
        foreach ($report->outlooks as $outlook) {
            $outlooks[] = [
                'outlook' => $outlook->outlook ?? '',
            ];
        }

        return [
            'report_id' => $report->report_id,
            'period' => $report->report_month_year,
            'project' => [
                'title' => $report->project_title,
                'type' => $report->project_type,
                'goal' => $report->goal,
                'place' => $report->place,
                'society_name' => $report->society_name,
            ],
            'objectives' => $objectives,
            'budget' => [
                'sanctioned' => (float)($report->amount_sanctioned_overview ?? 0),
                'forwarded' => (float)($report->amount_forwarded_overview ?? 0),
                'expenses' => $totalExpenses,
                'balance' => (float)($report->total_balance_forwarded ?? 0),
                'in_hand' => (float)($report->amount_in_hand ?? 0),
            ],
            'beneficiaries' => $report->total_beneficiaries ?? 0,
            'outlooks' => $outlooks,
            'photos' => $photos,
            'account_period' => [
                'start' => $report->account_period_start,
                'end' => $report->account_period_end,
            ],
        ];
    }

    /**
     * Prepare a collection of reports for analysis
     *
     * @param Collection $reports
     * @return array
     */
    public static function prepareCollectionForAnalysis(Collection $reports): array
    {
        $reportsData = [];

        foreach ($reports->sortBy('report_month_year') as $report) {
            $reportsData[] = self::prepareReportForAnalysis($report);
        }

        return [
            'reports' => $reportsData,
            'count' => count($reportsData),
            'period_range' => [
                'start' => $reports->min('report_month_year'),
                'end' => $reports->max('report_month_year'),
            ],
        ];
    }

    /**
     * Extract textual content from a report
     *
     * @param DPReport $report
     * @return string
     */
    public static function extractTextualContent(DPReport $report): string
    {
        $report->load([
            'objectives.activities',
            'outlooks'
        ]);

        $text = [];

        // Project information
        $text[] = "Project: {$report->project_title}";
        $text[] = "Goal: {$report->goal}";
        $text[] = "Period: {$report->report_month_year}";

        // Objectives
        foreach ($report->objectives as $objective) {
            $text[] = "\nObjective: {$objective->objective}";

            if ($objective->not_happened) {
                $text[] = "Not Happened: {$objective->not_happened}";
                $text[] = "Why: {$objective->why_not_happened}";
            }

            if ($objective->changes) {
                $text[] = "Changes Made: {$objective->why_changes}";
            }

            if ($objective->lessons_learnt) {
                $text[] = "Lessons Learnt: {$objective->lessons_learnt}";
            }

            // Activities
            foreach ($objective->activities as $activity) {
                if ($activity->summary_activities) {
                    $text[] = "Activity: {$activity->summary_activities}";
                }
                if ($activity->qualitative_quantitative_data) {
                    $text[] = "Data: {$activity->qualitative_quantitative_data}";
                }
            }
        }

        // Outlooks
        foreach ($report->outlooks as $outlook) {
            if ($outlook->outlook) {
                $text[] = "Outlook: {$outlook->outlook}";
            }
        }

        return implode("\n", $text);
    }
}
