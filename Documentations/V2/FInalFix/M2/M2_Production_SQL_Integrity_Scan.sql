-- =============================================================================
-- M2 Production SQL Integrity Scan
-- Milestone: M2 — Validation & Schema Alignment
-- READ-ONLY. No destructive commands. Safe for production.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- STEP 1 — Actual table names (from migrations; models confirm via $table)
-- -----------------------------------------------------------------------------
--  1) projects
--  2) project_objectives
--  3) project_results
--  4) project_risks
--  5) project_activities
--  6) project_timeframes
--  7) IES family working members  → project_IES_family_working_members
--  8) IAH earning members         → project_IAH_earning_members
--  9) IES expense details         → project_IES_expense_details (parent: project_IES_expenses)
-- 10) IAH budget details          → project_IAH_budget_details
-- -----------------------------------------------------------------------------
-- STEP 2 — Column names used in this scan (migrations + rename migrations)
-- -----------------------------------------------------------------------------
-- projects                      : in_charge, overall_project_budget, project_id
-- project_objectives            : objective, objective_id, project_id (description→objective)
-- project_results               : result, result_id, objective_id (outcome→result)
-- project_risks                  : risk, risk_id, objective_id (description→risk)
-- project_activities            : activity, verification, activity_id, objective_id (description→activity)
-- project_timeframes            : month, is_active, timeframe_id, activity_id
-- project_IES_family_working_members : monthly_income, IES_family_member_id, project_id
-- project_IAH_earning_members   : monthly_income, IAH_earning_id, project_id
-- project_IES_expense_details   : amount, IES_expense_id, particular
-- project_IAH_budget_details    : amount, IAH_budget_id, project_id, particular
-- -----------------------------------------------------------------------------


-- =============================================================================
-- A) NOT NULL CHECKS — Rows where NOT NULL columns contain NULL (violations)
-- =============================================================================

-- A1) projects: in_charge and overall_project_budget are NOT NULL (in_charge no default; overall_project_budget has default 0.00)
SELECT 'A1_projects_in_charge_NULL' AS check_name, project_id, id
FROM projects
WHERE in_charge IS NULL;

SELECT 'A2_projects_overall_budget_NULL' AS check_name, project_id, id
FROM projects
WHERE overall_project_budget IS NULL;

-- A3) project_objectives: objective is NOT NULL (after rename from description)
SELECT 'A3_project_objectives_objective_NULL' AS check_name, id, objective_id, project_id
FROM project_objectives
WHERE objective IS NULL OR TRIM(objective) = '';

-- A4) project_results: result is NOT NULL (after rename from outcome)
SELECT 'A4_project_results_result_NULL' AS check_name, id, result_id, objective_id
FROM project_results
WHERE result IS NULL OR TRIM(result) = '';

-- A5) project_risks: risk is NOT NULL (after rename from description)
SELECT 'A5_project_risks_risk_NULL' AS check_name, id, risk_id, objective_id
FROM project_risks
WHERE risk IS NULL OR TRIM(risk) = '';

-- A6) project_activities: activity and verification are NOT NULL
SELECT 'A6_project_activities_activity_NULL' AS check_name, id, activity_id, objective_id
FROM project_activities
WHERE activity IS NULL OR TRIM(activity) = '';

SELECT 'A7_project_activities_verification_NULL' AS check_name, id, activity_id, objective_id
FROM project_activities
WHERE verification IS NULL;

-- A8) project_timeframes: month and is_active are NOT NULL
SELECT 'A8_project_timeframes_month_NULL' AS check_name, id, timeframe_id, activity_id
FROM project_timeframes
WHERE month IS NULL OR TRIM(month) = '';

SELECT 'A9_project_timeframes_is_active_NULL' AS check_name, id, timeframe_id, activity_id
FROM project_timeframes
WHERE is_active IS NULL;


-- =============================================================================
-- B) ORPHAN RELATIONSHIP CHECKS — Child rows referencing missing parents
-- =============================================================================

-- B1) project_objectives with project_id not in projects
SELECT 'B1_objectives_orphan_project' AS check_name, o.id, o.objective_id, o.project_id
FROM project_objectives o
LEFT JOIN projects p ON p.project_id = o.project_id
WHERE p.project_id IS NULL;

-- B2) project_results with objective_id not in project_objectives
SELECT 'B2_results_orphan_objective' AS check_name, r.id, r.result_id, r.objective_id
FROM project_results r
LEFT JOIN project_objectives o ON o.objective_id = r.objective_id
WHERE o.objective_id IS NULL;

-- B3) project_risks with objective_id not in project_objectives
SELECT 'B3_risks_orphan_objective' AS check_name, r.id, r.risk_id, r.objective_id
FROM project_risks r
LEFT JOIN project_objectives o ON o.objective_id = r.objective_id
WHERE o.objective_id IS NULL;

-- B4) project_activities with objective_id not in project_objectives
SELECT 'B4_activities_orphan_objective' AS check_name, a.id, a.activity_id, a.objective_id
FROM project_activities a
LEFT JOIN project_objectives o ON o.objective_id = a.objective_id
WHERE o.objective_id IS NULL;

-- B5) project_timeframes with activity_id not in project_activities
SELECT 'B5_timeframes_orphan_activity' AS check_name, t.id, t.timeframe_id, t.activity_id
FROM project_timeframes t
LEFT JOIN project_activities a ON a.activity_id = t.activity_id
WHERE a.activity_id IS NULL;

-- B6) project_IES_family_working_members with project_id not in projects
SELECT 'B6_IES_family_orphan_project' AS check_name, m.id, m.IES_family_member_id, m.project_id
FROM project_IES_family_working_members m
LEFT JOIN projects p ON p.project_id = m.project_id
WHERE p.project_id IS NULL;

-- B7) project_IAH_earning_members with project_id not in projects
SELECT 'B7_IAH_earning_orphan_project' AS check_name, e.id, e.IAH_earning_id, e.project_id
FROM project_IAH_earning_members e
LEFT JOIN projects p ON p.project_id = e.project_id
WHERE p.project_id IS NULL;

-- B8) project_IES_expense_details with IES_expense_id not in project_IES_expenses
SELECT 'B8_IES_expense_details_orphan_expense' AS check_name, d.id, d.IES_expense_id
FROM project_IES_expense_details d
LEFT JOIN project_IES_expenses e ON e.IES_expense_id = d.IES_expense_id
WHERE e.IES_expense_id IS NULL;

-- B9) project_IAH_budget_details with project_id not in projects
SELECT 'B9_IAH_budget_details_orphan_project' AS check_name, b.id, b.IAH_budget_id, b.project_id
FROM project_IAH_budget_details b
LEFT JOIN projects p ON p.project_id = b.project_id
WHERE p.project_id IS NULL;


-- =============================================================================
-- C) NUMERIC ZERO EXISTENCE — Rows where amount/monthly_income = 0 (M2.5 allows 0)
-- =============================================================================

-- C1) IES family working members with monthly_income = 0 (should exist after M2.5; informational)
SELECT 'C1_IES_family_monthly_income_zero' AS check_name, id, IES_family_member_id, project_id, monthly_income
FROM project_IES_family_working_members
WHERE monthly_income = 0 OR monthly_income = 0.00;

-- C2) IAH earning members with monthly_income = 0 (informational)
SELECT 'C2_IAH_earning_monthly_income_zero' AS check_name, id, IAH_earning_id, project_id, monthly_income
FROM project_IAH_earning_members
WHERE monthly_income = 0 OR monthly_income = 0.00;

-- C3) IES expense details with amount = 0 (informational)
SELECT 'C3_IES_expense_detail_amount_zero' AS check_name, id, IES_expense_id, particular, amount
FROM project_IES_expense_details
WHERE amount = 0 OR amount = 0.00;

-- C4) IAH budget details with amount = 0 (informational)
SELECT 'C4_IAH_budget_detail_amount_zero' AS check_name, id, IAH_budget_id, project_id, particular, amount
FROM project_IAH_budget_details
WHERE amount = 0 OR amount = 0.00;


-- =============================================================================
-- D) NUMERIC NULL CHECKS — Numeric columns that are NULL (audit only; some columns are nullable)
-- =============================================================================

-- D1) projects.overall_project_budget NULL (should be 0 if not set; NOT NULL in schema with default)
SELECT 'D1_projects_overall_budget_NULL' AS check_name, project_id, id, overall_project_budget
FROM projects
WHERE overall_project_budget IS NULL;

-- D2) project_IES_family_working_members.monthly_income NULL (nullable; audit for data quality)
SELECT 'D2_IES_family_monthly_income_NULL' AS check_name, id, IES_family_member_id, project_id
FROM project_IES_family_working_members
WHERE monthly_income IS NULL;

-- D3) project_IAH_earning_members.monthly_income NULL (nullable; audit)
SELECT 'D3_IAH_earning_monthly_income_NULL' AS check_name, id, IAH_earning_id, project_id
FROM project_IAH_earning_members
WHERE monthly_income IS NULL;

-- D4) project_IES_expense_details.amount NULL (column is decimal NOT NULL in migration; violation if any)
SELECT 'D4_IES_expense_detail_amount_NULL' AS check_name, id, IES_expense_id, particular
FROM project_IES_expense_details
WHERE amount IS NULL;

-- D5) project_IAH_budget_details.amount NULL (nullable in migration; audit)
SELECT 'D5_IAH_budget_detail_amount_NULL' AS check_name, id, IAH_budget_id, project_id, particular
FROM project_IAH_budget_details
WHERE amount IS NULL;


-- =============================================================================
-- END OF M2 PRODUCTION SQL INTEGRITY SCAN
-- =============================================================================
