<?php

namespace App\Imports;

use App\Models\OldProjects\ProjectEduRUTAnnexedTargetGroup;
use Maatwebsite\Excel\Concerns\ToModel;

class EduRUTAnnexedTargetGroupImport implements ToModel
{
    public function model(array $row)
    {
        // Ignore the first column (S.No.) and map the rest of the fields
        return new ProjectEduRUTAnnexedTargetGroup([
            'beneficiary_name' => $row[1],  // Column 2 in Excel
            'family_background' => $row[2],  // Column 3 in Excel
            'need_of_support' => $row[3],  // Column 4 in Excel
        ]);
    }
}
