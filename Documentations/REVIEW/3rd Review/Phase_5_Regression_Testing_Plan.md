# Phase 5: Regression Testing Plan
## Type Hint Fix Verification - ProjectController Store/Update Flows

**Date:** 2024-12-XX  
**Status:** Ready for Testing  
**Objective:** Verify that all type hint fixes work correctly across all project types in both `store()` and `update()` flows.

---

## Overview

This document outlines the comprehensive regression testing plan to verify that the type hint normalization fixes (Phases 1-4) work correctly across all project types. The fixes ensure that sub-controllers accept `FormRequest` instead of specific FormRequest classes, allowing `ProjectController` to pass `StoreProjectRequest`/`UpdateProjectRequest` without type errors.

---

## Project Types to Test

### Institutional Project Types (8 types)
1. **Rural-Urban-Tribal** (`ProjectType::RURAL_URBAN_TRIBAL`)
2. **CHILD CARE INSTITUTION** (`ProjectType::CHILD_CARE_INSTITUTION`)
3. **Institutional Ongoing Group Educational proposal** (`ProjectType::INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL`)
4. **Livelihood Development Projects** (`ProjectType::LIVELIHOOD_DEVELOPMENT_PROJECTS`)
5. **Residential Skill Training Proposal 2** (`ProjectType::RESIDENTIAL_SKILL_TRAINING`)
6. **Development Projects** (`ProjectType::DEVELOPMENT_PROJECTS`)
7. **NEXT PHASE - DEVELOPMENT PROPOSAL** (`ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL`)
8. **PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER** (`ProjectType::CRISIS_INTERVENTION_CENTER`)

### Individual Project Types (4 types)
1. **Individual - Ongoing Educational support** (`ProjectType::INDIVIDUAL_ONGOING_EDUCATIONAL`)
2. **Individual - Livelihood Application** (`ProjectType::INDIVIDUAL_LIVELIHOOD_APPLICATION`)
3. **Individual - Access to Health** (`ProjectType::INDIVIDUAL_ACCESS_TO_HEALTH`)
4. **Individual - Initial - Educational support** (`ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL`)

---

## Test Scenarios

### Test Category 1: Store Flow (Project Creation)

#### Scenario 1.1: Create New Project - All Types
**Objective:** Verify that creating a new project for each type works without type errors.

**Steps:**
1. Navigate to project creation form
2. Select project type
3. Fill in all required fields
4. Submit form
5. Verify no `TypeError` in logs
6. Verify project is created successfully
7. Verify all sub-controllers are called correctly

**Expected Result:**
- ✅ No `TypeError: Argument #1 ($request) must be of type...` errors
- ✅ Project created in database
- ✅ All related data saved (general info, key info, type-specific data)
- ✅ Success message displayed
- ✅ Redirect to project list or edit page

**Controllers Tested (per type):**
- `GeneralInfoController::store()`
- `KeyInformationController::store()`
- Type-specific controllers (see detailed list below)

---

#### Scenario 1.2: Create Project as Draft
**Objective:** Verify draft save functionality works.

**Steps:**
1. Create project with "Save as Draft" option
2. Verify project status is set to `DRAFT`
3. Verify project can be edited later

**Expected Result:**
- ✅ No type errors
- ✅ Project saved with `status = 'draft'`
- ✅ Can edit project later

---

### Test Category 2: Update Flow (Project Editing)

#### Scenario 2.1: Update Existing Project - All Types
**Objective:** Verify that updating an existing project for each type works without type errors.

**Steps:**
1. Navigate to existing project edit page
2. Modify project data
3. Submit update form
4. Verify no `TypeError` in logs
5. Verify project is updated successfully

**Expected Result:**
- ✅ No `TypeError: Argument #1 ($request) must be of type...` errors
- ✅ Project updated in database
- ✅ All related data updated correctly
- ✅ Success message displayed
- ✅ Redirect to project list

**Controllers Tested (per type):**
- `GeneralInfoController::update()`
- `KeyInformationController::update()`
- Type-specific controllers (see detailed list below)

---

#### Scenario 2.2: Partial Update (Only Some Fields)
**Objective:** Verify that partial updates work correctly.

**Steps:**
1. Edit project and change only some fields
2. Submit update
3. Verify only changed fields are updated

**Expected Result:**
- ✅ No type errors
- ✅ Only modified fields updated
- ✅ Unchanged fields remain intact

---

### Test Category 3: Error Handling

#### Scenario 3.1: Validation Errors
**Objective:** Verify that validation errors are handled correctly.

**Steps:**
1. Submit form with invalid data
2. Verify validation errors are displayed
3. Verify no type errors occur

**Expected Result:**
- ✅ Validation errors displayed
- ✅ No type errors in logs
- ✅ Form data preserved (old input)

---

#### Scenario 3.2: Database Transaction Rollback
**Objective:** Verify that transaction rollback works on errors.

**Steps:**
1. Create scenario that causes database error
2. Verify transaction is rolled back
3. Verify no partial data saved

**Expected Result:**
- ✅ Transaction rolled back
- ✅ No partial data in database
- ✅ Error message displayed

---

## Detailed Controller Testing Matrix

### Institutional Project Types

#### 1. Rural-Urban-Tribal (EduRUT)
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `LogicalFrameworkController::store/update()`
- ✅ `SustainabilityController::store/update()`
- ✅ `BudgetController::store/update()`
- ✅ `AttachmentController::store/update()`
- ✅ `ProjectEduRUTBasicInfoController::store/update()`
- ✅ `EduRUTTargetGroupController::store/update()`
- ✅ `EduRUTAnnexedTargetGroupController::store/update()`

**Test Data Requirements:**
- General project info
- Logical framework objectives
- Sustainability details
- Budget items
- Attachments (optional)
- EduRUT basic info
- Target group data (multiple entries)
- Annexed target group data (multiple entries)

---

#### 2. CHILD CARE INSTITUTION (CCI)
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `LogicalFrameworkController::store/update()`
- ✅ `SustainabilityController::store/update()`
- ✅ `BudgetController::store/update()`
- ✅ `AttachmentController::store/update()`
- ✅ `CCIAchievementsController::store/update()`
- ✅ `CCIAgeProfileController::store/update()`
- ✅ `CCIAnnexedTargetGroupController::store/update()`
- ✅ `CCIEconomicBackgroundController::store/update()`
- ✅ `CCIPersonalSituationController::store/update()`
- ✅ `CCIPresentSituationController::store/update()`
- ✅ `CCIRationaleController::store/update()`
- ✅ `CCIStatisticsController::store/update()`

**Test Data Requirements:**
- All common institutional fields
- CCI achievements (academic, sport, other)
- Age profile data
- Annexed target group (multiple entries)
- Economic background
- Personal situation details
- Present situation
- Rationale
- Statistics data

---

#### 3. Institutional Ongoing Group Educational (IGE)
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `LogicalFrameworkController::store/update()`
- ✅ `SustainabilityController::store/update()`
- ✅ `BudgetController::store/update()`
- ✅ `AttachmentController::store/update()`
- ✅ `IGEInstitutionInfoController::store/update()`
- ✅ `IGEBeneficiariesSupportedController::store/update()`
- ✅ `IGENewBeneficiariesController::store/update()`
- ✅ `IGEOngoingBeneficiariesController::store/update()`
- ✅ `IGEBudgetController::store/update()`
- ✅ `IGEDevelopmentMonitoringController::store/update()`

**Test Data Requirements:**
- All common institutional fields
- Institution info
- Beneficiaries supported (multiple entries)
- New beneficiaries (multiple entries)
- Ongoing beneficiaries (multiple entries)
- IGE budget (multiple entries)
- Development monitoring details

---

#### 4. Livelihood Development Projects (LDP)
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `LogicalFrameworkController::store/update()`
- ✅ `SustainabilityController::store/update()`
- ✅ `BudgetController::store/update()`
- ✅ `AttachmentController::store/update()`
- ✅ `LDPInterventionLogicController::store/update()`
- ✅ `LDPNeedAnalysisController::store/update()`
- ✅ `LDPTargetGroupController::store/update()`

**Test Data Requirements:**
- All common institutional fields
- Intervention logic description
- Need analysis file upload
- Target group data (multiple entries)

---

#### 5. Residential Skill Training (RST)
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `LogicalFrameworkController::store/update()`
- ✅ `SustainabilityController::store/update()`
- ✅ `BudgetController::store/update()`
- ✅ `AttachmentController::store/update()`
- ✅ `RSTBeneficiariesAreaController::store/update()`
- ✅ `RSTGeographicalAreaController::store/update()`
- ✅ `RSTInstitutionInfoController::store/update()`
- ✅ `RSTTargetGroupAnnexureController::store/update()`
- ✅ `RSTTargetGroupController::store/update()`

**Test Data Requirements:**
- All common institutional fields
- Beneficiaries area (multiple entries)
- Geographical area (multiple entries)
- Institution info
- Target group annexure (multiple entries)
- Target group data

---

#### 6. Development Projects
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `LogicalFrameworkController::store/update()`
- ✅ `SustainabilityController::store/update()`
- ✅ `BudgetController::store/update()`
- ✅ `AttachmentController::store/update()`
- ✅ `RSTBeneficiariesAreaController::store/update()`

**Test Data Requirements:**
- All common institutional fields
- Beneficiaries area (multiple entries)

---

#### 7. NEXT PHASE - DEVELOPMENT PROPOSAL
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `LogicalFrameworkController::store/update()`
- ✅ `SustainabilityController::store/update()`
- ✅ `BudgetController::store/update()`
- ✅ `AttachmentController::store/update()`
- ✅ `RSTBeneficiariesAreaController::store/update()`

**Test Data Requirements:**
- All common institutional fields
- Beneficiaries area (multiple entries)

---

#### 8. Crisis Intervention Center (CIC)
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `LogicalFrameworkController::store/update()`
- ✅ `SustainabilityController::store/update()`
- ✅ `BudgetController::store/update()`
- ✅ `AttachmentController::store/update()`
- ✅ `CICBasicInfoController::store/update()`

**Test Data Requirements:**
- All common institutional fields
- CIC basic info

---

### Individual Project Types

#### 9. Individual - Ongoing Educational support (IES)
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `IESPersonalInfoController::store/update()`
- ✅ `IESFamilyWorkingMembersController::store/update()`
- ✅ `IESImmediateFamilyDetailsController::store/update()`
- ✅ `IESEducationBackgroundController::store/update()`
- ✅ `IESExpensesController::store/update()`
- ✅ `IESAttachmentsController::store/update()`

**Test Data Requirements:**
- General project info
- Key information
- Personal info
- Family working members (multiple entries)
- Immediate family details
- Education background
- Expenses (with details)
- Attachments

---

#### 10. Individual - Livelihood Application (ILP)
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `ILPPersonalInfoController::store/update()`
- ✅ `ILPRevenueGoalsController::store/update()`
- ✅ `ILPStrengthWeaknessController::store/update()`
- ✅ `ILPRiskAnalysisController::store/update()`
- ✅ `ILPAttachedDocumentsController::store/update()`
- ✅ `ILPBudgetController::store/update()`

**Test Data Requirements:**
- General project info
- Key information
- Personal info
- Revenue goals (business plan items, annual income, annual expenses)
- Strengths and weaknesses
- Risk analysis
- Attached documents
- Budget (multiple entries)

---

#### 11. Individual - Access to Health (IAH)
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `IAHPersonalInfoController::store/update()`
- ✅ `IAHEarningMembersController::store/update()`
- ✅ `IAHHealthConditionController::store/update()`
- ✅ `IAHSupportDetailsController::store/update()`
- ✅ `IAHBudgetDetailsController::store/update()`
- ✅ `IAHDocumentsController::store/update()`

**Test Data Requirements:**
- General project info
- Key information
- Personal info
- Earning members (multiple entries)
- Health condition details
- Support details
- Budget details (multiple entries)
- Documents

---

#### 12. Individual - Initial - Educational support (IIES)
**Controllers to Test:**
- ✅ `GeneralInfoController::store/update()`
- ✅ `KeyInformationController::store/update()`
- ✅ `IIESPersonalInfoController::store/update()`
- ✅ `IIESFamilyWorkingMembersController::store/update()`
- ✅ `IIESImmediateFamilyDetailsController::store/update()`
- ✅ `IIESEducationBackgroundController::store/update()`
- ✅ `IIESFinancialSupportController::store/update()`
- ✅ `IIESAttachmentsController::store/update()`
- ✅ `IIESExpensesController::store/update()`

**Test Data Requirements:**
- General project info
- Key information
- Personal info
- Family working members (multiple entries)
- Immediate family details
- Education background
- Financial support details
- Attachments
- Expenses (with details)

---

## Testing Checklist

### Pre-Testing Setup
- [ ] Clear Laravel log file (`storage/logs/laravel.log`)
- [ ] Ensure database is accessible
- [ ] Verify all migrations are run
- [ ] Create test user accounts (executor, applicant, provincial, coordinator)
- [ ] Backup database (optional but recommended)

### For Each Project Type (12 types × 2 flows = 24 test cases)

#### Store Flow Testing
- [ ] **Test 1.1:** Create new project (full form submission)
  - [ ] Fill all required fields
  - [ ] Submit form
  - [ ] Check Laravel log for `TypeError` messages
  - [ ] Verify project created in database
  - [ ] Verify all related data saved
  - [ ] Verify success message displayed

- [ ] **Test 1.2:** Create project as draft
  - [ ] Fill form and select "Save as Draft"
  - [ ] Submit form
  - [ ] Check Laravel log for errors
  - [ ] Verify project status is `draft`
  - [ ] Verify project can be edited later

- [ ] **Test 1.3:** Create project with validation errors
  - [ ] Submit form with invalid data
  - [ ] Check Laravel log for errors
  - [ ] Verify validation errors displayed
  - [ ] Verify no type errors

#### Update Flow Testing
- [ ] **Test 2.1:** Update existing project (full form submission)
  - [ ] Navigate to edit page
  - [ ] Modify all fields
  - [ ] Submit form
  - [ ] Check Laravel log for `TypeError` messages
  - [ ] Verify project updated in database
  - [ ] Verify all related data updated
  - [ ] Verify success message displayed

- [ ] **Test 2.2:** Partial update (only some fields)
  - [ ] Navigate to edit page
  - [ ] Modify only some fields
  - [ ] Submit form
  - [ ] Check Laravel log for errors
  - [ ] Verify only modified fields updated
  - [ ] Verify unchanged fields remain intact

- [ ] **Test 2.3:** Update with validation errors
  - [ ] Navigate to edit page
  - [ ] Enter invalid data
  - [ ] Submit form
  - [ ] Check Laravel log for errors
  - [ ] Verify validation errors displayed
  - [ ] Verify no type errors

---

## Error Monitoring

### What to Check in Laravel Logs

#### ✅ Success Indicators
- No `TypeError` messages
- No `Argument #1 ($request) must be of type...` errors
- Successful database commits
- Proper logging of each controller call

#### ❌ Failure Indicators
- `TypeError: Argument #1 ($request) must be of type...`
- `Call to undefined method validated()`
- Database transaction rollbacks
- Missing data in database after submission

### Log Search Commands

```bash
# Search for type errors
grep -i "TypeError" storage/logs/laravel.log

# Search for specific controller errors
grep -i "Controller.*update.*Argument" storage/logs/laravel.log
grep -i "Controller.*store.*Argument" storage/logs/laravel.log

# Search for successful operations
grep -i "saved successfully\|updated successfully" storage/logs/laravel.log
```

---

## Test Execution Log

### Test Results Template

```
Project Type: [TYPE_NAME]
Test Date: [DATE]
Tester: [NAME]

Store Flow:
- Test 1.1 (Full Create): [PASS/FAIL] - Notes: [NOTES]
- Test 1.2 (Draft Save): [PASS/FAIL] - Notes: [NOTES]
- Test 1.3 (Validation Errors): [PASS/FAIL] - Notes: [NOTES]

Update Flow:
- Test 2.1 (Full Update): [PASS/FAIL] - Notes: [NOTES]
- Test 2.2 (Partial Update): [PASS/FAIL] - Notes: [NOTES]
- Test 2.3 (Validation Errors): [PASS/FAIL] - Notes: [NOTES]

Errors Found:
- [LIST ANY ERRORS]

Overall Status: [PASS/FAIL]
```

---

## Known Issues & Workarounds

### Issue 1: Budget Fields Calculation
**Status:** Fixed in previous phases  
**Description:** Budget fields (amount_forwarded, amount_sanctioned, opening_balance) now calculate correctly.  
**Verification:** Check that budget calculations work in create/edit forms.

### Issue 2: File Uploads
**Status:** Should work with FormRequest  
**Description:** File uploads should work correctly with the new FormRequest type hints.  
**Verification:** Test file uploads for all project types that support attachments.

---

## Success Criteria

### Phase 5 is considered complete when:

1. ✅ All 12 project types can be created without type errors
2. ✅ All 12 project types can be updated without type errors
3. ✅ No `TypeError` messages in Laravel logs for any project type
4. ✅ All sub-controllers accept `FormRequest` correctly
5. ✅ Validation works correctly for all project types
6. ✅ Database transactions complete successfully
7. ✅ All related data is saved/updated correctly

---

## Post-Testing Actions

### If All Tests Pass:
1. ✅ Mark Phase 5 as complete
2. ✅ Document any edge cases found
3. ✅ Proceed to Phase 6 (Cleanup & Documentation)

### If Tests Fail:
1. ❌ Document the failure
2. ❌ Identify which controller(s) failed
3. ❌ Check if the fix was applied correctly
4. ❌ Re-apply fix if needed
5. ❌ Re-test the specific failing scenario

---

## Notes

- This testing should be performed in a development/staging environment
- Consider using database transactions or test database for safety
- Keep detailed logs of all test executions
- Document any unexpected behaviors or edge cases
- Update this document with actual test results

---

## Related Documents

- `TypeHint_Mismatch_Audit.md` - Initial audit of type hint issues
- `Budget_Fields_Analysis_and_Documentation.md` - Budget fields documentation
- Previous review documents in `@Documentations/REVIEW/`

---

**End of Testing Plan**

