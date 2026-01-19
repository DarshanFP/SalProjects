<?php

namespace App\Exports;

use App\Models\OldProjects\Project;
use App\Services\BudgetValidationService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class BudgetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->project->budgets;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No.',
            'Particular',
            'Costs (Rs.)',
            'Rate Multiplier',
            'Rate Duration',
            'This Phase (Rs.)',
        ];
    }

    /**
     * @param mixed $budget
     * @return array
     */
    public function map($budget): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $budget->particular ?? '',
            number_format($budget->rate_quantity ?? 0, 2),
            number_format($budget->rate_multiplier ?? 0, 2),
            number_format($budget->rate_duration ?? 0, 2),
            number_format($budget->this_phase ?? 0, 2),
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $budgetSummary = BudgetValidationService::getBudgetSummary($this->project);
        $budgetData = $budgetSummary['budget_data'];
        $lastRow = $this->project->budgets->count() + 3;

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Summary row
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6']
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Budget Items';
    }
}
