<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Wave 6A Phase 2.5: Backfill report society snapshot before enforcing NOT NULL.
 * Updates DP_Reports from projects (includes soft-deleted projects).
 * Reports with no matching project get fallback from first society.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Backfill from projects (raw join includes soft-deleted rows)
            DB::statement("
                UPDATE DP_Reports r
                INNER JOIN projects p ON r.project_id = p.project_id
                INNER JOIN societies s ON p.society_id = s.id
                SET
                    r.society_id = p.society_id,
                    r.society_name = COALESCE(NULLIF(TRIM(p.society_name), ''), s.name),
                    r.province_id = COALESCE(p.province_id, s.province_id)
                WHERE r.society_id IS NULL
                   OR r.province_id IS NULL
                   OR r.society_name IS NULL
                   OR TRIM(COALESCE(r.society_name, '')) = ''
            ");
        } else {
            // SQLite / other: iterate and update
            $rows = DB::table('DP_Reports as r')
                ->join('projects as p', 'r.project_id', '=', 'p.project_id')
                ->join('societies as s', 'p.society_id', '=', 's.id')
                ->where(function ($q) {
                    $q->whereNull('r.society_id')
                        ->orWhereNull('r.province_id')
                        ->orWhereNull('r.society_name')
                        ->orWhere('r.society_name', '');
                })
                ->select('r.report_id', 'p.society_id', 'p.society_name', 'p.province_id', 's.name as s_name')
                ->get();

            foreach ($rows as $row) {
                $societyName = $row->society_name ? trim($row->society_name) : null;
                if (empty($societyName)) {
                    $societyName = $row->s_name;
                }
                DB::table('DP_Reports')
                    ->where('report_id', $row->report_id)
                    ->update([
                        'society_id' => $row->society_id,
                        'society_name' => $societyName ?: 'Unknown',
                        'province_id' => $row->province_id ?? DB::table('societies')->where('id', $row->society_id)->value('province_id'),
                    ]);
            }
        }

        // For any reports still with nulls (no matching project), use first society as fallback
        $fallback = DB::table('societies')->orderBy('id')->first();
        if ($fallback) {
            $provinceId = $fallback->province_id;
            if ($provinceId === null) {
                $firstProvince = DB::table('provinces')->orderBy('id')->first();
                $provinceId = $firstProvince?->id ?? 1;
            }

            DB::table('DP_Reports')
                ->where(function ($q) {
                    $q->whereNull('society_id')
                        ->orWhereNull('province_id')
                        ->orWhereNull('society_name')
                        ->orWhere('society_name', '');
                })
                ->update([
                    'society_id' => $fallback->id,
                    'society_name' => $fallback->name ?: 'Unknown',
                    'province_id' => $provinceId,
                ]);
        }
    }

    public function down(): void
    {
        // No-op: backfill is irreversible; down would require restoring NULLs which we cannot reliably do
    }
};
