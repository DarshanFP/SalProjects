<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Architectural Guard Test: Budget Domain Isolation
 *
 * Ensures that budget arithmetic (multiplication of rate_quantity/rate_multiplier/rate_duration,
 * summation of phase totals, budget subtraction) exists ONLY in DerivedCalculationService.
 * Also enforces container-based resolution of DerivedCalculationService (no direct "new").
 */
class BudgetDomainIsolationTest extends TestCase
{
    private string $basePath;

    private array $violations = [];

    private const PATTERNS = [
        'rate_quantity *' => 'rate_quantity multiplication',
        'rate_multiplier *' => 'rate_multiplier multiplication',
        'rate_duration *' => 'rate_duration multiplication',
        "->sum('this_phase')" => 'sum(this_phase)',
        '->sum("this_phase")' => 'sum(this_phase)',
        "->sum('next_phase')" => 'sum(next_phase)',
        '->sum("next_phase")' => 'sum(next_phase)',
        '$total +=' => 'manual total accumulation',
        'array_sum(' => 'array_sum',
        '* $this->rate_quantity' => 'rate_quantity property multiplication',
        '* $this->rate_multiplier' => 'rate_multiplier property multiplication',
        '* $this->rate_duration' => 'rate_duration property multiplication',
    ];

    private const SCAN_DIRS = [
        'app/Models',
        'app/Http/Controllers',
        'app/Services',
    ];

    private const EXCLUDE_PATHS = [
        'app/Services/Budget',           // Entire Budget folder (contains DerivedCalculationService)
        'vendor',
        'tests',
    ];

    /**
     * Files excluded from scanning: validation layer and non-budget array_sum usage.
     * Remove entries as those areas are refactored to use DerivedCalculationService.
     */
    private const EXCLUDE_FILES = [
        'app/Services/BudgetValidationService.php',                    // Validation layer (->sum('this_phase'))
        'app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php', // array_sum for expense amounts
        'app/Http/Controllers/CoordinatorController.php',              // array_sum for performance scores
        'app/Http/Controllers/ExecutorController.php',                 // array_sum for dashboard aggregation
        'app/Http/Controllers/Reports/Quarterly/InstitutionalSupportController.php', // array_sum for report totals
    ];

    /**
     * Files allowed to use "new DerivedCalculationService()" (unit tests only).
     */
    private const ALLOWED_DIRECT_INSTANTIATION = [
        'tests/Unit/Budget/DerivedCalculationServiceTest.php',
        'tests/Architecture/BudgetDomainIsolationTest.php',
    ];

    private const INSTANTIATION_PATTERNS = [
        'new DerivedCalculationService(',
        'new \App\Services\Budget\DerivedCalculationService(',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 2) . '/';
    }

    public function test_no_budget_arithmetic_outside_derived_calculation_service(): void
    {
        $this->violations = [];

        foreach (self::SCAN_DIRS as $dir) {
            $fullPath = $this->basePath . $dir;
            if (! is_dir($fullPath)) {
                continue;
            }
            $this->scanDirectory($fullPath, $dir);
        }

        $messages = array_map(
            fn (array $v) => "Budget arithmetic detected outside DerivedCalculationService at: {$v['file']}:{$v['line']}",
            $this->violations
        );

        $this->assertEmpty(
            $this->violations,
            "Budget arithmetic detected outside DerivedCalculationService:\n" . implode("\n", $messages)
        );
    }

    public function test_no_direct_instantiation_of_derived_calculation_service(): void
    {
        $this->violations = [];
        $scanDirs = array_merge(self::SCAN_DIRS, ['tests']);

        foreach ($scanDirs as $dir) {
            $fullPath = $this->basePath . $dir;
            if (! is_dir($fullPath)) {
                continue;
            }
            $this->scanDirectoryForInstantiation($fullPath);
        }

        $messages = array_map(
            fn (array $v) => "Direct instantiation of DerivedCalculationService is forbidden. Use container resolution. at: {$v['file']}:{$v['line']}",
            $this->violations
        );

        $this->assertEmpty(
            $this->violations,
            "Direct instantiation of DerivedCalculationService is forbidden. Use container resolution.\n" . implode("\n", $messages)
        );
    }

    private function scanDirectoryForInstantiation(string $fullPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $relativePath = str_replace($this->basePath, '', $path);
            $normalized = str_replace('\\', '/', $relativePath);

            if (str_starts_with($normalized, 'vendor')) {
                continue;
            }

            if (in_array($normalized, self::ALLOWED_DIRECT_INSTANTIATION, true)) {
                continue;
            }

            $this->scanFileForInstantiation($path, $normalized);
        }
    }

    private function scanFileForInstantiation(string $path, string $relativePath): void
    {
        $lines = @file($path);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $lineNum => $line) {
            $lineNumber = $lineNum + 1;
            $trimmed = trim($line);

            if ($this->isCommentOnlyLine($trimmed)) {
                continue;
            }

            foreach (self::INSTANTIATION_PATTERNS as $pattern) {
                if (str_contains($line, $pattern)) {
                    $this->violations[] = [
                        'file' => $relativePath,
                        'line' => $lineNumber,
                        'pattern' => $pattern,
                    ];
                }
            }
        }
    }

    private function scanDirectory(string $fullPath, string $relativePrefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $relativePath = str_replace($this->basePath, '', $path);

            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            $this->scanFile($path, $relativePath);
        }
    }

    private function shouldExclude(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', $relativePath);

        foreach (self::EXCLUDE_PATHS as $exclude) {
            if (str_starts_with($normalized, $exclude)) {
                return true;
            }
        }

        foreach (self::EXCLUDE_FILES as $excludeFile) {
            if ($normalized === $excludeFile) {
                return true;
            }
        }

        return false;
    }

    private function scanFile(string $path, string $relativePath): void
    {
        $lines = @file($path);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $lineNum => $line) {
            $lineNumber = $lineNum + 1;
            $trimmed = trim($line);

            if ($this->isCommentOnlyLine($trimmed)) {
                continue;
            }

            foreach (self::PATTERNS as $pattern => $description) {
                if (str_contains($line, $pattern)) {
                    $this->violations[] = [
                        'file' => $relativePath,
                        'line' => $lineNumber,
                        'pattern' => $pattern,
                    ];
                }
            }
        }
    }

    private function isCommentOnlyLine(string $trimmed): bool
    {
        if ($trimmed === '') {
            return true;
        }

        if (str_starts_with($trimmed, '//')) {
            return true;
        }

        if (str_starts_with($trimmed, '#')) {
            return true;
        }

        if (str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*')) {
            return true;
        }

        if (str_starts_with($trimmed, '/**')) {
            return true;
        }

        return false;
    }
}
