<?php

namespace App\Constants;

class ProjectStatus
{
    // Draft and editable statuses
    const DRAFT = 'draft';
    const REVERTED_BY_PROVINCIAL = 'reverted_by_provincial';
    const REVERTED_BY_COORDINATOR = 'reverted_by_coordinator';

    // Submission statuses
    const SUBMITTED_TO_PROVINCIAL = 'submitted_to_provincial';
    const FORWARDED_TO_COORDINATOR = 'forwarded_to_coordinator';
    const APPROVED_BY_COORDINATOR = 'approved_by_coordinator';
    const REJECTED_BY_COORDINATOR = 'rejected_by_coordinator';

    // General user acting as Coordinator
    const APPROVED_BY_GENERAL_AS_COORDINATOR = 'approved_by_general_as_coordinator';
    const REVERTED_BY_GENERAL_AS_COORDINATOR = 'reverted_by_general_as_coordinator';

    // General user acting as Provincial
    const APPROVED_BY_GENERAL_AS_PROVINCIAL = 'approved_by_general_as_provincial';
    const REVERTED_BY_GENERAL_AS_PROVINCIAL = 'reverted_by_general_as_provincial';

    // Final/rejection (never editable by any role)
    const REJECTED_BY_GENERAL = 'rejected_by_general';

    // Granular revert statuses (revert to specific levels)
    const REVERTED_TO_EXECUTOR = 'reverted_to_executor';
    const REVERTED_TO_APPLICANT = 'reverted_to_applicant';
    const REVERTED_TO_PROVINCIAL = 'reverted_to_provincial';
    const REVERTED_TO_COORDINATOR = 'reverted_to_coordinator';

    /**
     * Approved statuses (M3.3.2: centralized for financial aggregation)
     */
    public const APPROVED_STATUSES = [
        self::APPROVED_BY_COORDINATOR,
        self::APPROVED_BY_GENERAL_AS_COORDINATOR,
        self::APPROVED_BY_GENERAL_AS_PROVINCIAL,
    ];

    /**
     * Final statuses (Wave 5F): never editable by any role.
     * Protects approval integrity and financial immutability.
     */
    public const FINAL_STATUSES = [
        self::APPROVED_BY_COORDINATOR,
        self::APPROVED_BY_GENERAL_AS_COORDINATOR,
        self::APPROVED_BY_GENERAL_AS_PROVINCIAL,
        self::REJECTED_BY_COORDINATOR,
        self::REJECTED_BY_GENERAL,
    ];

    /**
     * Get all editable statuses
     */
    public static function getEditableStatuses(): array
    {
        return [
            self::DRAFT,
            self::REVERTED_BY_PROVINCIAL,
            self::REVERTED_BY_COORDINATOR,
            self::REVERTED_BY_GENERAL_AS_PROVINCIAL,
            self::REVERTED_BY_GENERAL_AS_COORDINATOR,
            self::REVERTED_TO_EXECUTOR,
            self::REVERTED_TO_APPLICANT,
            self::REVERTED_TO_PROVINCIAL,
            self::REVERTED_TO_COORDINATOR,
        ];
    }

    /**
     * Get all submittable statuses
     */
    public static function getSubmittableStatuses(): array
    {
        return [
            self::DRAFT,
            self::REVERTED_BY_PROVINCIAL,
            self::REVERTED_BY_COORDINATOR,
            self::REVERTED_BY_GENERAL_AS_PROVINCIAL,
            self::REVERTED_BY_GENERAL_AS_COORDINATOR,
            self::REVERTED_TO_EXECUTOR,
            self::REVERTED_TO_APPLICANT,
            self::REVERTED_TO_PROVINCIAL,
            self::REVERTED_TO_COORDINATOR,
        ];
    }

    /**
     * Check if status is editable
     */
    public static function isEditable(string $status): bool
    {
        return in_array($status, self::getEditableStatuses());
    }

    /**
     * Check if status is final (Wave 6D: immutable; no updates allowed).
     */
    public static function isFinal(string $status): bool
    {
        return in_array($status, self::FINAL_STATUSES);
    }

    /**
     * Role-aware edit gate (Wave 5F).
     * Final statuses are never editable. Provincial/Coordinator/Admin/General get broader access; Executor/Applicant restricted to legacy editable statuses.
     */
    public static function canEditForRole(string $status, string $role): bool
    {
        if (in_array($status, self::FINAL_STATUSES)) {
            return false;
        }

        if ($role === 'provincial') {
            return true;
        }

        if ($role === 'coordinator') {
            return true;
        }

        if (in_array($role, ['admin', 'general'])) {
            return true;
        }

        return in_array($status, self::getEditableStatuses());
    }

    /**
     * Check if status is submittable
     */
    public static function isSubmittable(string $status): bool
    {
        return in_array($status, self::getSubmittableStatuses());
    }

    /**
     * Check if status is draft
     */
    public static function isDraft(string $status): bool
    {
        return $status === self::DRAFT;
    }

    /**
     * Check if status is approved (any approval status)
     */
    public static function isApproved(string $status): bool
    {
        return in_array($status, self::APPROVED_STATUSES, true);
    }

    /**
     * Check if status is reverted (any revert status)
     */
    public static function isReverted(string $status): bool
    {
        return in_array($status, [
            self::REVERTED_BY_PROVINCIAL,
            self::REVERTED_BY_COORDINATOR,
            self::REVERTED_BY_GENERAL_AS_PROVINCIAL,
            self::REVERTED_BY_GENERAL_AS_COORDINATOR,
            self::REVERTED_TO_EXECUTOR,
            self::REVERTED_TO_APPLICANT,
            self::REVERTED_TO_PROVINCIAL,
            self::REVERTED_TO_COORDINATOR,
        ]);
    }

    /**
     * Check if status is submitted to provincial
     */
    public static function isSubmittedToProvincial(string $status): bool
    {
        return $status === self::SUBMITTED_TO_PROVINCIAL;
    }

    /**
     * Check if status is forwarded to coordinator
     */
    public static function isForwardedToCoordinator(string $status): bool
    {
        return $status === self::FORWARDED_TO_COORDINATOR;
    }

    /**
     * Check if status is rejected
     */
    public static function isRejected(string $status): bool
    {
        return $status === self::REJECTED_BY_COORDINATOR;
    }

    /**
     * Get all statuses
     */
    public static function all(): array
    {
        return [
            self::DRAFT,
            self::REVERTED_BY_PROVINCIAL,
            self::REVERTED_BY_COORDINATOR,
            self::SUBMITTED_TO_PROVINCIAL,
            self::FORWARDED_TO_COORDINATOR,
            self::APPROVED_BY_COORDINATOR,
            self::REJECTED_BY_COORDINATOR,
            self::APPROVED_BY_GENERAL_AS_COORDINATOR,
            self::REVERTED_BY_GENERAL_AS_COORDINATOR,
            self::APPROVED_BY_GENERAL_AS_PROVINCIAL,
            self::REVERTED_BY_GENERAL_AS_PROVINCIAL,
            self::REVERTED_TO_EXECUTOR,
            self::REVERTED_TO_APPLICANT,
            self::REVERTED_TO_PROVINCIAL,
            self::REVERTED_TO_COORDINATOR,
        ];
    }
}

