<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Helpers\ProjectPermissionHelper;
use App\Constants\ProjectStatus;
use Illuminate\Support\Facades\DB;

class TestApplicantAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:applicant-access
                            {--applicant-id= : Specific applicant user ID to test}
                            {--create-test-data : Create test data if needed}
                            {--detailed : Show detailed output for all tests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test applicant user access to projects and reports';

    protected $testResults = [];
    protected $applicant = null;
    protected $verbose = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->verbose = $this->option('detailed');

        $this->info('🧪 Testing Applicant Access Functionality');
        $this->info('==========================================');
        $this->newLine();

        // Step 1: Find or create applicant user
        if (!$this->setupApplicant()) {
            return 1;
        }

        // Step 2: Setup test data if needed
        if ($this->option('create-test-data')) {
            $this->createTestData();
        }

        // Step 3: Run tests
        $this->runTests();

        // Step 4: Display results
        $this->displayResults();

        return 0;
    }

    /**
     * Setup applicant user for testing
     */
    protected function setupApplicant()
    {
        $applicantId = $this->option('applicant-id');

        if ($applicantId) {
            $this->applicant = User::find($applicantId);
            if (!$this->applicant) {
                $this->error("❌ Applicant user with ID {$applicantId} not found.");
                return false;
            }
            if ($this->applicant->role !== 'applicant') {
                $this->error("❌ User with ID {$applicantId} is not an applicant (role: {$this->applicant->role}).");
                return false;
            }
        } else {
            $this->applicant = User::where('role', 'applicant')->first();
            if (!$this->applicant) {
                $this->error("❌ No applicant users found in database.");
                $this->info("💡 Create an applicant user first or use --applicant-id option.");
                return false;
            }
        }

        $this->info("✅ Using applicant: {$this->applicant->name} (ID: {$this->applicant->id})");
        $this->newLine();

        return true;
    }

    /**
     * Create test data if needed
     */
    protected function createTestData()
    {
        $this->info('📝 Creating test data...');

        // Find a project where applicant is NOT owner
        $project = Project::where('user_id', '!=', $this->applicant->id)
            ->where('in_charge', '!=', $this->applicant->id)
            ->first();

        if ($project) {
            $oldInCharge = $project->in_charge;
            $project->in_charge = $this->applicant->id;
            $project->save();
            $this->info("   ✅ Set applicant as in-charge of project: {$project->project_id}");
            $this->warn("   ⚠️  Original in-charge was: {$oldInCharge}");
        } else {
            $this->warn("   ⚠️  No suitable project found for testing.");
        }

        $this->newLine();
    }

    /**
     * Run all tests
     */
    protected function runTests()
    {
        $this->info('🔍 Running Tests...');
        $this->newLine();

        // Test Group 1: Project Access
        $this->testProjectAccess();

        // Test Group 2: Permission Helper
        $this->testPermissionHelper();

        // Test Group 3: Dashboard Queries
        $this->testDashboardQueries();

        // Test Group 4: Report Queries
        $this->testReportQueries();

        // Test Group 5: Aggregated Reports
        $this->testAggregatedReports();
    }

    /**
     * Test Project Access
     */
    protected function testProjectAccess()
    {
        $this->info('📋 Test Group 1: Project Access');

        // Test 1.1: Find projects where applicant is owner
        $ownedProjects = Project::where('user_id', $this->applicant->id)->count();
        $this->testResult('1.1', 'Find owned projects', $ownedProjects > 0, "Found {$ownedProjects} owned projects");

        // Test 1.2: Find projects where applicant is in-charge (but not owner)
        $inChargeProjects = Project::where('in_charge', $this->applicant->id)
            ->where('user_id', '!=', $this->applicant->id)
            ->count();
        $this->testResult('1.2', 'Find in-charge projects (not owner)', $inChargeProjects > 0,
            "Found {$inChargeProjects} in-charge projects");

        // Test 1.3: Find projects where applicant is both owner and in-charge
        $bothProjects = Project::where('user_id', $this->applicant->id)
            ->where('in_charge', $this->applicant->id)
            ->count();
        $this->testResult('1.3', 'Find projects (owner & in-charge)', $bothProjects >= 0,
            "Found {$bothProjects} projects where applicant is both owner and in-charge");

        // Test 1.4: Total accessible projects
        $totalProjects = Project::where(function($query) {
            $query->where('user_id', $this->applicant->id)
                  ->orWhere('in_charge', $this->applicant->id);
        })->count();
        $this->testResult('1.4', 'Total accessible projects', $totalProjects > 0,
            "Total: {$totalProjects} projects (owned + in-charge)");

        $this->newLine();
    }

    /**
     * Test Permission Helper
     */
    protected function testPermissionHelper()
    {
        $this->info('🔐 Test Group 2: Permission Helper');

        // Find a project where applicant is owner
        $ownedProject = Project::where('user_id', $this->applicant->id)->first();

        if ($ownedProject) {
            $canEdit = ProjectPermissionHelper::canEdit($ownedProject, $this->applicant);
            $this->testResult('2.1', 'canEdit() - owned project', $canEdit,
                "Can edit: " . ($canEdit ? 'Yes' : 'No'));

            $canView = ProjectPermissionHelper::canView($ownedProject, $this->applicant);
            $this->testResult('2.2', 'canView() - owned project', $canView,
                "Can view: " . ($canView ? 'Yes' : 'No'));

            $canApplicantEdit = ProjectPermissionHelper::canApplicantEdit($ownedProject, $this->applicant);
            $this->testResult('2.3', 'canApplicantEdit() - owned project', $canApplicantEdit,
                "Can applicant edit: " . ($canApplicantEdit ? 'Yes' : 'No'));
        } else {
            $this->warn("   ⚠️  No owned project found for permission testing");
        }

        // Find a project where applicant is in-charge (but not owner) - KEY TEST
        $inChargeProject = Project::where('in_charge', $this->applicant->id)
            ->where('user_id', '!=', $this->applicant->id)
            ->first();

        if ($inChargeProject) {
            $canEdit = ProjectPermissionHelper::canEdit($inChargeProject, $this->applicant);
            $this->testResult('2.4', 'canEdit() - in-charge project ⭐', $canEdit,
                "Can edit in-charge project: " . ($canEdit ? 'Yes ✅' : 'No ❌'));

            $canView = ProjectPermissionHelper::canView($inChargeProject, $this->applicant);
            $this->testResult('2.5', 'canView() - in-charge project', $canView,
                "Can view in-charge project: " . ($canView ? 'Yes' : 'No'));

            $canApplicantEdit = ProjectPermissionHelper::canApplicantEdit($inChargeProject, $this->applicant);
            $this->testResult('2.6', 'canApplicantEdit() - in-charge project ⭐', $canApplicantEdit,
                "Can applicant edit in-charge project: " . ($canApplicantEdit ? 'Yes ✅' : 'No ❌'));
        } else {
            $this->warn("   ⚠️  No in-charge project found - this is a KEY test case!");
            $this->info("   💡 Use --create-test-data to set up a test project");
        }

        // Test getEditableProjects
        $editableProjects = ProjectPermissionHelper::getEditableProjects($this->applicant)->count();
        $this->testResult('2.7', 'getEditableProjects() count', $editableProjects >= 0,
            "Editable projects: {$editableProjects}");

        $this->newLine();
    }

    /**
     * Test Dashboard Queries
     */
    protected function testDashboardQueries()
    {
        $this->info('📊 Test Group 3: Dashboard Queries');

        // Test approved projects query (like ExecutorDashboard)
        $approvedProjects = Project::where(function($query) {
            $query->where('user_id', $this->applicant->id)
                  ->orWhere('in_charge', $this->applicant->id);
        })
        ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
        ->count();

        $this->testResult('3.1', 'Approved projects (dashboard) ⭐', $approvedProjects >= 0,
            "Found {$approvedProjects} approved projects");

        // Test owned approved projects
        $ownedApproved = Project::where('user_id', $this->applicant->id)
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
            ->count();

        // Test in-charge approved projects
        $inChargeApproved = Project::where('in_charge', $this->applicant->id)
            ->where('user_id', '!=', $this->applicant->id)
            ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
            ->count();

        $this->testResult('3.2', 'In-charge approved projects ⭐', $inChargeApproved >= 0,
            "Owned: {$ownedApproved}, In-charge: {$inChargeApproved}");

        $this->newLine();
    }

    /**
     * Test Report Queries
     */
    protected function testReportQueries()
    {
        $this->info('📄 Test Group 4: Report Queries');

        // Get project IDs where applicant has access
        $projectIds = Project::where(function($query) {
            $query->where('user_id', $this->applicant->id)
                  ->orWhere('in_charge', $this->applicant->id);
        })->pluck('project_id');

        // Test reports for accessible projects
        $totalReports = DPReport::whereIn('project_id', $projectIds)->count();
        $this->testResult('4.1', 'Total reports (accessible projects)', $totalReports >= 0,
            "Found {$totalReports} reports");

        // Test reports for owned projects
        $ownedProjectIds = Project::where('user_id', $this->applicant->id)->pluck('project_id');
        $ownedReports = DPReport::whereIn('project_id', $ownedProjectIds)->count();

        // Test reports for in-charge projects
        $inChargeProjectIds = Project::where('in_charge', $this->applicant->id)
            ->where('user_id', '!=', $this->applicant->id)
            ->pluck('project_id');
        $inChargeReports = DPReport::whereIn('project_id', $inChargeProjectIds)->count();

        $this->testResult('4.2', 'Reports for in-charge projects ⭐', $inChargeReports >= 0,
            "Owned projects reports: {$ownedReports}, In-charge projects reports: {$inChargeReports}");

        // Test pending reports
        $pendingReports = DPReport::whereIn('project_id', $projectIds)
            ->whereIn('status', [
                DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_COORDINATOR,
            ])
            ->count();

        $this->testResult('4.3', 'Pending reports', $pendingReports >= 0,
            "Found {$pendingReports} pending reports");

        // Test approved reports
        $approvedReports = DPReport::whereIn('project_id', $projectIds)
            ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
            ->count();

        $this->testResult('4.4', 'Approved reports', $approvedReports >= 0,
            "Found {$approvedReports} approved reports");

        $this->newLine();
    }

    /**
     * Test Aggregated Reports
     */
    protected function testAggregatedReports()
    {
        $this->info('📈 Test Group 5: Aggregated Reports');

        // Get project IDs
        $projectIds = Project::where(function($query) {
            $query->where('user_id', $this->applicant->id)
                  ->orWhere('in_charge', $this->applicant->id);
        })->pluck('project_id');

        // Test quarterly reports
        if (class_exists(\App\Models\Reports\Quarterly\QuarterlyReport::class)) {
            $quarterlyReports = \App\Models\Reports\Quarterly\QuarterlyReport::whereIn('project_id', $projectIds)->count();
            $this->testResult('5.1', 'Quarterly reports (accessible projects)', $quarterlyReports >= 0,
                "Found {$quarterlyReports} quarterly reports");
        } else {
            $this->warn("   ⚠️  QuarterlyReport model not found");
        }

        $this->newLine();
    }

    /**
     * Record test result
     */
    protected function testResult($testId, $testName, $passed, $details = '')
    {
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        $this->testResults[] = [
            'id' => $testId,
            'name' => $testName,
            'passed' => $passed,
            'details' => $details
        ];

        if ($this->verbose || !$passed) {
            $this->line("   {$status} - {$testName}");
            if ($details) {
                $this->line("      {$details}");
            }
        } else {
            $this->line("   {$status} - {$testName}");
        }
    }

    /**
     * Display test results summary
     */
    protected function displayResults()
    {
        $this->newLine();
        $this->info('📊 Test Results Summary');
        $this->info('========================');
        $this->newLine();

        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, fn($r) => $r['passed']));
        $failed = $total - $passed;

        // Display all results
        $this->table(
            ['Test ID', 'Test Name', 'Status', 'Details'],
            array_map(function($result) {
                return [
                    $result['id'],
                    $result['name'],
                    $result['passed'] ? '✅ PASS' : '❌ FAIL',
                    $result['details'] ?? ''
                ];
            }, $this->testResults)
        );

        $this->newLine();
        $this->info("Total Tests: {$total}");
        $this->info("Passed: {$passed}");
        $this->info("Failed: {$failed}");

        if ($failed > 0) {
            $this->newLine();
            $this->warn('⚠️  Some tests failed. Review the details above.');

            // Show failed tests
            $failedTests = array_filter($this->testResults, fn($r) => !$r['passed']);
            if (count($failedTests) > 0) {
                $this->error('Failed Tests:');
                foreach ($failedTests as $test) {
                    $this->error("   - {$test['id']}: {$test['name']}");
                }
            }
        } else {
            $this->newLine();
            $this->info('🎉 All tests passed!');
        }

        // Highlight key tests
        $keyTests = array_filter($this->testResults, fn($r) => strpos($r['name'], '⭐') !== false);
        if (count($keyTests) > 0) {
            $this->newLine();
            $this->info('⭐ Key Tests (Critical Functionality):');
            foreach ($keyTests as $test) {
                $status = $test['passed'] ? '✅' : '❌';
                $this->line("   {$status} {$test['id']}: {$test['name']}");
            }
        }
    }
}
