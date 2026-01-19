<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BudgetReportExport implements WithMultipleSheets
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new BudgetVsActualSheet($this->reportData['budget_vs_actual']),
            new ExpenseBreakdownSheet($this->reportData['expense_breakdown']),
            new TrendAnalysisSheet($this->reportData['trend_analysis']),
            new SummarySheet($this->reportData['summary']),
        ];
    }
}

class BudgetVsActualSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return array_map(function($item) {
            return [
                $item['project_id'],
                $item['project_title'],
                $item['project_type'],
                number_format($item['budget'], 2),
                number_format($item['actual'], 2),
                number_format($item['variance'], 2),
                number_format($item['variance_percentage'], 2),
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            'Project ID',
            'Project Title',
            'Project Type',
            'Budget (Rs.)',
            'Actual Expenses (Rs.)',
            'Variance (Rs.)',
            'Variance (%)',
        ];
    }

    public function title(): string
    {
        return 'Budget vs Actual';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}

class ExpenseBreakdownSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return array_map(function($item) {
            return [
                $item['project_id'],
                $item['project_title'],
                $item['project_type'],
                number_format($item['total_expenses'], 2),
                number_format($item['percentage_of_budget'], 2),
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            'Project ID',
            'Project Title',
            'Project Type',
            'Total Expenses (Rs.)',
            'Percentage of Budget (%)',
        ];
    }

    public function title(): string
    {
        return 'Expense Breakdown';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}

class TrendAnalysisSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return array_map(function($item) {
            return [
                $item['month'],
                number_format($item['total_expenses'], 2),
                $item['project_count'],
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            'Month',
            'Total Expenses (Rs.)',
            'Number of Projects',
        ];
    }

    public function title(): string
    {
        return 'Trend Analysis';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}

class SummarySheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [[
            $this->data['total_projects'],
            number_format($this->data['total_budget'], 2),
            number_format($this->data['total_expenses'], 2),
            number_format($this->data['total_remaining'], 2),
        ]];
    }

    public function headings(): array
    {
        return [
            'Total Projects',
            'Total Budget (Rs.)',
            'Total Expenses (Rs.)',
            'Total Remaining (Rs.)',
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6']
                ],
            ],
        ];
    }
}
