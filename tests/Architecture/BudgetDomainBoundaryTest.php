<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Phase 2.4 — Budget Domain Boundary Enforcement Test
 *
 * Prevents reintroduction of arithmetic inside models, controllers, or JS
 * outside the canonical calculation modules (DerivedCalculationService, budget-calculations.js).
 */
class BudgetDomainBoundaryTest extends TestCase
{
    private string $basePath;

    private array $violations = [];

    /** @var array<string, string> Pattern => description */
    private const PHP_PATTERNS = [
        'rate_quantity *' => 'rate_quantity multiplication',
        'rate_multiplier *' => 'rate_multiplier multiplication',
        'rate_duration *' => 'rate_duration multiplication',
        '* rate_quantity' => 'multiplication by rate_quantity',
        '* rate_multiplier' => 'multiplication by rate_multiplier',
        '* rate_duration' => 'multiplication by rate_duration',
        "->sum('this_phase')" => 'sum(this_phase)',
        '->sum("this_phase")' => 'sum(this_phase)',
        "->sum('next_phase')" => 'sum(next_phase)',
        '->sum("next_phase")' => 'sum(next_phase)',
        'array_sum(' => 'array_sum',
        '$total +=' => 'manual total accumulation',
    ];

    /** @var array<string, string> Pattern => description */
    private const JS_PATTERNS = [
        '* rateQuantity' => 'rateQuantity multiplication',
        '* rateMultiplier' => 'rateMultiplier multiplication',
        '* rateDuration' => 'rateDuration multiplication',
        'total +=' => 'total accumulation',
        'parseFloat(' => 'parseFloat for arithmetic (check for parseFloat * parseFloat)',
    ];

    private const PHP_SCAN_DIRS = [
        'app/Models',
        'app/Http/Controllers',
        'app/Services',
    ];

    private const JS_SCAN_DIRS = [
        'resources/js',
        'resources/views',
    ];

    /** Paths excluded from PHP scan (allowed files) */
    private const PHP_EXCLUDE_PATHS = [
        'app/Services/Budget/DerivedCalculationService.php',
        'vendor',
        'tests',
        'storage',
    ];

    /** Paths excluded from JS scan */
    private const JS_EXCLUDE_PATHS = [
        'budget-calculations.js',
        'vendor',
        'storage',
    ];

    /**
     * PHP files with known non-budget arithmetic (array_sum, etc.).
     * Audited: these use array_sum/$total for non-budget-domain purposes.
     */
    private const PHP_EXCLUDE_FILES = [
        'app/Services/BudgetValidationService.php',
        'app/Services/Budget/BudgetCalculationService.php',
        'app/Services/Numeric/BoundedNumericService.php',
        'app/Http/Controllers/CoordinatorController.php',
        'app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php',
        'app/Http/Controllers/ExecutorController.php',
        'app/Http/Controllers/Reports/Quarterly/InstitutionalSupportController.php',
    ];

    /**
     * JS files with non-standard budget arithmetic (ILP cost fields, report statements).
     */
    private const JS_EXCLUDE_FILES = [
        'resources/views/projects/partials/ILP/budget.blade.php',
        'resources/views/projects/partials/Edit/ILP/budget.blade.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 2) . '/';
    }

    public function test_no_budget_arithmetic_in_php_outside_derived_calculation_service(): void
    {
        $this->violations = [];

        foreach (self::PHP_SCAN_DIRS as $dir) {
            $fullPath = $this->basePath . $dir;
            if (! is_dir($fullPath)) {
                continue;
            }
            $this->scanPhpDirectory($fullPath);
        }

        $messages = array_map(
            fn (array $v) => "{$v['file']}:{$v['line']} — {$v['pattern']}",
            $this->violations
        );

        $this->assertEmpty(
            $this->violations,
            "Budget arithmetic detected in PHP outside DerivedCalculationService:\n" . implode("\n", $messages)
        );
    }

    public function test_no_budget_arithmetic_in_js_outside_budget_calculations_module(): void
    {
        $this->violations = [];

        foreach (self::JS_SCAN_DIRS as $dir) {
            $fullPath = $this->basePath . $dir;
            if (! is_dir($fullPath)) {
                continue;
            }
            $this->scanJsDirectory($fullPath);
        }

        $messages = array_map(
            fn (array $v) => "{$v['file']}:{$v['line']} — {$v['pattern']}",
            $this->violations
        );

        $this->assertEmpty(
            $this->violations,
            "Budget arithmetic detected in JS outside budget-calculations.js:\n" . implode("\n", $messages)
        );
    }

    private function scanPhpDirectory(string $fullPath): void
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

            if ($this->shouldExcludePhp($normalized)) {
                continue;
            }

            $this->scanPhpFile($path, $normalized);
        }
    }

    private function shouldExcludePhp(string $normalized): bool
    {
        foreach (self::PHP_EXCLUDE_PATHS as $exclude) {
            if (str_starts_with($normalized, $exclude)) {
                return true;
            }
        }

        foreach (self::PHP_EXCLUDE_FILES as $excludeFile) {
            if ($normalized === $excludeFile) {
                return true;
            }
        }

        return false;
    }

    private function scanPhpFile(string $path, string $relativePath): void
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

            foreach (self::PHP_PATTERNS as $pattern => $description) {
                if (str_contains($line, $pattern)) {
                    $this->violations[] = [
                        'file' => $relativePath,
                        'line' => $lineNumber,
                        'pattern' => $description,
                    ];
                }
            }
        }
    }

    private function scanJsDirectory(string $fullPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            $relativePath = str_replace($this->basePath, '', $path);
            $normalized = str_replace('\\', '/', $relativePath);

            $ext = $file->getExtension();
            $name = $file->getFilename();
            $isJs = $ext === 'js' || $ext === 'blade' || $ext === 'vue' || str_ends_with($name, '.blade.php');

            if (! $isJs) {
                continue;
            }

            if ($this->shouldExcludeJs($normalized, $name)) {
                continue;
            }

            $this->scanJsFile($path, $normalized);
        }
    }

    private function shouldExcludeJs(string $normalized, string $filename): bool
    {
        foreach (self::JS_EXCLUDE_PATHS as $exclude) {
            if (str_contains($normalized, $exclude) || $filename === $exclude) {
                return true;
            }
        }

        foreach (self::JS_EXCLUDE_FILES as $excludeFile) {
            if ($normalized === $excludeFile) {
                return true;
            }
        }

        return false;
    }

    private function scanJsFile(string $path, string $relativePath): void
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

            foreach (self::JS_PATTERNS as $pattern => $description) {
                if (str_contains($line, $pattern)) {
                    if ($pattern === 'parseFloat(') {
                        if (! str_contains($line, 'parseFloat') || ! str_contains($line, '*')) {
                            continue;
                        }
                        if (preg_match('/parseFloat\s*\([^)]*\)\s*\*\s*parseFloat\s*\(/s', $line)) {
                            $this->violations[] = [
                                'file' => $relativePath,
                                'line' => $lineNumber,
                                'pattern' => 'parseFloat(...) * parseFloat(...)',
                            ];
                        }
                        continue;
                    }
                    $this->violations[] = [
                        'file' => $relativePath,
                        'line' => $lineNumber,
                        'pattern' => $description,
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
        if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#')) {
            return true;
        }
        if (str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '* ') || str_starts_with($trimmed, '**')) {
            return true;
        }
        if (str_starts_with($trimmed, '<!--') || str_starts_with($trimmed, '{{--')) {
            return true;
        }
        return false;
    }
}
