# Phase-Wise Implementation Plan: Reports Save Draft Feature

## Overview

This document outlines the phase-wise implementation plan for adding "Save Draft" functionality to reports (both create and edit operations), following the established pattern from the projects module.

**Total Estimated Time:** 2-3 weeks  
**Priority:** High  
**Target:** Monthly Reports (can be extended to other report types later)

---

## Phase 1: Backend Foundation (Days 1-3)

### Objective
Set up backend infrastructure to handle draft saves, including validation logic and controller updates.

### Tasks

#### Task 1.1: Update StoreMonthlyReportRequest for Draft Saves
**File:** `app/Http/Requests/Reports/Monthly/StoreMonthlyReportRequest.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Add method to check if this is a draft save
2. Make validation rules conditional based on draft save
3. Keep `project_id` as required (essential for draft saves)
4. Make `report_month`, `report_year`, and `particulars` nullable when draft save

**Implementation:**
```php
public function rules(): array
{
    $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';
    
    return [
        'project_id' => 'required|string|max:255', // Always required
        'save_as_draft' => 'nullable|boolean',
        
        // Reporting period - required only if not draft
        'report_month' => $isDraft ? 'nullable|integer|between:1,12' : 'required|integer|between:1,12',
        'report_year' => $isDraft ? 'nullable|integer|min:2020|max:' . (date('Y') + 1) : 'required|integer|min:2020|max:' . (date('Y') + 1),
        
        // ... other fields remain nullable ...
        
        // Statements of Account - required only if not draft
        'particulars' => $isDraft ? 'nullable|array' : 'required|array',
        'particulars.*' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
        
        // ... rest of rules ...
    ];
}

public function messages(): array
{
    $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';
    
    $messages = [
        'project_id.required' => 'Project ID is required.',
        // ... other messages ...
    ];
    
    // Only add required messages if not draft
    if (!$isDraft) {
        $messages['report_month.required'] = 'Reporting month is required.';
        $messages['report_year.required'] = 'Reporting year is required.';
        $messages['particulars.required'] = 'At least one particular is required in Statements of Account.';
        $messages['particulars.*.required'] = 'Particular description is required.';
    }
    
    return $messages;
}
```

**Testing:**
- [ ] Test validation with `save_as_draft = 1` (fields should be nullable)
- [ ] Test validation without `save_as_draft` (fields should be required)
- [ ] Test that `project_id` is always required

---

#### Task 1.2: Update UpdateMonthlyReportRequest for Draft Saves
**File:** `app/Http/Requests/Reports/Monthly/UpdateMonthlyReportRequest.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Add method to check if this is a draft save
2. Make validation rules conditional based on draft save
3. Similar to Task 1.1 but for update operation

**Implementation:**
```php
public function rules(): array
{
    $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';
    
    return [
        'project_id' => 'required|string|max:255', // Always required
        'save_as_draft' => 'nullable|boolean',
        
        // Reporting period - required only if not draft
        'report_month' => $isDraft ? 'nullable|integer|between:1,12' : 'required|integer|between:1,12',
        'report_year' => $isDraft ? 'nullable|integer|min:2020|max:' . (date('Y') + 1) : 'required|integer|min:2020|max:' . (date('Y') + 1),
        
        // Statements of Account - required only if not draft
        'particulars' => $isDraft ? 'nullable|array' : 'required|array',
        'particulars.*' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
        
        // ... rest of rules (same as StoreMonthlyReportRequest) ...
    ];
}
```

**Testing:**
- [ ] Test validation with `save_as_draft = 1` (fields should be nullable)
- [ ] Test validation without `save_as_draft` (fields should be required)
- [ ] Test authorization still works correctly

---

#### Task 1.3: Update ReportController@store for Draft Saves
**File:** `app/Http/Controllers/Reports/Monthly/ReportController.php`  
**Estimated Time:** 3 hours

**Changes:**
1. Check for `save_as_draft` parameter after validation
2. Set report status to 'draft' when saving as draft
3. Redirect to edit page after draft save with success message
4. Do not send notifications for draft saves
5. Log activity for draft saves

**Implementation:**
```php
public function store(StoreMonthlyReportRequest $request)
{
    Log::info('Store method initiated', [
        'project_id' => $request->project_id,
        'report_month' => $request->report_month,
        'report_year' => $request->report_year,
        'save_as_draft' => $request->has('save_as_draft'),
    ]);

    DB::beginTransaction();
    try {
        $validatedData = $request->validated();
        $isDraftSave = $request->has('save_as_draft') && $request->input('save_as_draft') == '1';

        // Generate report_id
        $project_id = $validatedData['project_id'];
        $report_id = $this->generateReportId($project_id);

        // Create the main report
        $report = $this->createReport($validatedData, $report_id);

        // Handle additional report data (only if not empty/null)
        if (!$isDraftSave || !empty($validatedData['objective'])) {
            $this->storeObjectivesAndActivities($request, $report_id, $report);
        }
        if (!$isDraftSave || !empty($validatedData['particulars'])) {
            $this->handleAccountDetails($request, $report_id, $project_id);
        }
        $this->handleOutlooks($request, $report_id);
        $this->handlePhotos($request, $report_id);
        $this->handleSpecificProjectData($request, $report_id);
        $this->handleAttachments($request, $report);

        // Set status based on draft save
        if ($isDraftSave) {
            $report->status = DPReport::STATUS_DRAFT;
            $report->save();
            Log::info('Report saved as draft', ['report_id' => $report_id]);
        }

        DB::commit();
        Log::info('Transaction committed and report created successfully.');

        // Only send notifications if not a draft save
        if (!$isDraftSave) {
            $reportWithId = DPReport::where('report_id', $report_id)->first();
            $reportId = $reportWithId ? $reportWithId->getAttribute('id') : null;

            if ($reportId) {
                $project = Project::where('project_id', $project_id)->with('user')->first();
                if ($project) {
                    // Notify coordinators and provincial (existing logic)
                    // ... existing notification code ...
                }
            }
        }

        // Log activity
        $user = Auth::user();
        if ($isDraftSave) {
            ActivityHistoryService::logReportCreate($report, $user, 'Report saved as draft');
        } else {
            ActivityHistoryService::logReportCreate($report, $user, 'Report created');
        }

        // Redirect based on draft save
        if ($isDraftSave) {
            return redirect()->route('monthly.report.edit', $report_id)
                ->with('success', 'Report saved as draft. You can continue editing later.');
        }

        return redirect()->route('monthly.report.index')->with('success', 'Report submitted successfully.');
    } catch (ValidationException $ve) {
        // ... existing error handling ...
    } catch (\Exception $e) {
        // ... existing error handling ...
    }
}
```

**Testing:**
- [ ] Test creating report with `save_as_draft = 1`
- [ ] Test creating report without `save_as_draft` (normal flow)
- [ ] Verify status is set to 'draft' for draft saves
- [ ] Verify notifications are not sent for draft saves
- [ ] Verify activity is logged for draft saves
- [ ] Verify redirect to edit page for draft saves

---

#### Task 1.4: Update ReportController@update for Draft Saves
**File:** `app/Http/Controllers/Reports/Monthly/ReportController.php`  
**Estimated Time:** 3 hours

**Changes:**
1. Check for `save_as_draft` parameter
2. Maintain or set status to 'draft' when saving as draft
3. Redirect to edit page after draft save with success message
4. Do not change status if report was already submitted (unless business rules allow)

**Implementation:**
```php
public function update(UpdateMonthlyReportRequest $request, $report_id)
{
    Log::info('Update method initiated', [
        'report_id' => $report_id,
        'project_id' => $request->project_id,
        'save_as_draft' => $request->has('save_as_draft'),
    ]);

    DB::beginTransaction();
    try {
        $validatedData = $request->validated();
        $isDraftSave = $request->has('save_as_draft') && $request->input('save_as_draft') == '1';

        $user = Auth::user();
        
        // ... existing role-based filtering ...

        $report = $reportQuery->firstOrFail();

        // Update the main report
        $this->updateReport($validatedData, $report);

        // Handle updated data
        if (!$isDraftSave || !empty($validatedData['objective'])) {
            $this->storeObjectivesAndActivities($request, $report_id, $report);
        }
        if (!$isDraftSave || !empty($validatedData['particulars'])) {
            $this->handleAccountDetails($request, $report_id, $validatedData['project_id']);
        }
        $this->handleOutlooks($request, $report_id);
        $this->updatePhotos($request, $report_id);
        $this->handleSpecificProjectData($request, $report_id);
        $this->handleUpdateAttachments($request, $report);

        // Set status to draft if saving as draft
        if ($isDraftSave) {
            $report->status = DPReport::STATUS_DRAFT;
            $report->save();
            Log::info('Report saved as draft', ['report_id' => $report_id]);
        }

        DB::commit();

        $report->refresh();

        // Log activity
        if ($isDraftSave) {
            ActivityHistoryService::logReportUpdate($report, $user, 'Report saved as draft');
        } else {
            ActivityHistoryService::logReportUpdate($report, $user, 'Report details updated');
        }

        Log::info('Transaction committed and report updated successfully.');
        
        // Redirect based on draft save
        if ($isDraftSave) {
            return redirect()->route('monthly.report.edit', $report_id)
                ->with('success', 'Report saved as draft. You can continue editing later.');
        }

        return redirect()->route('monthly.report.index')->with('success', 'Report updated successfully.');
    } catch (ValidationException $ve) {
        // ... existing error handling ...
    } catch (\Exception $e) {
        // ... existing error handling ...
    }
}
```

**Testing:**
- [ ] Test updating report with `save_as_draft = 1`
- [ ] Test updating report without `save_as_draft` (normal flow)
- [ ] Verify status is set to 'draft' for draft saves
- [ ] Verify activity is logged for draft saves
- [ ] Verify redirect to edit page for draft saves
- [ ] Test with reports in different statuses (draft, submitted, etc.)

---

### Phase 1 Deliverables
- ✅ Updated `StoreMonthlyReportRequest` with conditional validation
- ✅ Updated `UpdateMonthlyReportRequest` with conditional validation
- ✅ Updated `ReportController@store` to handle draft saves
- ✅ Updated `ReportController@update` to handle draft saves
- ✅ Unit tests for validation logic
- ✅ Integration tests for controller methods

**Phase 1 Completion Criteria:**
- All backend changes implemented
- All tests passing
- Code reviewed and approved

---

## Phase 2: Frontend Implementation (Days 4-6)

### Objective
Add "Save as Draft" buttons and JavaScript handlers to both create and edit forms.

### Tasks

#### Task 2.1: Add "Save as Draft" Button to Create Form
**File:** `resources/views/reports/monthly/ReportAll.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Add "Save as Draft" button next to "Submit Report" button
2. Style button appropriately (secondary style)
3. Add button ID for JavaScript targeting

**Implementation:**
```blade
{{-- Around line 233, where Submit Report button is --}}
<div class="d-flex justify-content-end mt-4 mb-4">
    <button type="button" id="saveDraftBtn" class="btn btn-secondary me-2">
        <i class="fas fa-save me-2"></i>Save as Draft
    </button>
    <button type="submit" id="submitReportBtn" class="btn btn-primary">
        <i class="fas fa-paper-plane me-2"></i>Submit Report
    </button>
</div>
```

**Testing:**
- [ ] Verify button appears correctly
- [ ] Verify button styling matches design
- [ ] Verify button is visible and accessible

---

#### Task 2.2: Add JavaScript Handler for Create Form Draft Save
**File:** `resources/views/reports/monthly/ReportAll.blade.php`  
**Estimated Time:** 4 hours

**Changes:**
1. Add JavaScript to handle "Save as Draft" button click
2. Remove required attributes from form fields
3. Add hidden input for `save_as_draft` parameter
4. Enable disabled fields before submission
5. Show loading indicator during save
6. Handle form submission

**Implementation:**
```javascript
<script>
document.addEventListener('DOMContentLoaded', function() {
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const createForm = document.getElementById('createReportForm') || document.querySelector('form[action="{{ route('monthly.report.store') }}"]');
    const submitReportBtn = document.getElementById('submitReportBtn');

    // Handle "Save as Draft" button click
    if (saveDraftBtn && createForm) {
        saveDraftBtn.addEventListener('click', function(e) {
            try {
                e.preventDefault();
                
                // Remove required attributes temporarily to allow submission
                const requiredFields = createForm.querySelectorAll('[required]');
                const removedRequired = [];
                requiredFields.forEach(field => {
                    if (field.hasAttribute('required')) {
                        removedRequired.push(field);
                        field.removeAttribute('required');
                    }
                });
                
                // Add hidden input to indicate draft save
                let draftInput = createForm.querySelector('input[name="save_as_draft"]');
                if (!draftInput) {
                    draftInput = document.createElement('input');
                    draftInput.type = 'hidden';
                    draftInput.name = 'save_as_draft';
                    draftInput.value = '1';
                    createForm.appendChild(draftInput);
                } else {
                    draftInput.value = '1';
                }
                
                // Enable all disabled fields before submission to ensure their values are included
                const disabledFields = createForm.querySelectorAll('[disabled]');
                const enabledFields = [];
                disabledFields.forEach(field => {
                    if (field.disabled) {
                        enabledFields.push(field);
                        field.disabled = false;
                    }
                });
                
                // Show all hidden sections temporarily to ensure values are submitted
                const hiddenSections = createForm.querySelectorAll('[style*="display: none"]');
                hiddenSections.forEach(section => {
                    section.style.display = '';
                });
                
                // Show loading indicator
                saveDraftBtn.disabled = true;
                const originalText = saveDraftBtn.innerHTML;
                saveDraftBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving Draft...';
                
                // Submit form
                createForm.submit();
                
                // Note: If form submission fails, we won't reach here
                // But if it does, restore button state
                setTimeout(() => {
                    saveDraftBtn.disabled = false;
                    saveDraftBtn.innerHTML = originalText;
                    
                    // Restore required attributes (in case of validation error)
                    removedRequired.forEach(field => {
                        field.setAttribute('required', 'required');
                    });
                    
                    // Restore disabled state
                    enabledFields.forEach(field => {
                        field.disabled = true;
                    });
                }, 5000);
                
            } catch (error) {
                console.error('Draft save error:', error);
                saveDraftBtn.disabled = false;
                saveDraftBtn.innerHTML = 'Save as Draft';
                alert('An error occurred while saving the draft. Please try again.');
            }
        });
    }

    // Handle form submission to check if it's a draft save
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            // Check if this is a draft save (bypass validation)
            const isDraftSave = this.querySelector('input[name="save_as_draft"]');
            if (isDraftSave && isDraftSave.value === '1') {
                // Allow draft save without validation
                return true;
            }
            
            // For normal submission, allow HTML5 validation
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return false;
            }
        });
    }
});
</script>
```

**Testing:**
- [ ] Test clicking "Save as Draft" button
- [ ] Verify required attributes are removed
- [ ] Verify hidden input is added
- [ ] Verify form submits correctly
- [ ] Verify loading indicator shows
- [ ] Test with validation errors (should still work for draft)

---

#### Task 2.3: Add "Save as Draft" Button to Edit Form
**File:** `resources/views/reports/monthly/edit.blade.php`  
**Estimated Time:** 2 hours

**Changes:**
1. Add "Save as Draft" button next to "Update Report" button
2. Style button appropriately (secondary style)
3. Add button ID for JavaScript targeting

**Implementation:**
```blade
{{-- Around line 174, where Update Report button is --}}
<div class="d-flex justify-content-end mt-4 mb-4">
    <button type="button" id="saveDraftBtn" class="btn btn-secondary me-2">
        <i class="fas fa-save me-2"></i>Save as Draft
    </button>
    <button type="submit" id="updateReportBtn" class="btn btn-primary">
        <i class="fas fa-save me-2"></i>Update Report
    </button>
</div>
```

**Testing:**
- [ ] Verify button appears correctly
- [ ] Verify button styling matches design
- [ ] Verify button is visible and accessible

---

#### Task 2.4: Add JavaScript Handler for Edit Form Draft Save
**File:** `resources/views/reports/monthly/edit.blade.php`  
**Estimated Time:** 4 hours

**Changes:**
1. Add JavaScript to handle "Save as Draft" button click
2. Similar to Task 2.2 but for edit form
3. Handle form submission with PUT method

**Implementation:**
```javascript
<script>
document.addEventListener('DOMContentLoaded', function() {
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const editForm = document.getElementById('editReportForm') || document.querySelector('form[action*="update"]');
    const updateReportBtn = document.getElementById('updateReportBtn');

    // Handle "Save as Draft" button click
    if (saveDraftBtn && editForm) {
        saveDraftBtn.addEventListener('click', function(e) {
            try {
                e.preventDefault();
                
                // Remove required attributes temporarily
                const requiredFields = editForm.querySelectorAll('[required]');
                const removedRequired = [];
                requiredFields.forEach(field => {
                    if (field.hasAttribute('required')) {
                        removedRequired.push(field);
                        field.removeAttribute('required');
                    }
                });
                
                // Add hidden input to indicate draft save
                let draftInput = editForm.querySelector('input[name="save_as_draft"]');
                if (!draftInput) {
                    draftInput = document.createElement('input');
                    draftInput.type = 'hidden';
                    draftInput.name = 'save_as_draft';
                    draftInput.value = '1';
                    editForm.appendChild(draftInput);
                } else {
                    draftInput.value = '1';
                }
                
                // Enable all disabled fields
                const disabledFields = editForm.querySelectorAll('[disabled]');
                const enabledFields = [];
                disabledFields.forEach(field => {
                    if (field.disabled) {
                        enabledFields.push(field);
                        field.disabled = false;
                    }
                });
                
                // Show loading indicator
                saveDraftBtn.disabled = true;
                const originalText = saveDraftBtn.innerHTML;
                saveDraftBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving Draft...';
                
                // Submit form
                editForm.submit();
                
            } catch (error) {
                console.error('Draft save error:', error);
                saveDraftBtn.disabled = false;
                saveDraftBtn.innerHTML = 'Save as Draft';
                alert('An error occurred while saving the draft. Please try again.');
            }
        });
    }

    // Handle form submission
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const isDraftSave = this.querySelector('input[name="save_as_draft"]');
            if (isDraftSave && isDraftSave.value === '1') {
                return true; // Allow draft save
            }
            
            // For normal submission, validate
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return false;
            }
        });
    }
});
</script>
```

**Testing:**
- [ ] Test clicking "Save as Draft" button
- [ ] Verify required attributes are removed
- [ ] Verify hidden input is added
- [ ] Verify form submits correctly with PUT method
- [ ] Verify loading indicator shows
- [ ] Test with validation errors

---

### Phase 2 Deliverables
- ✅ "Save as Draft" button in create form
- ✅ "Save as Draft" button in edit form
- ✅ JavaScript handlers for both forms
- ✅ Loading indicators
- ✅ Error handling

**Phase 2 Completion Criteria:**
- All frontend changes implemented
- Buttons functional
- JavaScript tested in multiple browsers
- Code reviewed and approved

---

## Phase 3: Integration Testing (Days 7-9)

### Objective
Test the complete draft save functionality end-to-end, including edge cases and error scenarios.

### Tasks

#### Task 3.1: Create Report Draft Save Testing
**Estimated Time:** 4 hours

**Test Cases:**
1. Create report with minimal data (only project_id) - save as draft
2. Create report with partial data - save as draft
3. Create report with all data - save as draft (should still work)
4. Create report and save as draft, then edit and complete
5. Create report and save as draft, then edit and save as draft again
6. Verify draft report appears in index
7. Verify draft report is editable
8. Verify notifications are not sent for draft saves
9. Verify activity is logged for draft saves
10. Test with different project types

**Test Script:**
```php
// Test Case 1: Minimal data draft save
1. Navigate to create report page
2. Select project
3. Click "Save as Draft" (without filling any other fields)
4. Verify: Report created with status 'draft'
5. Verify: Redirected to edit page
6. Verify: Success message displayed
7. Verify: Report appears in index with draft status

// Test Case 2: Partial data draft save
1. Navigate to create report page
2. Fill project, month, year, some objectives
3. Click "Save as Draft"
4. Verify: Report saved with partial data
5. Verify: Can edit and complete later

// Test Case 3: Full data draft save
1. Fill all fields completely
2. Click "Save as Draft"
3. Verify: Report saved with all data
4. Verify: Status is still 'draft'
5. Verify: Can submit later

// Test Case 4: Draft to Complete flow
1. Create draft report (minimal data)
2. Edit draft report
3. Fill all required fields
4. Click "Update Report" (not draft)
5. Verify: Report updated and can be submitted

// Test Case 5: Multiple draft saves
1. Create draft report
2. Edit and make changes
3. Click "Save as Draft" again
4. Verify: Changes saved, status remains draft
```

---

#### Task 3.2: Edit Report Draft Save Testing
**Estimated Time:** 4 hours

**Test Cases:**
1. Edit existing draft report - save as draft
2. Edit existing submitted report - save as draft (if allowed by business rules)
3. Edit report, remove some required fields, save as draft
4. Edit report, make changes, save as draft
5. Verify status remains or changes to draft appropriately
6. Verify activity is logged

**Test Script:**
```php
// Test Case 1: Edit draft report
1. Open existing draft report for editing
2. Make changes
3. Click "Save as Draft"
4. Verify: Changes saved, status remains 'draft'
5. Verify: Redirected to edit page
6. Verify: Success message displayed

// Test Case 2: Edit submitted report as draft
1. Open submitted report (if editable)
2. Make changes
3. Click "Save as Draft"
4. Verify: Changes saved, status changes to 'draft' (if business rules allow)
5. Verify: Report can be edited further

// Test Case 3: Remove required fields and save as draft
1. Edit report with all fields filled
2. Remove report_month, report_year
3. Click "Save as Draft"
4. Verify: Report saved without validation errors
5. Verify: Can add fields back later
```

---

#### Task 3.3: Validation Testing
**Estimated Time:** 3 hours

**Test Cases:**
1. Test validation with `save_as_draft = 1` (fields should be nullable)
2. Test validation without `save_as_draft` (fields should be required)
3. Test that `project_id` is always required
4. Test edge cases (empty arrays, null values, etc.)

---

#### Task 3.4: Cross-Browser Testing
**Estimated Time:** 3 hours

**Test Browsers:**
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

**Test Cases:**
1. Draft save functionality works in all browsers
2. JavaScript handlers work correctly
3. Form submission works correctly
4. Loading indicators display correctly

---

#### Task 3.5: Error Handling Testing
**Estimated Time:** 2 hours

**Test Cases:**
1. Network error during draft save
2. Server error during draft save
3. Validation error (should still work for draft)
4. Database error handling
5. Transaction rollback on error

---

### Phase 3 Deliverables
- ✅ Complete test suite
- ✅ Test results documentation
- ✅ Bug reports (if any)
- ✅ Test coverage report

**Phase 3 Completion Criteria:**
- All test cases passing
- No critical bugs
- Cross-browser compatibility verified
- Error handling verified

---

## Phase 4: User Acceptance Testing & Documentation (Days 10-12)

### Objective
Get user feedback, fix any issues, and create documentation.

### Tasks

#### Task 4.1: User Acceptance Testing
**Estimated Time:** 4 hours

**Activities:**
1. Provide test environment to users
2. Create UAT test checklist
3. Gather user feedback
4. Document issues and feedback
5. Prioritize fixes

**UAT Checklist:**
- [ ] Users can save incomplete reports as drafts
- [ ] Users can edit draft reports
- [ ] Draft reports appear in report index
- [ ] Draft reports have clear status indicators
- [ ] User experience is intuitive
- [ ] No confusion between draft and submitted reports
- [ ] Performance is acceptable
- [ ] Works with all project types

---

#### Task 4.2: Bug Fixes
**Estimated Time:** 4 hours

**Activities:**
1. Fix critical bugs found in UAT
2. Fix high-priority bugs
3. Address user feedback
4. Re-test fixes

---

#### Task 4.3: Documentation
**Estimated Time:** 4 hours

**Documents to Create:**
1. **User Guide:**
   - How to save reports as drafts
   - How to edit draft reports
   - How to submit draft reports
   - Status indicators explanation

2. **Developer Guide:**
   - Implementation details
   - Code structure
   - Extension points
   - Testing guide

3. **Release Notes:**
   - New feature description
   - User benefits
   - Known limitations (if any)

---

### Phase 4 Deliverables
- ✅ UAT completed
- ✅ All critical bugs fixed
- ✅ User guide created
- ✅ Developer guide created
- ✅ Release notes created

**Phase 4 Completion Criteria:**
- User acceptance obtained
- Documentation complete
- All critical issues resolved

---

## Phase 5: Deployment & Monitoring (Days 13-14)

### Objective
Deploy to production and monitor for issues.

### Tasks

#### Task 5.1: Pre-Deployment Checklist
**Estimated Time:** 2 hours

**Checklist:**
- [ ] All code reviewed and approved
- [ ] All tests passing
- [ ] Documentation complete
- [ ] Database migrations tested (if any)
- [ ] Backup strategy in place
- [ ] Rollback plan prepared
- [ ] Deployment plan documented

---

#### Task 5.2: Deployment
**Estimated Time:** 2 hours

**Activities:**
1. Deploy to staging environment
2. Run smoke tests
3. Deploy to production
4. Verify deployment
5. Monitor logs

---

#### Task 5.3: Post-Deployment Monitoring
**Estimated Time:** Ongoing (first week)

**Monitor:**
1. Error logs for draft save operations
2. User feedback
3. Performance metrics
4. Database queries
5. Any issues reported

---

### Phase 5 Deliverables
- ✅ Feature deployed to production
- ✅ Monitoring in place
- ✅ Issue tracking setup

**Phase 5 Completion Criteria:**
- Feature live in production
- No critical issues
- Users can successfully use the feature

---

## Summary

### Timeline
- **Phase 1:** Days 1-3 (Backend Foundation)
- **Phase 2:** Days 4-6 (Frontend Implementation)
- **Phase 3:** Days 7-9 (Integration Testing)
- **Phase 4:** Days 10-12 (UAT & Documentation)
- **Phase 5:** Days 13-14 (Deployment)

**Total:** 14 days (2-3 weeks)

### Resources Required
- 1 Backend Developer (Phases 1, 3)
- 1 Frontend Developer (Phase 2)
- 1 QA Engineer (Phase 3)
- 1 Technical Writer (Phase 4)
- DevOps Support (Phase 5)

### Risks & Mitigation
1. **Risk:** Validation logic complexity
   - **Mitigation:** Follow established pattern from projects module

2. **Risk:** JavaScript conflicts
   - **Mitigation:** Use namespaced functions, test thoroughly

3. **Risk:** User confusion
   - **Mitigation:** Clear UI indicators, comprehensive documentation

### Success Metrics
- ✅ Users can save incomplete reports as drafts
- ✅ Draft save functionality works for both create and edit
- ✅ No breaking changes to existing functionality
- ✅ User satisfaction with the feature
- ✅ No increase in support tickets related to data loss

---

## Appendix

### A. Code References

**Projects Save Draft Implementation:**
- Controller: `app/Http/Controllers/Projects/ProjectController.php` (lines 705-720)
- Create Form: `resources/views/projects/Oldprojects/createProjects.blade.php` (lines 415-458)
- Edit Form: `resources/views/projects/Oldprojects/edit.blade.php` (lines 147-188)
- Store Request: `app/Http/Requests/Projects/StoreProjectRequest.php`
- Update Request: `app/Http/Requests/Projects/UpdateProjectRequest.php`

**Reports Current Implementation:**
- Controller: `app/Http/Controllers/Reports/Monthly/ReportController.php`
- Create Form: `resources/views/reports/monthly/ReportAll.blade.php`
- Edit Form: `resources/views/reports/monthly/edit.blade.php`
- Store Request: `app/Http/Requests/Reports/Monthly/StoreMonthlyReportRequest.php`
- Update Request: `app/Http/Requests/Reports/Monthly/UpdateMonthlyReportRequest.php`
- Model: `app/Models/Reports/Monthly/DPReport.php`

### B. Testing Checklist Template

```markdown
## Test Case: [Name]
**Priority:** [High/Medium/Low]
**Status:** [Pass/Fail/Blocked]

**Steps:**
1. 
2. 
3. 

**Expected Result:**
- 

**Actual Result:**
- 

**Notes:**
- 
```

### C. Deployment Checklist

```markdown
## Pre-Deployment
- [ ] Code reviewed
- [ ] Tests passing
- [ ] Documentation updated
- [ ] Database backup taken
- [ ] Rollback plan prepared

## Deployment
- [ ] Deploy to staging
- [ ] Smoke tests passed
- [ ] Deploy to production
- [ ] Verify deployment

## Post-Deployment
- [ ] Monitor logs
- [ ] Check error rates
- [ ] Gather user feedback
- [ ] Document any issues
```

---

**Document Version:** 1.0  
**Last Updated:** 2025-01-XX  
**Status:** Ready for Implementation
