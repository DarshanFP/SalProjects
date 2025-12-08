# Database Tables and Relationships Documentation

This document provides an overview of all database tables, their relationships, and usage in the SalProjects Laravel application.

---

## Core System Table of Contents

1. [Core System Tables](#1-core-system-tables)
2. [Project Management Tables](#2-project-management-tables)
3. [Project Type-Specific Tables](#3-project-type-specific-tables)
4. [Report Management Tables](#4-report-management-tables)
5. [Comments and Attachments](#5-comments-and-attachments)
6. [Legacy/Old Development Projects](#6-legacyold-development-projects)

---

## 1. Core System Tables

### `users`

**Purpose**: Stores user accounts and authentication information.

**Relationships**:

- **Self-referential**: `parent_id` → `users.id` (hierarchical user structure)
- **Has Many**:
  - `projects` (via `user_id`)
  - `reports` (via `user_id` in `DP_Reports`)
  - `comments` (via `user_id` in `report_comments` and `project_comments`)
  - `children` (via `parent_id`)

**Usage**:

- User authentication and authorization
- Role-based access control (admin, coordinator, provincial, executor, general, applicant)
- Province-based organization (Bangalore, Vijayawada, Visakhapatnam, Generalate, Luzern)
- Hierarchical user management (parent-child relationships)

**Key Features**:

- Uses Spatie Laravel Permission package for roles and permissions
- Supports password reset functionality
- Tracks user status (active/inactive)

---

### `password_reset_tokens`

**Purpose**: Stores password reset tokens for user password recovery.

**Relationships**: None (standalone table)

**Usage**: Handles password reset requests via email tokens.

---

### `sessions`

**Purpose**: Stores user session data.

**Relationships**:

- **Belongs To**: `users` (via `user_id`)

**Usage**: Laravel session management for authenticated users.

---

### `failed_jobs`

**Purpose**: Stores failed queue jobs.

**Relationships**: None

**Usage**: Laravel queue system error tracking.

---

### `personal_access_tokens`

**Purpose**: Stores API personal access tokens (Sanctum).

**Relationships**: Polymorphic relationship with tokenable models (typically `users`)

**Usage**: API authentication using Laravel Sanctum.

---

### Permission System Tables (Spatie Laravel Permission)

#### `permissions`

**Purpose**: Stores permission definitions.

**Relationships**:

- **Many to Many**: `roles` (via `role_has_permissions`)
- **Many to Many**: Models (via `model_has_permissions`)

#### `roles`

**Purpose**: Stores role definitions.

**Relationships**:

- **Many to Many**: `permissions` (via `role_has_permissions`)
- **Many to Many**: Models (via `model_has_roles`)

#### `model_has_permissions`

**Purpose**: Pivot table for direct model-permission assignments.

**Relationships**:

- **Belongs To**: `permissions`
- **Polymorphic**: Various models (typically `users`)

#### `model_has_roles`

**Purpose**: Pivot table for model-role assignments.

**Relationships**:

- **Belongs To**: `roles`
- **Polymorphic**: Various models (typically `users`)

#### `role_has_permissions`

**Purpose**: Pivot table for role-permission assignments.

**Relationships**:

- **Belongs To**: `roles` and `permissions`

**Usage**: Comprehensive role-based access control system for managing user permissions across the application.

---

## 2. Project Management Tables

### `projects`

**Purpose**: Main table storing all project information.

**Relationships**:

- **Belongs To**:
  - `users` (via `user_id` - project creator/owner)
  - `users` (via `in_charge` - project in-charge)
  - `projects` (via `predecessor_project_id` - for next phase projects)
- **Has Many**:
  - `project_budgets`
  - `project_attachments`
  - `project_objectives`
  - `project_sustainabilities`
  - `project_comments`
  - `DP_Reports` (monthly reports)
  - All project type-specific tables (see section 3)
  - `projects` (via `predecessor_project_id` - successor projects)

**Usage**:

- Central repository for all project data
- Supports multiple project types (see project type-specific tables)
- Tracks project status workflow (draft, submitted, approved, etc.)
- Manages project phases and budgets
- Links to logical framework (objectives, results, risks, activities, timeframes)

**Key Features**:

- Auto-generates unique `project_id` based on project type
- Supports project continuation (predecessor/successor relationships)
- Tracks financial information (budget, sanctioned amounts, forwarded amounts)

---

### `project_budgets`

**Purpose**: Stores budget details for projects by phase.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**:

- Phase-wise budget planning
- Tracks rate, quantity, multiplier, duration for each budget item
- Calculates amounts for current and next phases

---

### `project_attachments`

**Purpose**: Stores file attachments related to projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**:

- Document storage for project proposals
- File uploads with descriptions and public URLs

---

### `project_objectives`

**Purpose**: Stores project objectives (logical framework).

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)
- **Has Many**:
  - `project_results` (via `objective_id`)
  - `project_risks` (via `objective_id`)
  - `project_activities` (via `objective_id`)

**Usage**:

- Logical framework structure
- Links objectives to results, risks, and activities
- Used in project planning and reporting

---

### `project_results`

**Purpose**: Stores expected outcomes/results for objectives.

**Relationships**:

- **Belongs To**: `project_objectives` (via `objective_id`)

**Usage**: Defines expected outcomes for each project objective.

---

### `project_risks`

**Purpose**: Stores identified risks for objectives.

**Relationships**:

- **Belongs To**: `project_objectives` (via `objective_id`)

**Usage**: Risk management and mitigation planning.

---

### `project_activities`

**Purpose**: Stores activities planned for objectives.

**Relationships**:

- **Belongs To**: `project_objectives` (via `objective_id`)
- **Has Many**: `project_timeframes` (via `activity_id`)

**Usage**:

- Activity planning and tracking
- Links to timeframes for scheduling

---

### `project_timeframes`

**Purpose**: Stores monthly timeframe information for activities.

**Relationships**:

- **Belongs To**: `project_activities` (via `activity_id`)

**Usage**: Monthly activity scheduling and tracking.

---

### `project_sustainabilities`

**Purpose**: Stores sustainability, monitoring, and evaluation information.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**:

- Sustainability planning
- Monitoring and evaluation methodologies
- Reporting methodologies

---

### `project_comments`

**Purpose**: Stores comments/feedback on projects.

**Relationships**:

- **Belongs To**:
  - `projects` (via `project_id`)
  - `users` (via `user_id`)

**Usage**:

- Project review and feedback system
- Communication between coordinators, provincials, and executors
- Workflow comments during approval process

---

## 3. Project Type-Specific Tables

The application supports multiple project types, each with specialized tables:

### 3.1 Rural-Urban-Tribal (EduRUT) Projects

#### `Project_EduRUT_Basic_Info`

**Purpose**: Basic information for Rural-Urban-Tribal educational projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Stores operational area, institution type, group type, project location, and need analysis.

#### `project_edu_rut_target_groups`

**Purpose**: Target group beneficiaries for EduRUT projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Individual beneficiary details including education support information.

#### `project_edu_rut_annexed_target_groups`

**Purpose**: Additional target group information (annexed).

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Extended beneficiary information with family background and support needs.

---

### 3.2 Child Care Institution (CCI) Projects

#### `project_cic_basic_info`

**Purpose**: Basic information for Crisis Intervention Center projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Institution statistics and beneficiary information.

#### `project_CCI_rationale`

**Purpose**: Rationale for CCI projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

#### `project_CCI_statistics`

**Purpose**: Statistical data for CCI projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Tracks children served, reintegrated, shifted, pursuing higher studies, etc.

#### `project_CCI_age_profile`

**Purpose**: Age-wise education profile of children.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Educational statistics by age groups (below 5, 6-10, 11-15, 16+).

#### `project_CCI_annexed_target_group`

**Purpose**: Detailed beneficiary information for CCI.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Individual child profiles with family background.

#### `project_CCI_personal_situation`

**Purpose**: Personal situation of children (orphans, semi-orphans, etc.).

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Categorizes children by family situation (with parents, orphans, HIV-affected, differently-abled, etc.).

#### `project_CCI_economic_background`

**Purpose**: Economic background of parents/guardians.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Tracks parent occupation categories (agricultural labour, marginal farmers, self-employed, etc.).

#### `project_CCI_present_situation`

**Purpose**: Current challenges and focus areas.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Internal/external challenges and areas of focus.

#### `project_CCI_achievements`

**Purpose**: Achievements of children (academic, sports, other).

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

---

### 3.3 Livelihood Development Projects (LDP)

#### `project_LDP_need_analysis`

**Purpose**: Need analysis document for LDP projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Stores uploaded need analysis documents.

#### `project_LDP_target_group`

**Purpose**: Target beneficiaries for livelihood projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Beneficiary details, family situation, nature of livelihood, amount requested.

#### `project_LDP_intervention_logic`

**Purpose**: Intervention logic description for LDP.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

---

### 3.4 Residential Skill Training (RST) Projects

#### `project_RST_institution_info`

**Purpose**: Training center/institution information.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Institution setup year, students trained, training outcomes.

#### `project_RST_target_group`

**Purpose**: Target group description for RST.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Number of beneficiaries and their description.

#### `project_RST_target_group_annexure`

**Purpose**: Detailed beneficiary profiles for RST.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Individual trainee information.

#### `project_RST_geographical_areas`

**Purpose**: Geographical coverage of RST projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Mandal, villages, towns, and beneficiary counts.

#### `project_RST_DP_beneficiaries_area`

**Purpose**: Beneficiary areas for Development Projects and RST.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Project areas, beneficiary categories, direct/indirect beneficiaries.

---

### 3.5 Institutional Ongoing Group Educational (IGE) Projects

#### `project_IGE_institution_info`

**Purpose**: Institution information for IGE projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Institutional type, age group, previous year beneficiaries, outcomes.

#### `project_IGE_beneficiaries_supported`

**Purpose**: Classes of beneficiaries supported.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Beneficiary class and total numbers.

#### `project_IGE_ongoing_beneficiaries`

**Purpose**: Ongoing/continuing beneficiaries.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Details of beneficiaries continuing from previous year.

#### `project_IGE_new_beneficiaries`

**Purpose**: New beneficiaries for current year.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: New beneficiary details and family background.

#### `project_IGE_budget`

**Purpose**: Budget details for IGE beneficiaries.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: College fees, hostel fees, scholarship eligibility, family contribution, amount requested.

#### `project_IGE_development_monitoring`

**Purpose**: Development monitoring information.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Proposed activities, monitoring methods, evaluation process.

---

### 3.6 Individual - Ongoing Educational Support (IES) Projects

#### `project_IES_personal_info`

**Purpose**: Personal information of IES beneficiaries.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Name, age, gender, contact, address, family information, parent income.

#### `project_IES_immediate_family_details`

**Purpose**: Immediate family situation details.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Family health conditions, residential status, employment with St. Ann's, support received.

#### `project_IES_family_working_members`

**Purpose**: Working members of the family.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Family member names, work nature, monthly income.

#### `project_IES_education_background`

**Purpose**: Educational background and performance.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Previous class, amount sanctioned/utilized, scholarship information, academic performance.

#### `project_IES_expenses`

**Purpose**: Expense summary for IES.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)
- **Has Many**: `project_IES_expense_details` (via `IES_expense_id`)

**Usage**: Total expenses, expected scholarships, support from other sources, beneficiary contribution, balance requested.

#### `project_IES_expense_details`

**Purpose**: Detailed expense breakdown.

**Relationships**:

- **Belongs To**: `project_IES_expenses` (via `IES_expense_id`)

**Usage**: Particular-wise expense details with amounts.

#### `project_IES_attachments`

**Purpose**: Document attachments for IES projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Aadhar card, fee quotation, scholarship proof, medical confirmation, caste certificate, etc.

---

### 3.7 Individual - Initial Educational Support (IIES) Projects

#### `project_IIES_personal_info`

**Purpose**: Personal information of IIES beneficiaries.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Similar to IES but for initial support applications.

#### `project_IIES_immediate_family_details`

**Purpose**: Family situation details for IIES.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Family health, residential, employment details.

#### `project_IIES_family_working_members`

**Purpose**: Working family members for IIES.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

#### `project_IIES_education_background`

**Purpose**: Educational background for IIES.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Previous education, current studies, aspirations, long-term effects.

#### `project_IIES_scope_financial_support`

**Purpose**: Financial support scope and eligibility.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Government scholarship eligibility, other scholarships, family contribution.

#### `project_IIES_expenses`

**Purpose**: Expense summary for IIES.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)
- **Has Many**: `project_IIES_expense_details` (via `IIES_expense_id`)

#### `project_IIES_expense_details`

**Purpose**: Detailed expense breakdown for IIES.

**Relationships**:

- **Belongs To**: `project_IIES_expenses` (via `IIES_expense_id`)

#### `project_IIES_attachments`

**Purpose**: Document attachments for IIES.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

---

### 3.8 Individual Livelihood Projects (ILP)

#### `project_ILP_personal_info`

**Purpose**: Personal information for livelihood project applicants.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Name, age, gender, contact, occupation, marital status, family situation, business details.

#### `project_ILP_budget`

**Purpose**: Budget details for livelihood projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Budget description, cost, beneficiary contribution, amount requested.

#### `project_ILP_strength_weakness`

**Purpose**: Business strengths and weaknesses analysis.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: SWOT-like analysis for business planning.

#### `project_ILP_revenue_goals`

**Purpose**: Revenue goals and business plan items.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Year-wise business plan items, annual income/expenses projections.

#### `project_ILP_risk_analysis`

**Purpose**: Risk analysis for livelihood projects.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Identified risks, mitigation measures, business sustainability, expected profits.

#### `project_ILP_attached_docs`

**Purpose**: Document attachments for ILP.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Aadhar, request letter, purchase quotations, other documents.

---

### 3.9 Individual Access to Health (IAH) Projects

#### `project_IAH_personal_info`

**Purpose**: Personal information for health support applicants.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Name, age, gender, contact, address, guardian information.

#### `project_IAH_health_condition`

**Purpose**: Health condition details.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Illness, treatment details, doctor, hospital, health situation, family situation.

#### `project_IAH_earning_members`

**Purpose**: Earning members of the family.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Member name, work type, monthly income.

#### `project_IAH_budget_details`

**Purpose**: Budget details for health support.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Particulars, amount, total expenses, family contribution, amount requested.

#### `project_IAH_support_details`

**Purpose**: Support received details.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Employment with St. Ann's, previous support, government support.

#### `project_IAH_documents`

**Purpose**: Document attachments for IAH.

**Relationships**:

- **Belongs To**: `projects` (via `project_id`)

**Usage**: Aadhar copy, request letter, medical reports, other documents.

---

## 4. Report Management Tables

### `DP_Reports`

**Purpose**: Monthly development project reports.

**Relationships**:

- **Belongs To**:
  - `projects` (via `project_id`)
  - `users` (via `user_id`)
- **Has Many**:
  - `DP_Objectives` (via `report_id`)
  - `DP_AccountDetails` (via `report_id`)
  - `DP_Photos` (via `report_id`)
  - `DP_Outlooks` (via `report_id`)
  - `qrdl_annexure` (via `report_id`)
  - `rqis_age_profiles` (via `report_id`)
  - `rqst_trainee_profile` (via `report_id`)
  - `rqwd_inmates_profiles` (via `report_id`)
  - `report_attachments` (via `report_id`)
  - `report_comments` (via `report_id`)

**Usage**:

- Monthly reporting for development projects
- Tracks report status workflow (draft, submitted, approved, reverted)
- Stores financial overview (amount sanctioned, forwarded, in hand)
- Links to previous reports via `report_before_id`
- Supports revert functionality with `revert_reason`

**Key Features**:

- Auto-generates unique `report_id`
- Status-based workflow management
- Account period tracking (start/end dates)

---

### `DP_Objectives`

**Purpose**: Objectives reported in monthly reports.

**Relationships**:

- **Belongs To**:
  - `DP_Reports` (via `report_id`)
  - `project_objectives` (via `project_objective_id` - links to original project objective)
- **Has Many**: `DP_Activities` (via `objective_id`)

**Usage**:

- Reports on project objectives progress
- Tracks expected outcomes, changes, lessons learnt
- Links back to original project objectives

---

### `DP_Activities`

**Purpose**: Activities reported in monthly reports.

**Relationships**:

- **Belongs To**:
  - `DP_Objectives` (via `objective_id`)
  - `project_activities` (via `project_activity_id` - links to original project activity)

**Usage**:

- Monthly activity summaries
- Qualitative and quantitative data
- Intermediate outcomes tracking
- Links back to original project activities

---

### `DP_AccountDetails`

**Purpose**: Detailed account information for reports.

**Relationships**:

- **Belongs To**:
  - `DP_Reports` (via `report_id`)
  - `projects` (via `project_id`)
  - `project_budgets` (via `project_id` - for budget row tracking)

**Usage**:

- Detailed financial tracking by particulars
- Amount forwarded, sanctioned, expenses (last month, this month, total)
- Balance amount calculations
- `is_budget_row` flag links to project budget items

---

### `DP_Photos`

**Purpose**: Photo attachments for reports.

**Relationships**:

- **Belongs To**: `DP_Reports` (via `report_id`)

**Usage**: Visual documentation of project activities and outcomes.

---

### `DP_Outlooks`

**Purpose**: Future plans and outlooks for reports.

**Relationships**:

- **Belongs To**: `DP_Reports` (via `report_id`)

**Usage**: Plans for next month and future activities.

---

### `qrdl_annexure`

**Purpose**: Annexure for Quarterly Development Livelihood reports.

**Relationships**:

- **Belongs To**: `DP_Reports` (via `report_id`)

**Usage**:

- Beneficiary details for livelihood support
- Self-employment details, amounts sanctioned, profits
- Impact and challenges tracking

---

### `rqis_age_profiles`

**Purpose**: Age profiles for Quarterly Institutional Support reports.

**Relationships**:

- **Belongs To**: `DP_Reports` (via `report_id`)

**Usage**: Age-wise and education-wise beneficiary statistics.

---

### `rqst_trainee_profile`

**Purpose**: Trainee profiles for Quarterly Skill Training reports.

**Relationships**:

- **Belongs To**: `DP_Reports` (via `report_id`)

**Usage**: Education category-wise trainee numbers.

---

### `rqwd_inmates_profiles`

**Purpose**: Inmate profiles for Quarterly Women in Distress reports.

**Relationships**:

- **Belongs To**: `DP_Reports` (via `report_id`)

**Usage**: Age category and status-wise inmate statistics.

---

### `report_attachments`

**Purpose**: File attachments for reports.

**Relationships**:

- **Belongs To**: `DP_Reports` (via `report_id`)

**Usage**: Document storage for reports with descriptions and public URLs.

---

### `report_comments`

**Purpose**: Comments/feedback on reports.

**Relationships**:

- **Belongs To**:
  - `DP_Reports` (via `report_id`)
  - `users` (via `user_id`)

**Usage**:

- Report review and feedback system
- Communication during approval workflow
- Auto-generates unique comment IDs

---

## 5. Comments and Attachments

### `project_comments`

**Purpose**: Comments on projects.

**Relationships**:

- **Belongs To**:
  - `projects` (via `project_id`)
  - `users` (via `user_id`)

**Usage**: Project review, feedback, and approval workflow comments.

---

### `report_comments`

**Purpose**: Comments on reports.

**Relationships**:

- **Belongs To**:
  - `DP_Reports` (via `report_id`)
  - `users` (via `user_id`)

**Usage**: Report review, feedback, and approval workflow comments.

---

## 6. Legacy/Old Development Projects

### `oldDevelopmentProjects`

**Purpose**: Legacy development projects (pre-new system).

**Relationships**:

- **Belongs To**: `users` (via `user_id`)
- **Has Many**:
  - `old_DP_budgets` (via `project_id`)
  - `old_DP_attachments` (via `project_id`)

**Usage**:

- Historical project data
- Migration from old system
- Reference for project continuity

---

### `old_DP_budgets`

**Purpose**: Budgets for old development projects.

**Relationships**:

- **Belongs To**: `oldDevelopmentProjects` (via `project_id`)

**Usage**: Historical budget data.

---

### `old_DP_attachments`

**Purpose**: Attachments for old development projects.

**Relationships**:

- **Belongs To**: `oldDevelopmentProjects` (via `project_id`)

**Usage**: Historical document storage.

---

## Relationship Summary

### Core Relationships Flow

```
users
├── projects (creator/owner)
│   ├── project_budgets
│   ├── project_attachments
│   ├── project_objectives
│   │   ├── project_results
│   │   ├── project_risks
│   │   └── project_activities
│   │       └── project_timeframes
│   ├── project_sustainabilities
│   ├── project_comments
│   ├── [Project Type-Specific Tables]
│   └── DP_Reports
│       ├── DP_Objectives
│       │   └── DP_Activities
│       ├── DP_AccountDetails
│       ├── DP_Photos
│       ├── DP_Outlooks
│       ├── qrdl_annexure
│       ├── rqis_age_profiles
│       ├── rqst_trainee_profile
│       ├── rqwd_inmates_profiles
│       ├── report_attachments
│       └── report_comments
└── reports (via user_id in DP_Reports)
└── comments (via user_id)
```

### Project Type-Specific Relationships

Each project type has its own set of related tables that all link back to the main `projects` table via `project_id`. The relationship is typically:

- **One-to-One**: Basic info, rationale, statistics, personal info, etc.
- **One-to-Many**: Target groups, beneficiaries, budgets, attachments, etc.

---

## Usage Patterns

### Project Creation Flow

1. Create project in `projects` table
2. Add general information (budgets, attachments, objectives)
3. Add project type-specific information
4. Add logical framework (objectives, results, risks, activities, timeframes)
5. Add sustainability information

### Report Creation Flow

1. Create report in `DP_Reports` table
2. Link to project and previous report
3. Add objectives (linked to project objectives)
4. Add activities (linked to project activities)
5. Add account details (linked to project budgets)
6. Add photos, outlooks, and type-specific data
7. Add attachments and comments

### Workflow Management

- Projects and reports use status fields to track workflow
- Comments system enables communication between roles
- Revert functionality allows returning items for changes
- Status progression: draft → submitted → forwarded → approved/rejected

---

## Notes

- All tables use Laravel's `timestamps` (created_at, updated_at) unless specified otherwise
- Foreign key constraints use `onDelete('cascade')` for data integrity
- Unique identifiers are auto-generated (project_id, report_id, comment_id, etc.)
- The system supports multiple project types with specialized data structures
- Reports link back to original project data for consistency
- Hierarchical user structure supports organizational management

---

**Last Updated**: Generated from migration files and model relationships analysis
**Application**: SalProjects Laravel Application
