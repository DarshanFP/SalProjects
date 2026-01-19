-- Quick Test Setup SQL Queries
-- Use these queries to set up test data for applicant access testing

-- ============================================
-- 1. CHECK EXISTING APPLICANT USERS
-- ============================================
SELECT id, name, email, role, province
FROM users
WHERE role = 'applicant';

-- ============================================
-- 2. CHECK EXISTING EXECUTOR USERS
-- ============================================
SELECT id, name, email, role, province
FROM users
WHERE role = 'executor';

-- ============================================
-- 3. FIND PROJECTS WHERE USER IS OWNER
-- ============================================
-- Replace [USER_ID] with actual applicant user ID
SELECT project_id, project_title, user_id, in_charge, status
FROM projects
WHERE user_id = [USER_ID];

-- ============================================
-- 4. FIND PROJECTS WHERE USER IS IN-CHARGE (BUT NOT OWNER)
-- ============================================
-- Replace [USER_ID] with actual applicant user ID
SELECT project_id, project_title, user_id, in_charge, status
FROM projects
WHERE in_charge = [USER_ID]
  AND user_id != [USER_ID];

-- ============================================
-- 5. FIND PROJECTS WHERE USER IS BOTH OWNER AND IN-CHARGE
-- ============================================
-- Replace [USER_ID] with actual applicant user ID
SELECT project_id, project_title, user_id, in_charge, status
FROM projects
WHERE user_id = [USER_ID]
  AND in_charge = [USER_ID];

-- ============================================
-- 6. SET UP TEST PROJECT: Make applicant in-charge of existing project
-- ============================================
-- Replace [PROJECT_ID] with actual project_id
-- Replace [APPLICANT_USER_ID] with actual applicant user ID
UPDATE projects
SET in_charge = [APPLICANT_USER_ID]
WHERE project_id = '[PROJECT_ID]'
  AND user_id != [APPLICANT_USER_ID];  -- Ensure applicant is not the owner

-- ============================================
-- 7. VERIFY TEST SETUP: Check all projects for an applicant
-- ============================================
-- Replace [APPLICANT_USER_ID] with actual applicant user ID
SELECT
    project_id,
    project_title,
    user_id,
    in_charge,
    status,
    CASE
        WHEN user_id = [APPLICANT_USER_ID] AND in_charge = [APPLICANT_USER_ID] THEN 'Owner & In-Charge'
        WHEN user_id = [APPLICANT_USER_ID] THEN 'Owner Only'
        WHEN in_charge = [APPLICANT_USER_ID] THEN 'In-Charge Only'
        ELSE 'No Access'
    END AS access_type
FROM projects
WHERE user_id = [APPLICANT_USER_ID]
   OR in_charge = [APPLICANT_USER_ID];

-- ============================================
-- 8. CHECK REPORTS FOR TEST PROJECTS
-- ============================================
-- Replace [PROJECT_ID] with actual project_id
SELECT report_id, project_id, user_id, status, created_at
FROM DP_Reports
WHERE project_id = '[PROJECT_ID]'
ORDER BY created_at DESC;

-- ============================================
-- 9. FIND APPROVED PROJECTS FOR APPLICANT
-- ============================================
-- Replace [APPLICANT_USER_ID] with actual applicant user ID
SELECT project_id, project_title, status
FROM projects
WHERE (user_id = [APPLICANT_USER_ID] OR in_charge = [APPLICANT_USER_ID])
  AND status = 'approved_by_coordinator';

-- ============================================
-- 10. FIND DRAFT PROJECTS FOR APPLICANT (EDITABLE)
-- ============================================
-- Replace [APPLICANT_USER_ID] with actual applicant user ID
SELECT project_id, project_title, status
FROM projects
WHERE (user_id = [APPLICANT_USER_ID] OR in_charge = [APPLICANT_USER_ID])
  AND status = 'draft';

-- ============================================
-- 11. COUNT PROJECTS BY ACCESS TYPE FOR APPLICANT
-- ============================================
-- Replace [APPLICANT_USER_ID] with actual applicant user ID
SELECT
    CASE
        WHEN user_id = [APPLICANT_USER_ID] AND in_charge = [APPLICANT_USER_ID] THEN 'Owner & In-Charge'
        WHEN user_id = [APPLICANT_USER_ID] THEN 'Owner Only'
        WHEN in_charge = [APPLICANT_USER_ID] THEN 'In-Charge Only'
    END AS access_type,
    COUNT(*) as project_count,
    GROUP_CONCAT(project_id) as project_ids
FROM projects
WHERE user_id = [APPLICANT_USER_ID]
   OR in_charge = [APPLICANT_USER_ID]
GROUP BY access_type;

-- ============================================
-- 12. CLEANUP: Remove test in-charge assignment
-- ============================================
-- Use this to revert test changes if needed
-- Replace [PROJECT_ID] with actual project_id
-- Replace [ORIGINAL_IN_CHARGE_ID] with original in-charge user ID
-- UPDATE projects
-- SET in_charge = [ORIGINAL_IN_CHARGE_ID]
-- WHERE project_id = '[PROJECT_ID]';
