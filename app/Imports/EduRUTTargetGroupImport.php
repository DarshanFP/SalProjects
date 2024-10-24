<?php

namespace App\Imports;

use App\Models\OldProjects\ProjectEduRUTTargetGroup;
use Maatwebsite\Excel\Concerns\ToModel;

class EduRUTTargetGroupImport implements ToModel
{
    public function model(array $row)
    {
        // Ignore the first column (S.No.) and map the rest of the fields
        return new ProjectEduRUTTargetGroup([
            'beneficiary_name' => $row[1],  // Column 2 in Excel
            'caste' => $row[2],  // Column 3 in Excel
            'institution_name' => $row[3],  // Column 4 in Excel
            'class_standard' => $row[4],  // Column 5 in Excel
            'total_tuition_fee' => $row[5],  // Column 6 in Excel
            'eligibility_scholarship' => $row[6],  // Column 7 in Excel
            'expected_amount' => $row[7],  // Column 8 in Excel
            'contribution_from_family' => $row[8],  // Column 9 in Excel
        ]);
    }
}
