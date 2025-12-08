# Tables to Truncate for Production

This document lists all database tables that should be truncated before moving to production, while keeping Core System Tables intact (users, permissions, roles, etc.).

---

## Important Notes

⚠️ **WARNING**: These operations will delete all data from the listed tables. Make sure to:
- Backup your database before truncating
- Verify that you want to keep user data and permission settings
- Ensure all testing data is in these tables and not in Core System Tables

✅ **KEEP INTACT**: The following tables will NOT be truncated:
- `users` - User accounts and authentication
- `password_reset_tokens` - Password reset functionality
- `sessions` - User sessions
- `failed_jobs` - Queue job tracking
- `personal_access_tokens` - API tokens
- `permissions` - Permission definitions
- `roles` - Role definitions
- `model_has_permissions` - Direct permission assignments
- `model_has_roles` - Role assignments
- `role_has_permissions` - Role-permission mappings

---

## Tables to Truncate

### 1. Project Management Tables

```
projects
project_budgets
project_attachments
project_objectives
project_results
project_risks
project_activities
project_timeframes
project_sustainabilities
project_comments
```

**Total: 10 tables**

---

### 2. Project Type-Specific Tables

#### Rural-Urban-Tribal (EduRUT) Projects
```
Project_EduRUT_Basic_Info
project_edu_rut_target_groups
project_edu_rut_annexed_target_groups
```

#### Crisis Intervention Center (CIC) Projects
```
project_cic_basic_info
```

#### Child Care Institution (CCI) Projects
```
project_CCI_rationale
project_CCI_statistics
project_CCI_age_profile
project_CCI_annexed_target_group
project_CCI_personal_situation
project_CCI_economic_background
project_CCI_present_situation
project_CCI_achievements
```

#### Livelihood Development Projects (LDP)
```
project_LDP_need_analysis
project_LDP_target_group
project_LDP_intervention_logic
```

#### Residential Skill Training (RST) Projects
```
project_RST_institution_info
project_RST_target_group
project_RST_target_group_annexure
project_RST_geographical_areas
project_RST_DP_beneficiaries_area
```

#### Institutional Ongoing Group Educational (IGE) Projects
```
project_IGE_institution_info
project_IGE_beneficiaries_supported
project_IGE_ongoing_beneficiaries
project_IGE_new_beneficiaries
project_IGE_budget
project_IGE_development_monitoring
```

#### Individual - Ongoing Educational Support (IES) Projects
```
project_IES_personal_info
project_IES_immediate_family_details
project_IES_family_working_members
project_IES_education_background
project_IES_expenses
project_IES_expense_details
project_IES_attachments
```

#### Individual - Initial Educational Support (IIES) Projects
```
project_IIES_personal_info
project_IIES_immediate_family_details
project_IIES_family_working_members
project_IIES_education_background
project_IIES_scope_financial_support
project_IIES_expenses
project_IIES_expense_details
project_IIES_attachments
```

#### Individual Livelihood Projects (ILP)
```
project_ILP_personal_info
project_ILP_budget
project_ILP_strength_weakness
project_ILP_revenue_goals
project_ILP_risk_analysis
project_ILP_attached_docs
```

#### Individual Access to Health (IAH) Projects
```
project_IAH_personal_info
project_IAH_health_condition
project_IAH_earning_members
project_IAH_budget_details
project_IAH_support_details
project_IAH_documents
```

**Total: 50 tables**

---

### 3. Report Management Tables

```
DP_Reports
DP_Objectives
DP_Activities
DP_AccountDetails
DP_Photos
DP_Outlooks
qrdl_annexure
rqis_age_profiles
rqst_trainee_profile
rqwd_inmates_profiles
report_attachments
report_comments
```

**Total: 12 tables**

---

### 4. Legacy/Old Development Projects

```
oldDevelopmentProjects
old_DP_budgets
old_DP_attachments
```

**Total: 3 tables**

---

## Complete List (Alphabetically Sorted)

For easy reference, here are all tables to truncate in alphabetical order:

```
DP_AccountDetails
DP_Activities
DP_Objectives
DP_Outlooks
DP_Photos
DP_Reports
Project_EduRUT_Basic_Info
oldDevelopmentProjects
old_DP_attachments
old_DP_budgets
project_CCI_achievements
project_CCI_age_profile
project_CCI_annexed_target_group
project_CCI_economic_background
project_CCI_personal_situation
project_CCI_present_situation
project_CCI_rationale
project_CCI_statistics
project_IAH_budget_details
project_IAH_documents
project_IAH_earning_members
project_IAH_health_condition
project_IAH_personal_info
project_IAH_support_details
project_IES_attachments
project_IES_education_background
project_IES_expense_details
project_IES_expenses
project_IES_family_working_members
project_IES_immediate_family_details
project_IES_personal_info
project_IGE_beneficiaries_supported
project_IGE_budget
project_IGE_development_monitoring
project_IGE_institution_info
project_IGE_new_beneficiaries
project_IGE_ongoing_beneficiaries
project_IIES_attachments
project_IIES_education_background
project_IIES_expense_details
project_IIES_expenses
project_IIES_family_working_members
project_IIES_immediate_family_details
project_IIES_personal_info
project_IIES_scope_financial_support
project_ILP_attached_docs
project_ILP_budget
project_ILP_personal_info
project_ILP_revenue_goals
project_ILP_risk_analysis
project_ILP_strength_weakness
project_LDP_intervention_logic
project_LDP_need_analysis
project_LDP_target_group
project_RST_DP_beneficiaries_area
project_RST_geographical_areas
project_RST_institution_info
project_RST_target_group
project_RST_target_group_annexure
project_activities
project_attachments
project_budgets
project_cic_basic_info
project_comments
project_edu_rut_annexed_target_groups
project_edu_rut_target_groups
project_objectives
project_results
project_risks
project_sustainabilities
project_timeframes
projects
qrdl_annexure
report_attachments
report_comments
rqis_age_profiles
rqst_trainee_profile
rqwd_inmates_profiles
```

**Total: 75 tables**

---

## SQL Truncate Commands

### Option 1: Truncate All at Once (MySQL/MariaDB)

```sql
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE DP_AccountDetails;
TRUNCATE TABLE DP_Activities;
TRUNCATE TABLE DP_Objectives;
TRUNCATE TABLE DP_Outlooks;
TRUNCATE TABLE DP_Photos;
TRUNCATE TABLE DP_Reports;
TRUNCATE TABLE Project_EduRUT_Basic_Info;
TRUNCATE TABLE oldDevelopmentProjects;
TRUNCATE TABLE old_DP_attachments;
TRUNCATE TABLE old_DP_budgets;
TRUNCATE TABLE project_CCI_achievements;
TRUNCATE TABLE project_CCI_age_profile;
TRUNCATE TABLE project_CCI_annexed_target_group;
TRUNCATE TABLE project_CCI_economic_background;
TRUNCATE TABLE project_CCI_personal_situation;
TRUNCATE TABLE project_CCI_present_situation;
TRUNCATE TABLE project_CCI_rationale;
TRUNCATE TABLE project_CCI_statistics;
TRUNCATE TABLE project_IAH_budget_details;
TRUNCATE TABLE project_IAH_documents;
TRUNCATE TABLE project_IAH_earning_members;
TRUNCATE TABLE project_IAH_health_condition;
TRUNCATE TABLE project_IAH_personal_info;
TRUNCATE TABLE project_IAH_support_details;
TRUNCATE TABLE project_IES_attachments;
TRUNCATE TABLE project_IES_education_background;
TRUNCATE TABLE project_IES_expense_details;
TRUNCATE TABLE project_IES_expenses;
TRUNCATE TABLE project_IES_family_working_members;
TRUNCATE TABLE project_IES_immediate_family_details;
TRUNCATE TABLE project_IES_personal_info;
TRUNCATE TABLE project_IGE_beneficiaries_supported;
TRUNCATE TABLE project_IGE_budget;
TRUNCATE TABLE project_IGE_development_monitoring;
TRUNCATE TABLE project_IGE_institution_info;
TRUNCATE TABLE project_IGE_new_beneficiaries;
TRUNCATE TABLE project_IGE_ongoing_beneficiaries;
TRUNCATE TABLE project_IIES_attachments;
TRUNCATE TABLE project_IIES_education_background;
TRUNCATE TABLE project_IIES_expense_details;
TRUNCATE TABLE project_IIES_expenses;
TRUNCATE TABLE project_IIES_family_working_members;
TRUNCATE TABLE project_IIES_immediate_family_details;
TRUNCATE TABLE project_IIES_personal_info;
TRUNCATE TABLE project_IIES_scope_financial_support;
TRUNCATE TABLE project_ILP_attached_docs;
TRUNCATE TABLE project_ILP_budget;
TRUNCATE TABLE project_ILP_personal_info;
TRUNCATE TABLE project_ILP_revenue_goals;
TRUNCATE TABLE project_ILP_risk_analysis;
TRUNCATE TABLE project_ILP_strength_weakness;
TRUNCATE TABLE project_LDP_intervention_logic;
TRUNCATE TABLE project_LDP_need_analysis;
TRUNCATE TABLE project_LDP_target_group;
TRUNCATE TABLE project_RST_DP_beneficiaries_area;
TRUNCATE TABLE project_RST_geographical_areas;
TRUNCATE TABLE project_RST_institution_info;
TRUNCATE TABLE project_RST_target_group;
TRUNCATE TABLE project_RST_target_group_annexure;
TRUNCATE TABLE project_activities;
TRUNCATE TABLE project_attachments;
TRUNCATE TABLE project_budgets;
TRUNCATE TABLE project_cic_basic_info;
TRUNCATE TABLE project_comments;
TRUNCATE TABLE project_edu_rut_annexed_target_groups;
TRUNCATE TABLE project_edu_rut_target_groups;
TRUNCATE TABLE project_objectives;
TRUNCATE TABLE project_results;
TRUNCATE TABLE project_risks;
TRUNCATE TABLE project_sustainabilities;
TRUNCATE TABLE project_timeframes;
TRUNCATE TABLE projects;
TRUNCATE TABLE qrdl_annexure;
TRUNCATE TABLE report_attachments;
TRUNCATE TABLE report_comments;
TRUNCATE TABLE rqis_age_profiles;
TRUNCATE TABLE rqst_trainee_profile;
TRUNCATE TABLE rqwd_inmates_profiles;

SET FOREIGN_KEY_CHECKS = 1;
```

### Option 2: Laravel Artisan Command

You can create a custom Artisan command to truncate these tables:

```php
// In app/Console/Commands/TruncateTestData.php
public function handle()
{
    $tables = [
        'DP_AccountDetails',
        'DP_Activities',
        'DP_Objectives',
        'DP_Outlooks',
        'DP_Photos',
        'DP_Reports',
        'Project_EduRUT_Basic_Info',
        'oldDevelopmentProjects',
        'old_DP_attachments',
        'old_DP_budgets',
        // ... (all other tables)
    ];

    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    
    foreach ($tables as $table) {
        DB::table($table)->truncate();
    }
    
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    
    $this->info('All test data truncated successfully!');
}
```

---

## Execution Order Considerations

Due to foreign key constraints, consider truncating in this order:

1. **Report-related tables first** (they depend on projects):
   - `report_comments`
   - `report_attachments`
   - `rqwd_inmates_profiles`
   - `rqst_trainee_profile`
   - `rqis_age_profiles`
   - `qrdl_annexure`
   - `DP_Outlooks`
   - `DP_Photos`
   - `DP_AccountDetails`
   - `DP_Activities`
   - `DP_Objectives`
   - `DP_Reports`

2. **Project type-specific tables** (they depend on projects)

3. **Project management tables** (core project data)

4. **Legacy tables** (independent)

**Note**: Using `SET FOREIGN_KEY_CHECKS = 0` before truncating and `SET FOREIGN_KEY_CHECKS = 1` after will allow truncation in any order.

---

## Verification Checklist

Before truncating:
- [ ] Database backup created
- [ ] User accounts verified in `users` table
- [ ] Permissions and roles verified
- [ ] All test data is in the tables listed above
- [ ] No production data exists in these tables

After truncating:
- [ ] Verify `users` table still has data
- [ ] Verify permissions and roles are intact
- [ ] Check that all listed tables are empty
- [ ] Test user login functionality
- [ ] Verify application can create new projects

---

**Last Updated**: Generated from Database_Tables_and_Relationships.md
**Application**: SalProjects Laravel Application

