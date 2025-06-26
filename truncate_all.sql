-- =====================================================
-- TRUNCATE ALL PROJECT AND REPORT TABLES
-- =====================================================
-- This file contains SQL commands to truncate all tables
-- related to projects and project reports
--
-- WARNING: This will permanently delete all data from these tables!
-- Always backup your database before running this script.
-- =====================================================

-- Disable foreign key checks to avoid constraint issues
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- MAIN PROJECT TABLES
-- =====================================================
TRUNCATE TABLE projects;
TRUNCATE TABLE project_comments;
TRUNCATE TABLE project_attachments;
TRUNCATE TABLE project_budgets;
TRUNCATE TABLE project_objectives;
TRUNCATE TABLE project_results;
TRUNCATE TABLE project_risks;
TRUNCATE TABLE project_sustainabilities;
TRUNCATE TABLE project_activities;
TRUNCATE TABLE project_timeframes;

-- =====================================================
-- CCI (CHILD CARE INSTITUTION) PROJECT TABLES
-- =====================================================
TRUNCATE TABLE project_CCI_rationale;
TRUNCATE TABLE project_CCI_statistics;
TRUNCATE TABLE project_CCI_annexed_target_group;
TRUNCATE TABLE project_CCI_age_profile;
TRUNCATE TABLE project_CCI_personal_situation;
TRUNCATE TABLE project_CCI_economic_background;
TRUNCATE TABLE project_CCI_achievements;
TRUNCATE TABLE project_CCI_present_situation;

-- =====================================================
-- RST (RESIDENTIAL SKILL TRAINING) PROJECT TABLES
-- =====================================================
TRUNCATE TABLE project_RST_institution_info;
TRUNCATE TABLE project_RST_target_group;
TRUNCATE TABLE project_RST_target_group_annexure;
TRUNCATE TABLE project_RST_geographical_areas;
TRUNCATE TABLE project_RST_DP_beneficiaries_area;

-- =====================================================
-- EduRUT (RURAL-URBAN-TRIBAL) PROJECT TABLES
-- =====================================================
TRUNCATE TABLE Project_EduRUT_Basic_Info;
TRUNCATE TABLE project_edu_rut_target_groups;
TRUNCATE TABLE project_edu_rut_annexed_target_groups;

-- =====================================================
-- IES (INDIVIDUAL - ONGOING EDUCATIONAL SUPPORT) PROJECT TABLES
-- =====================================================
TRUNCATE TABLE project_IES_personal_info;
TRUNCATE TABLE project_IES_family_working_members;
TRUNCATE TABLE project_IES_immediate_family_details;
TRUNCATE TABLE project_IES_educational_background;
TRUNCATE TABLE project_IES_expenses;
TRUNCATE TABLE project_IES_expense_details;
TRUNCATE TABLE project_IES_attachments;

-- =====================================================
-- IIES (INDIVIDUAL - INITIAL EDUCATIONAL SUPPORT) PROJECT TABLES
-- =====================================================
TRUNCATE TABLE project_IIES_personal_info;
TRUNCATE TABLE project_IIES_family_working_members;
TRUNCATE TABLE project_IIES_immediate_family_details;
TRUNCATE TABLE project_IIES_education_background;
TRUNCATE TABLE project_IIES_expenses;
TRUNCATE TABLE project_IIES_expense_details;
TRUNCATE TABLE project_IIES_scope_financial_support;
TRUNCATE TABLE project_IIES_attachments;

-- =====================================================
-- ILP (INDIVIDUAL - LIVELIHOOD APPLICATION) PROJECT TABLES
-- =====================================================
TRUNCATE TABLE project_ILP_personal_info;
TRUNCATE TABLE project_ILP_revenue_plan_items;
TRUNCATE TABLE project_ILP_revenue_income;
TRUNCATE TABLE project_ILP_revenue_expenses;
TRUNCATE TABLE project_ILP_risk_analysis;
TRUNCATE TABLE project_ILP_strength_weakness;
TRUNCATE TABLE project_ILP_budget;
TRUNCATE TABLE project_ILP_attached_docs;

-- =====================================================
-- IAH (INDIVIDUAL - ACCESS TO HEALTH) PROJECT TABLES
-- =====================================================
TRUNCATE TABLE project_IAH_personal_info;
TRUNCATE TABLE project_IAH_health_condition;
TRUNCATE TABLE project_IAH_earning_members;
TRUNCATE TABLE project_IAH_budget_details;
TRUNCATE TABLE project_IAH_support_details;
TRUNCATE TABLE project_IAH_documents;

-- =====================================================
-- IGE (INSTITUTIONAL ONGOING GROUP EDUCATIONAL) PROJECT TABLES
-- =====================================================
TRUNCATE TABLE project_IGE_institution_info;
TRUNCATE TABLE project_IGE_beneficiaries_supported;
TRUNCATE TABLE project_IGE_ongoing_beneficiaries;
TRUNCATE TABLE project_IGE_new_beneficiaries;
TRUNCATE TABLE project_IGE_budget;
TRUNCATE TABLE project_IGE_development_monitoring;

-- =====================================================
-- LDP (LIVELIHOOD DEVELOPMENT PROJECTS) TABLES
-- =====================================================
TRUNCATE TABLE project_LDP_need_analysis;
TRUNCATE TABLE project_LDP_target_group;
TRUNCATE TABLE project_LDP_intervention_logic;

-- =====================================================
-- CIC (CRISIS INTERVENTION CENTER) PROJECT TABLES
-- =====================================================
TRUNCATE TABLE project_cic_basic_info;

-- =====================================================
-- OLD DEVELOPMENT PROJECT TABLES
-- =====================================================
TRUNCATE TABLE oldDevelopmentProjects;
TRUNCATE TABLE old_DP_budgets;
TRUNCATE TABLE old_DP_attachments;

-- =====================================================
-- REPORT TABLES
-- =====================================================
TRUNCATE TABLE DP_Reports;
TRUNCATE TABLE report_comments;
TRUNCATE TABLE report_attachments;
TRUNCATE TABLE DP_Objectives;
TRUNCATE TABLE DP_Activities;
TRUNCATE TABLE DP_AccountDetails;
TRUNCATE TABLE DP_Photos;
TRUNCATE TABLE DP_Outlooks;
TRUNCATE TABLE qrdl_annexure;
TRUNCATE TABLE rqis_age_profiles;
TRUNCATE TABLE rqst_trainee_profile;
TRUNCATE TABLE rqwd_inmates_profiles;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- RESET AUTO_INCREMENT COUNTERS (OPTIONAL)
-- =====================================================
-- Uncomment the following lines if you want to reset auto-increment counters


ALTER TABLE projects AUTO_INCREMENT = 1;
ALTER TABLE project_comments AUTO_INCREMENT = 1;
ALTER TABLE project_attachments AUTO_INCREMENT = 1;
ALTER TABLE project_budgets AUTO_INCREMENT = 1;
ALTER TABLE project_objectives AUTO_INCREMENT = 1;
ALTER TABLE project_results AUTO_INCREMENT = 1;
ALTER TABLE project_risks AUTO_INCREMENT = 1;
ALTER TABLE project_sustainabilities AUTO_INCREMENT = 1;
ALTER TABLE project_activities AUTO_INCREMENT = 1;
ALTER TABLE project_timeframes AUTO_INCREMENT = 1;
ALTER TABLE DP_Reports AUTO_INCREMENT = 1;
ALTER TABLE report_comments AUTO_INCREMENT = 1;
ALTER TABLE report_attachments AUTO_INCREMENT = 1;
ALTER TABLE DP_Objectives AUTO_INCREMENT = 1;
ALTER TABLE DP_Activities AUTO_INCREMENT = 1;
ALTER TABLE DP_AccountDetails AUTO_INCREMENT = 1;
ALTER TABLE DP_Photos AUTO_INCREMENT = 1;
ALTER TABLE DP_Outlooks AUTO_INCREMENT = 1;
ALTER TABLE qrdl_annexure AUTO_INCREMENT = 1;
ALTER TABLE rqis_age_profiles AUTO_INCREMENT = 1;
ALTER TABLE rqst_trainee_profile AUTO_INCREMENT = 1;
ALTER TABLE rqwd_inmates_profiles AUTO_INCREMENT = 1;


-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================
SELECT 'All project and report tables have been truncated successfully!' AS message;
