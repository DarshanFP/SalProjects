<?php

namespace App\Constants;

class ProjectType
{
    // Institutional Project Types
    const CHILD_CARE_INSTITUTION = 'CHILD CARE INSTITUTION';
    const DEVELOPMENT_PROJECTS = 'Development Projects';
    const RURAL_URBAN_TRIBAL = 'Rural-Urban-Tribal';
    const INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL = 'Institutional Ongoing Group Educational proposal';
    const LIVELIHOOD_DEVELOPMENT_PROJECTS = 'Livelihood Development Projects';
    const CRISIS_INTERVENTION_CENTER = 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER';
    const NEXT_PHASE_DEVELOPMENT_PROPOSAL = 'NEXT PHASE - DEVELOPMENT PROPOSAL';
    const RESIDENTIAL_SKILL_TRAINING = 'Residential Skill Training Proposal 2';

    // Individual Project Types
    const INDIVIDUAL_ONGOING_EDUCATIONAL = 'Individual - Ongoing Educational support';
    const INDIVIDUAL_LIVELIHOOD_APPLICATION = 'Individual - Livelihood Application';
    const INDIVIDUAL_ACCESS_TO_HEALTH = 'Individual - Access to Health';
    const INDIVIDUAL_INITIAL_EDUCATIONAL = 'Individual - Initial - Educational support';

    /**
     * Get all institutional project types
     */
    public static function getInstitutionalTypes(): array
    {
        return [
            self::CHILD_CARE_INSTITUTION,
            self::DEVELOPMENT_PROJECTS,
            self::RURAL_URBAN_TRIBAL,
            self::INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL,
            self::LIVELIHOOD_DEVELOPMENT_PROJECTS,
            self::CRISIS_INTERVENTION_CENTER,
            self::NEXT_PHASE_DEVELOPMENT_PROPOSAL,
            self::RESIDENTIAL_SKILL_TRAINING,
        ];
    }

    /**
     * Get all individual project types
     */
    public static function getIndividualTypes(): array
    {
        return [
            self::INDIVIDUAL_ONGOING_EDUCATIONAL,
            self::INDIVIDUAL_LIVELIHOOD_APPLICATION,
            self::INDIVIDUAL_ACCESS_TO_HEALTH,
            self::INDIVIDUAL_INITIAL_EDUCATIONAL,
        ];
    }

    /**
     * Get all project types
     */
    public static function all(): array
    {
        return array_merge(
            self::getInstitutionalTypes(),
            self::getIndividualTypes()
        );
    }

    /**
     * Check if project type is institutional
     */
    public static function isInstitutional(string $projectType): bool
    {
        return in_array($projectType, self::getInstitutionalTypes());
    }

    /**
     * Check if project type is individual
     */
    public static function isIndividual(string $projectType): bool
    {
        return in_array($projectType, self::getIndividualTypes());
    }

    /**
     * Get project types that require predecessor projects
     */
    public static function getTypesRequiringPredecessor(): array
    {
        return [
            self::DEVELOPMENT_PROJECTS,
            self::NEXT_PHASE_DEVELOPMENT_PROPOSAL,
        ];
    }

    /**
     * Check if project type requires predecessor
     */
    public static function requiresPredecessor(string $projectType): bool
    {
        return in_array($projectType, self::getTypesRequiringPredecessor());
    }
}

