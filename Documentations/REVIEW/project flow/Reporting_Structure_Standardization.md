# Reporting Structure Standardization

**Date:** January 2025  
**Status:** 🔄 **IN PROGRESS**  
**Phase:** 4.3 - Standardize Reporting Structure

---

## Objective

Standardize the reporting structure across all project types to ensure consistency in:
- Section ordering
- Field names
- Validation rules
- UI/UX patterns
- Data formats

---

## Standard Report Structure

### Section Order (All Report Types)

1. **Basic Information**
2. **Key Information** (Goal)
3. **Objectives & Activities**
4. **Project-Specific Sections** (if applicable)
5. **Outlooks**
6. **Statements of Account**
7. **Photos**
8. **Attachments**

---

## Field Name Standardization

### Basic Information Fields

| Field Label | Database Field | Form Field Name | Type | Required |
|------------|----------------|-----------------|------|----------|
| Project Type | `project_type` | `project_type` | text | Yes (readonly) |
| Project ID | `project_id` | `project_id` | text | Yes (readonly) |
| Project Title | `project_title` | `project_title` | text | Yes (readonly) |
| Place | `place` | `place` | text | Yes (readonly) |
| Society Name | `society_name` | `society_name` | text | Yes (readonly) |
| Commencement Month & Year | `commencement_month_year` | `commencement_month_year` | date | Yes (readonly) |
| Sister/s In-Charge | `in_charge` | `in_charge` | text | Yes (readonly) |
| Total No. of Beneficiaries | `total_beneficiaries` | `total_beneficiaries` | integer | No |
| Reporting Month | `report_month` | `report_month` | integer (1-12) | Yes |
| Reporting Year | `report_year` | `report_year` | integer | Yes |
| Report Month & Year (combined) | `report_month_year` | - | date (calculated) | Yes |

**Note:** `report_month_year` is calculated from `report_month` and `report_year` in the controller.

---

### Statements of Account Fields

**Standard Columns (Development Projects):**
| Column | Field Name | Type | Required | Calculated |
|--------|------------|------|----------|------------|
| Particulars | `particulars[]` | text | Yes | No |
| Amount Forwarded | `amount_forwarded[]` | decimal | No | No |
| Amount Sanctioned | `amount_sanctioned[]` | decimal | Yes | No |
| Total Amount | `total_amount[]` | decimal | Yes | Yes (2+3) |
| Expenses Last Month | `expenses_last_month[]` | decimal | No | No |
| Expenses This Month | `expenses_this_month[]` | decimal | No | No |
| Total Expenses | `total_expenses[]` | decimal | Yes | Yes (5+6) |
| Balance Amount | `balance_amount[]` | decimal | Yes | Yes (4-7) |

**Note:** Individual project types may have different column structures, but calculation logic should be consistent.

---

### Objectives & Activities Fields

| Field Label | Field Name | Type | Required |
|------------|------------|------|----------|
| Objective | `objective[index]` | text | Yes |
| Expected Outcome | `expected_outcome[index][activity_index]` | text | No |
| Activity | `activity[index][activity_index]` | text | No |
| Month | `month[index][activity_index]` | integer (1-12) | No |
| Summary of Activities | `summary_activities[index][activity_index][timeframe_index]` | text | No |
| What Did Not Happen | `not_happened[index]` | text | No |
| Why Did Not Happen | `why_not_happened[index]` | text | No |
| Changes | `changes[index]` | enum (yes/no) | No |
| Why Changes | `why_changes[index]` | text | No |
| Lessons Learnt | `lessons_learnt[index]` | text | No |
| To Do Based on Lessons | `todo_lessons_learnt[index]` | text | No |

---

### Outlook Fields

| Field Label | Field Name | Type | Required |
|------------|------------|------|----------|
| Date | `date[]` | date | No |
| Action Plan for Next Month | `plan_next_month[]` | text | No |

---

### Photo Fields

| Field Label | Field Name | Type | Required | Max |
|------------|------------|------|----------|-----|
| Photo | `photos[]` | file (image) | No | 10 photos |
| Photo Description | `photo_descriptions[]` | text | No | - |

**File Constraints:**
- Format: jpeg, png, jpg, gif
- Max size: 5 MB per photo
- Max photos: 10 per report

---

### Attachment Fields

| Field Label | Field Name | Type | Required | Max |
|------------|------------|------|----------|-----|
| Attachment File | `attachment_files[]` | file | No | Unlimited |
| Attachment Name | `attachment_names[]` | text | No | 255 chars |
| Attachment Description | `attachment_descriptions[]` | text | No | 1000 chars |

**File Constraints:**
- Format: pdf, doc, docx, xls, xlsx
- Max size: 2 MB per file

---

## Validation Rules Standardization

### Common Validation Rules

```php
// Basic Information
'project_id' => 'required|string|max:255',
'project_title' => 'nullable|string|max:255',
'project_type' => 'nullable|string|max:255',
'place' => 'nullable|string|max:255',
'society_name' => 'nullable|string|max:255',
'commencement_month_year' => 'nullable|date',
'in_charge' => 'nullable|string|max:255',
'total_beneficiaries' => 'nullable|integer|min:0',

// Reporting Period
'report_month' => 'required|integer|between:1,12',
'report_year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
'goal' => 'nullable|string',

// Accounting Period
'account_period_start' => 'nullable|date',
'account_period_end' => 'nullable|date|after_or_equal:account_period_start',

// Statements of Account
'particulars' => 'required|array',
'particulars.*' => 'required|string|max:255',
'amount_forwarded' => 'nullable|array',
'amount_forwarded.*' => 'nullable|numeric|min:0',
'amount_sanctioned' => 'nullable|array',
'amount_sanctioned.*' => 'nullable|numeric|min:0',
'total_amount' => 'nullable|array',
'total_amount.*' => 'nullable|numeric|min:0',
'expenses_last_month' => 'nullable|array',
'expenses_last_month.*' => 'nullable|numeric|min:0',
'expenses_this_month' => 'nullable|array',
'expenses_this_month.*' => 'nullable|numeric|min:0',
'total_expenses' => 'nullable|array',
'total_expenses.*' => 'nullable|numeric|min:0',
'balance_amount' => 'nullable|array',
'balance_amount.*' => 'nullable|numeric',

// Objectives
'objective' => 'nullable|array',
'objective.*' => 'nullable|string',
'expected_outcome' => 'nullable|array',
'expected_outcome.*' => 'nullable|array',
'expected_outcome.*.*' => 'nullable|string',
'activity' => 'nullable|array',
'activity.*' => 'nullable|array',
'activity.*.*' => 'nullable|string',
'month' => 'nullable|array',
'month.*' => 'nullable|array',
'month.*.*' => 'nullable|integer|between:1,12',

// Outlooks
'date' => 'nullable|array',
'date.*' => 'nullable|date',
'plan_next_month' => 'nullable|array',
'plan_next_month.*' => 'nullable|string',

// Photos
'photos' => 'nullable|array',
'photos.*' => 'nullable|array',
'photos.*.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5 MB
'photo_descriptions' => 'nullable|array',
'photo_descriptions.*' => 'nullable|string|max:1000',

// Attachments
'attachment_files' => 'nullable|array',
'attachment_files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:2048', // 2 MB
'attachment_names' => 'nullable|array',
'attachment_names.*' => 'nullable|string|max:255',
'attachment_descriptions' => 'nullable|array',
'attachment_descriptions.*' => 'nullable|string|max:1000',
```

---

## Section Ordering Standardization

### Standard Section Order (All Report Types)

1. **Basic Information Card**
   - All basic fields in consistent order
   - Readonly fields styled consistently

2. **Key Information Card**
   - Goal of the Project

3. **Objectives & Activities Card**
   - Dynamic objectives with activities
   - Consistent structure across all types

4. **Project-Specific Sections** (Conditional)
   - Livelihood Annexure (LDP only)
   - Age Profiles (IGE only)
   - Trainee Profiles (RST only)
   - Inmate Profiles (CIC only)

5. **Outlooks Card**
   - Date and Action Plan fields
   - Consistent add/remove functionality

6. **Statements of Account Card**
   - Project-type-specific format
   - Consistent calculation logic
   - Consistent table structure

7. **Photos Card**
   - Consistent photo upload interface
   - Consistent description format
   - Max 10 photos

8. **Attachments Card**
   - Consistent attachment upload interface
   - Consistent name and description fields

---

## UI/UX Standardization

### Form Styling

**Readonly Fields:**
- Class: `readonly-input` or `form-control readonly-input`
- Background color: Light gray or specific color (#f8f9fa or #202ba3)
- Cursor: not-allowed

**Editable Fields:**
- Class: `form-control` or `form-control select-input`
- Background color: White or specific color (#202ba3 for selects)
- Standard Bootstrap styling

**Section Headers:**
- Consistent card structure
- Consistent header styling
- Consistent section numbering (if applicable)

### Button Standardization

**Primary Actions:**
- Class: `btn btn-primary`
- Text: "Submit Report", "Update Report", "Generate Report"

**Secondary Actions:**
- Class: `btn btn-secondary`
- Text: "Cancel", "Reset"

**Danger Actions:**
- Class: `btn btn-danger`
- Text: "Remove", "Delete"

**Success Actions:**
- Class: `btn btn-success`
- Text: "Approve", "Mark as Completed"

---

## Data Format Standardization

### Date Formats

**Display Format:**
- Month & Year: "January 2025" or "Jan 2025"
- Full Date: "January 15, 2025" or "15-01-2025"
- Database: "Y-m-d" (2025-01-15)

**Input Format:**
- Month: Dropdown (1-12) or month input
- Year: Dropdown or number input
- Date: Date picker (YYYY-MM-DD)

### Number Formats

**Display Format:**
- Currency: "Rs. 1,445,000.00" or "₱1,445,000.00"
- Decimal: "1,445,000.00" (2 decimal places)
- Integer: "1,445" (no decimals)

**Input Format:**
- Type: `number`
- Step: `0.01` for decimals, `1` for integers
- Min: `0` (for amounts)

---

## Implementation Checklist

### Task 4.3.1: Standardize Field Names ✅
- [x] Document standard field names
- [ ] Update all views to use standard field names
- [ ] Update controllers to use standard field names
- [ ] Update validation rules

### Task 4.3.2: Standardize Section Ordering ✅
- [x] Document standard section order
- [ ] Update all report forms to follow standard order
- [ ] Ensure consistent card structure

### Task 4.3.3: Standardize Validation ✅
- [x] Document standard validation rules
- [ ] Create FormRequest classes for report validation
- [ ] Apply consistent validation across all report types

### Task 4.3.4: Standardize UI/UX ✅
- [x] Document standard styling
- [ ] Apply consistent styling across all forms
- [ ] Ensure consistent button placement and styling

### Task 4.3.5: Update Documentation ✅
- [x] Create standardization document
- [ ] Update user guides
- [ ] Add examples

---

## Files to Update

### Views
1. `resources/views/reports/monthly/ReportCommonForm.blade.php`
2. `resources/views/reports/monthly/developmentProject/reportform.blade.php`
3. `resources/views/reports/monthly/edit.blade.php`
4. All partials in `resources/views/reports/monthly/partials/`

### Controllers
1. `app/Http/Controllers/Reports/Monthly/ReportController.php`
2. Validation rules in `validateRequest()` method

### Form Requests (To Create)
1. `app/Http/Requests/Reports/Monthly/StoreMonthlyReportRequest.php`
2. `app/Http/Requests/Reports/Monthly/UpdateMonthlyReportRequest.php`

---

## Standardization Rules

### Rule 1: Field Name Consistency
- Use `report_month` and `report_year` (not `report_month_year` in forms)
- Calculate `report_month_year` in controller from `report_month` and `report_year`
- Use consistent array naming: `field_name[]` for arrays

### Rule 2: Section Ordering
- Always follow the standard section order
- Project-specific sections come after Objectives, before Outlooks
- Statements of Account always before Photos

### Rule 3: Validation Consistency
- All reports use the same base validation rules
- Project-specific validations are additions, not replacements
- Use FormRequest classes for complex validation

### Rule 4: Calculation Consistency
- Total Expenses = Expenses Last Month + Expenses This Month (always)
- Balance Amount = Total Amount - Total Expenses (always)
- Total Amount = Amount Forwarded + Amount Sanctioned (for development projects)

### Rule 5: UI Consistency
- All readonly fields use same styling
- All editable fields use same styling
- All buttons use consistent classes and placement
- All cards use consistent structure

---

## Next Steps

1. **Review and Approve:** Review this standardization document
2. **Implement Changes:** Update views, controllers, and validation
3. **Test:** Verify all report types follow standards
4. **Document:** Update user guides with standardized structure

---

**Status:** 📋 **STANDARDIZATION DOCUMENT CREATED**  
**Next:** Implementation of standardization changes
