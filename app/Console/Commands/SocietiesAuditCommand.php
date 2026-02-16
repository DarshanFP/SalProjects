<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SocietiesAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'societies:audit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Phase 0: Read-only pre-migration audit for society/project mapping (Revision 5). No schema or data changes.';

    /**
     * Execute the console command.
     * READ-ONLY. No schema changes. No updates. No deletes.
     */
    public function handle(): int
    {
        $this->info('========================================');
        $this->info('  PHASE 0 — PRODUCTION AUDIT');
        $this->info('  Society → Project Mapping (Revision 5)');
        $this->info('========================================');
        $this->newLine();

        $hasFail = false;
        $hasWarning = false;

        // ---------- 1. Duplicate society names ----------
        $this->info('--- 1️⃣  Duplicate society names ---');
        $duplicates = DB::table('societies')
            ->select('name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        $dupCount = $duplicates->count();
        if ($dupCount > 0) {
            $this->getOutput()->getErrorStyle()->writeln('   FAIL: ' . $dupCount . ' duplicate name(s) found.');
            foreach ($duplicates as $row) {
                $this->line("   - \"{$row->name}\" (count: {$row->cnt})");
            }
            $hasFail = true;
        } else {
            $this->line('   PASS: No duplicate society names.');
        }
        $this->newLine();

        // ---------- 2. Projects without user ----------
        $this->info('--- 2️⃣  Projects without user ---');
        $projectsNoUser = DB::table('projects')->whereNull('user_id')->count();
        if ($projectsNoUser > 0) {
            $this->getOutput()->getErrorStyle()->writeln("   FAIL: {$projectsNoUser} project(s) have user_id IS NULL.");
            $hasFail = true;
        } else {
            $this->line('   PASS: All projects have a user_id.');
        }
        $this->newLine();

        // ---------- 3. project.society_name not found in societies ----------
        $this->info('--- 3️⃣  project.society_name not found in societies ---');
        $projectNamesMissing = DB::table('projects')
            ->leftJoin('societies', 'projects.society_name', '=', 'societies.name')
            ->whereNotNull('projects.society_name')
            ->where('projects.society_name', '!=', '')
            ->whereNull('societies.id')
            ->distinct()
            ->pluck('projects.society_name');
        $projectMissingCount = $projectNamesMissing->count();
        if ($projectMissingCount > 0) {
            $this->warn("   WARNING: {$projectMissingCount} distinct project.society_name value(s) not found in societies.");
            foreach ($projectNamesMissing->take(50) as $name) {
                $this->line("   - \"{$name}\"");
            }
            if ($projectMissingCount > 50) {
                $this->line("   ... and " . ($projectMissingCount - 50) . " more.");
            }
            $hasWarning = true;
        } else {
            $this->line('   PASS: All non-empty project.society_name values exist in societies.');
        }
        $this->newLine();

        // ---------- 4. user.society_name not found in societies ----------
        $this->info('--- 4️⃣  user.society_name not found in societies ---');
        $userNamesMissing = DB::table('users')
            ->leftJoin('societies', 'users.society_name', '=', 'societies.name')
            ->whereNotNull('users.society_name')
            ->where('users.society_name', '!=', '')
            ->whereNull('societies.id')
            ->distinct()
            ->pluck('users.society_name');
        $userMissingCount = $userNamesMissing->count();
        if ($userMissingCount > 0) {
            $this->warn("   WARNING: {$userMissingCount} distinct user.society_name value(s) not found in societies.");
            foreach ($userNamesMissing->take(50) as $name) {
                $this->line("   - \"{$name}\"");
            }
            if ($userMissingCount > 50) {
                $this->line("   ... and " . ($userMissingCount - 50) . " more.");
            }
            $hasWarning = true;
        } else {
            $this->line('   PASS: All non-empty user.society_name values exist in societies.');
        }
        $this->newLine();

        // ---------- 5. Duplicate provinces by name ----------
        $this->info('--- 5️⃣  Duplicate provinces by name ---');
        $dupProvinces = DB::table('provinces')
            ->select('name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        $dupProvinceCount = $dupProvinces->count();
        if ($dupProvinceCount > 0) {
            $this->getOutput()->getErrorStyle()->writeln('   FAIL: ' . $dupProvinceCount . ' duplicate province name(s) found.');
            foreach ($dupProvinces as $row) {
                $this->line("   - \"{$row->name}\" (count: {$row->cnt})");
            }
            $hasFail = true;
        } else {
            $this->line('   PASS: No duplicate province names.');
        }
        $this->newLine();

        // ---------- 6. Users with NULL or empty province ----------
        $this->info('--- 6️⃣  Users with NULL or empty province ---');
        $usersNoProvince = DB::table('users')
            ->where(function ($q) {
                $q->whereNull('province')->orWhere('province', '');
            })
            ->count();
        if ($usersNoProvince > 0) {
            $this->getOutput()->getErrorStyle()->writeln("   FAIL: {$usersNoProvince} user(s) have province IS NULL or empty.");
            $hasFail = true;
        } else {
            $this->line('   PASS: All users have non-empty province.');
        }
        $this->newLine();

        // ---------- 7. Users whose province does NOT match provinces.name ----------
        $this->info('--- 7️⃣  Users whose province does NOT match provinces.name ---');
        $usersProvinceNoMatch = DB::table('users')
            ->leftJoin('provinces', 'provinces.name', '=', 'users.province')
            ->whereNotNull('users.province')
            ->where('users.province', '!=', '')
            ->whereNull('provinces.id')
            ->distinct()
            ->pluck('users.province');
        $noMatchCount = $usersProvinceNoMatch->count();
        if ($noMatchCount > 0) {
            $this->getOutput()->getErrorStyle()->writeln("   FAIL: {$noMatchCount} distinct user province value(s) have no matching provinces.name.");
            foreach ($usersProvinceNoMatch as $name) {
                $this->line("   - \"{$name}\"");
            }
            $hasFail = true;
        } else {
            $this->line('   PASS: All non-empty user provinces match a provinces.name.');
        }
        $this->newLine();

        // ---------- 8. Projects whose user's province would fail resolution ----------
        $this->info('--- 8️⃣  Projects whose user\'s province would fail resolution ---');
        $projectsFailResolution = DB::table('projects')
            ->join('users', 'projects.user_id', '=', 'users.id')
            ->leftJoin('provinces', 'provinces.name', '=', 'users.province')
            ->where(function ($q) {
                $q->whereNull('provinces.id')
                    ->orWhereNull('users.province')
                    ->orWhere('users.province', '');
            })
            ->select('projects.id', 'projects.project_id', 'users.province')
            ->get();
        $failResCount = $projectsFailResolution->count();
        if ($failResCount > 0) {
            $this->getOutput()->getErrorStyle()->writeln("   FAIL: {$failResCount} project(s) would fail province resolution (user province null/empty or no match).");
            foreach ($projectsFailResolution->take(20) as $row) {
                $prov = $row->province === null ? 'NULL' : "\"{$row->province}\"";
                $this->line("   - project id: {$row->id}, project_id: {$row->project_id}, user.province: {$prov}");
            }
            if ($failResCount > 20) {
                $this->line("   ... and " . ($failResCount - 20) . " more.");
            }
            $hasFail = true;
        } else {
            $this->line('   PASS: All projects have a user with province resolvable to provinces.name.');
        }
        $this->newLine();

        // ---------- 9. Estimate projects province backfill distribution (summary only) ----------
        $this->info('--- 9️⃣  Estimate projects province backfill distribution ---');
        $distribution = DB::table('projects')
            ->join('users', 'projects.user_id', '=', 'users.id')
            ->select('users.province', DB::raw('COUNT(projects.id) as project_count'))
            ->groupBy('users.province')
            ->orderByDesc('project_count')
            ->get();
        $this->line('   Summary (by user.province):');
        foreach ($distribution as $row) {
            $label = $row->province === null || $row->province === '' ? '(null/empty)' : "\"{$row->province}\"";
            $this->line("   - {$label}: {$row->project_count} project(s)");
        }
        $this->line('   (Informational only — not a pass/fail condition.)');
        $this->newLine();

        // ---------- 10. Estimate society_name resolution success rate ----------
        $this->info('--- 🔟 Estimate society_name resolution success rate ---');
        $totalDistinctProjectSocietyNames = (int) DB::table('projects')
            ->whereNotNull('society_name')
            ->where('society_name', '!=', '')
            ->count(DB::raw('DISTINCT society_name'));
        $resolvedProjectSocietyNames = (int) DB::table('projects')
            ->join('societies', 'projects.society_name', '=', 'societies.name')
            ->whereNotNull('projects.society_name')
            ->where('projects.society_name', '!=', '')
            ->count(DB::raw('DISTINCT projects.society_name'));
        $pct = $totalDistinctProjectSocietyNames > 0
            ? round(100.0 * $resolvedProjectSocietyNames / $totalDistinctProjectSocietyNames, 1)
            : 100.0;
        $this->line("   Distinct project.society_name: {$totalDistinctProjectSocietyNames}");
        $this->line("   Resolving to societies.name:  {$resolvedProjectSocietyNames}");
        $this->line("   Resolution rate: {$pct}%");
        if ($pct < 100) {
            $this->warn('   WARNING: Resolution rate < 100%.');
            $hasWarning = true;
        } else {
            $this->line('   PASS: 100% resolution rate.');
        }
        $this->newLine();

        // ---------- Dry-run summary (Part D) ----------
        $this->info('========================================');
        $this->info('  DRY-RUN SUMMARY (counts only, no updates)');
        $this->info('========================================');
        $totalUsers = DB::table('users')->count();
        $usersResolvableProvinceId = DB::table('users')
            ->join('provinces', 'provinces.name', '=', 'users.province')
            ->whereNotNull('users.province')
            ->where('users.province', '!=', '')
            ->count('users.id');
        $totalProjects = DB::table('projects')->count();
        $projectsResolvableProvinceId = DB::table('projects')
            ->join('users', 'projects.user_id', '=', 'users.id')
            ->join('provinces', 'provinces.name', '=', 'users.province')
            ->count('projects.id');
        $projectsResolvableSocietyId = DB::table('projects')
            ->join('societies', 'projects.society_name', '=', 'societies.name')
            ->whereNotNull('projects.society_name')
            ->where('projects.society_name', '!=', '')
            ->count('projects.id');
        $projectsWithSocietyName = DB::table('projects')
            ->whereNotNull('society_name')
            ->where('society_name', '!=', '')
            ->count();
        $projectsUnresolvedSocietyName = $projectsWithSocietyName - $projectsResolvableSocietyId;

        $this->line("   Total users:                      {$totalUsers}");
        $this->line("   Users resolvable to province_id:   {$usersResolvableProvinceId}");
        $this->line("   Total projects:                   {$totalProjects}");
        $this->line("   Projects resolvable to province_id: {$projectsResolvableProvinceId}");
        $this->line("   Projects resolvable to society_id:  {$projectsResolvableSocietyId}");
        $this->line("   Projects with unresolved society_name: {$projectsUnresolvedSocietyName}");
        $this->newLine();

        // ---------- Final status ----------
        $this->info('========================================');
        if ($hasFail) {
            $this->getOutput()->getErrorStyle()->writeln('  AUDIT FAILED');
            $this->info('========================================');
            return 1;
        }
        if ($hasWarning) {
            $this->warn('  AUDIT PASSED (WITH WARNINGS)');
            $this->info('========================================');
            return 0;
        }
        $this->info('  AUDIT PASSED');
        $this->info('========================================');
        return 0;
    }
}
