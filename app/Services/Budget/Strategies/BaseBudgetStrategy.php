<?php

namespace App\Services\Budget\Strategies;

use App\Models\OldProjects\Project;
use Illuminate\Support\Collection;

/**
 * Base abstract class for budget calculation strategies
 *
 * Provides common functionality and configuration loading
 */
abstract class BaseBudgetStrategy implements BudgetCalculationStrategyInterface
{
    /**
     * Project type this strategy handles
     *
     * @var string
     */
    protected string $projectType;

    /**
     * Configuration for this project type
     *
     * @var array
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param string $projectType The project type this strategy handles
     */
    public function __construct(string $projectType)
    {
        $this->projectType = $projectType;
        $this->loadConfiguration();
    }

    /**
     * Load configuration for this project type
     *
     * @return void
     * @throws \RuntimeException If configuration not found
     */
    protected function loadConfiguration(): void
    {
        $config = config("budget.field_mappings.{$this->projectType}");

        if (!$config) {
            throw new \RuntimeException("Budget configuration not found for project type: {$this->projectType}");
        }

        $this->config = $config;
    }

    /**
     * Get the project type this strategy handles
     *
     * @return string
     */
    public function getProjectType(): string
    {
        return $this->projectType;
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Get field mapping
     *
     * @param string $field Standard field name (particular, amount, contribution, id)
     * @return string|null Actual field name in database
     */
    protected function getFieldMapping(string $field): ?string
    {
        return $this->config['fields'][$field] ?? null;
    }

    /**
     * Check if this project type is phase-based
     *
     * @return bool
     */
    protected function isPhaseBased(): bool
    {
        return $this->getConfig('phase_based', false);
    }

    /**
     * Get phase selection method
     *
     * @return string 'current' or 'highest'
     */
    protected function getPhaseSelection(): string
    {
        return $this->getConfig('phase_selection', 'current');
    }
}
