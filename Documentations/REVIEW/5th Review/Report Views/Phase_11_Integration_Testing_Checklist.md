# Phase 11: Integration Testing - All Project Types

**Date:** January 2025  
**Status:** In Progress  
**Duration:** 4 hours  
**Priority:** High

---

## Testing Overview

This document provides a comprehensive testing checklist for all 12 project types to verify:
1. Field indexing works correctly
2. Activity card UI functions properly
3. Form submission works with indexed fields
4. Edit functionality works correctly
5. Status management flow works
6. Cross-browser compatibility

---

## Pre-Testing Setup

### Prerequisites
- [ ] Development environment is running
- [ ] Database has test data for all project types
- [ ] Test user accounts available (Executor, Provincial, Coordinator)
- [ ] Browser developer tools ready for debugging

### Test Data Requirements
- [ ] At least one project of each type exists
- [ ] Projects have multiple objectives and activities
- [ ] Some projects have existing reports (for edit testing)

---

## Testing Checklist by Project Type

### 1. Development Projects

**Project Type:** `Development Projects`  
**Budget Strategy:** DirectMappingStrategy  
**Special Features:** None

#### Create Report Testing
- [ ] **Outlook Section**
  - [ ] Index badges display correctly (1, 2, 3...)
  - [ ] Add outlook - index updates correctly
  - [ ] Remove outlook - reindexing works
  - [ ] Form submission includes all outlook entries

- [ ] **Statements of Account**
  - [ ] "No." column displays correctly
  - [ ] Initial rows show correct index numbers
  - [ ] Add row - index updates correctly
  - [ ] Remove row - reindexing works
  - [ ] Form submission includes all account rows

- [ ] **Photos Section**
  - [ ] Photo group index badges display (1, 2, 3...)
  - [ ] Add photo group - index updates
  - [ ] Remove photo group - reindexing works
  - [ ] Upload photos works correctly

- [ ] **Activities Section**
  - [ ] Activity cards display with index badges
  - [ ] Cards are collapsed by default
  - [ ] Click card - expands/collapses correctly
  - [ ] Status badges update (Empty → In Progress → Complete)
  - [ ] Scheduled months display correctly
  - [ ] Form submission includes all activity data

- [ ] **Attachments Section**
  - [ ] Attachment index badges display
  - [ ] Add attachment - index updates
  - [ ] Remove attachment - reindexing works
  - [ ] File upload works correctly

#### Edit Report Testing
- [ ] Edit existing report loads correctly
- [ ] All index numbers display correctly
- [ ] Activity cards work in edit mode
- [ ] Update report saves correctly
- [ ] Index numbers persist after save

#### Status Management Testing
- [ ] Submit report (draft → submitted_to_provincial)
- [ ] Forward report (submitted_to_provincial → forwarded_to_coordinator)
- [ ] Approve report (forwarded_to_coordinator → approved_by_coordinator)
- [ ] Revert report with reason
- [ ] Status changes don't affect indexed fields

---

### 2. Livelihood Development Projects

**Project Type:** `Livelihood Development Projects`  
**Budget Strategy:** SingleSourceContributionStrategy  
**Special Features:** Annexure section with Impact Groups

#### Create Report Testing
- [ ] **Annexure Section (Impact Groups)**
  - [ ] Impact group index badges display (1, 2, 3...)
  - [ ] S No. field updates correctly
  - [ ] Add impact group - reindexing works
  - [ ] Remove impact group - reindexing works
  - [ ] Form submission includes all impact groups

- [ ] All common sections (Outlook, Statements, Photos, Activities, Attachments) work as in Development Projects

#### Edit Report Testing
- [ ] Edit existing report with annexure data
- [ ] Impact groups display correctly
- [ ] Index numbers persist
- [ ] Update works correctly

---

### 3. Institutional Ongoing Group Educational proposal

**Project Type:** `Institutional Ongoing Group Educational proposal`  
**Budget Strategy:** DirectMappingStrategy  
**Special Features:** Age Profile table (static, no indexing needed)

#### Create Report Testing
- [ ] Age Profile table displays correctly (static table)
- [ ] All common sections work correctly
- [ ] Form submission includes age profile data

#### Edit Report Testing
- [ ] Edit existing report with age profile data
- [ ] All sections work correctly

---

### 4. Residential Skill Training Proposal 2

**Project Type:** `Residential Skill Training Proposal 2`  
**Budget Strategy:** DirectMappingStrategy  
**Special Features:** Trainee Profile table (static, no indexing needed)

#### Create Report Testing
- [ ] Trainee Profile table displays correctly (static table)
- [ ] All common sections work correctly
- [ ] Form submission includes trainee profile data

#### Edit Report Testing
- [ ] Edit existing report with trainee profile data
- [ ] All sections work correctly

---

### 5. PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER

**Project Type:** `PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER`  
**Budget Strategy:** DirectMappingStrategy  
**Special Features:** Inmates Profile table (static, no indexing needed)

#### Create Report Testing
- [ ] Inmates Profile table displays correctly (static table)
- [ ] All common sections work correctly
- [ ] Form submission includes inmate profile data

#### Edit Report Testing
- [ ] Edit existing report with inmate profile data
- [ ] All sections work correctly

---

### 6. Individual - Livelihood Application (ILP)

**Project Type:** `Individual - Livelihood Application`  
**Budget Strategy:** SingleSourceContributionStrategy  
**Special Features:** None

#### Create Report Testing
- [ ] All common sections work correctly
- [ ] Statements of Account uses ILP budget structure
- [ ] Form submission works correctly

#### Edit Report Testing
- [ ] Edit existing report works correctly
- [ ] All sections work correctly

---

### 7. Individual - Access to Health (IAH)

**Project Type:** `Individual - Access to Health`  
**Budget Strategy:** SingleSourceContributionStrategy  
**Special Features:** None

#### Create Report Testing
- [ ] All common sections work correctly
- [ ] Statements of Account uses IAH budget structure
- [ ] Form submission works correctly

#### Edit Report Testing
- [ ] Edit existing report works correctly
- [ ] All sections work correctly

---

### 8. Individual - Ongoing Educational support (IES)

**Project Type:** `Individual - Ongoing Educational support`  
**Budget Strategy:** MultipleSourceContributionStrategy  
**Special Features:** None

#### Create Report Testing
- [ ] All common sections work correctly
- [ ] Statements of Account uses IES expense structure
- [ ] Form submission works correctly

#### Edit Report Testing
- [ ] Edit existing report works correctly
- [ ] All sections work correctly

---

### 9. Individual - Initial - Educational support (IIES)

**Project Type:** `Individual - Initial - Educational support`  
**Budget Strategy:** MultipleSourceContributionStrategy  
**Special Features:** None

#### Create Report Testing
- [ ] All common sections work correctly
- [ ] Statements of Account uses IIES expense structure
- [ ] Form submission works correctly

#### Edit Report Testing
- [ ] Edit existing report works correctly
- [ ] All sections work correctly

---

### 10-12. Other Project Types

**Project Types:**
- CHILD CARE INSTITUTION
- Rural-Urban-Tribal
- NEXT PHASE - DEVELOPMENT PROPOSAL

#### Create Report Testing
- [ ] All common sections work correctly
- [ ] Form submission works correctly

#### Edit Report Testing
- [ ] Edit existing report works correctly
- [ ] All sections work correctly

---

## Cross-Feature Testing

### Field Indexing - All Sections

#### Outlook Section
- [ ] Index badges visible and correct
- [ ] Add/remove works correctly
- [ ] Reindexing works correctly
- [ ] Form submission includes all outlooks

#### Statements of Account
- [ ] "No." column visible in all 7 partials
- [ ] Index numbers correct
- [ ] Add/remove rows works
- [ ] Reindexing works
- [ ] Form submission includes all rows

#### Photos
- [ ] Index badges visible
- [ ] Add/remove photo groups works
- [ ] Reindexing works
- [ ] File upload works

#### Activities
- [ ] Index badges visible
- [ ] Add/remove activities works
- [ ] Reindexing works
- [ ] Form submission includes all activities

#### Attachments
- [ ] Index badges visible
- [ ] Add/remove attachments works
- [ ] Reindexing works
- [ ] File upload works

---

### Activity Card UI - All Project Types

#### Card Display
- [ ] Cards are collapsed by default
- [ ] Cards show activity name
- [ ] Cards show scheduled months
- [ ] Cards show status badge (Empty/In Progress/Complete)
- [ ] Index badges visible on cards

#### Card Interaction
- [ ] Click card header expands form
- [ ] Click again collapses form
- [ ] Multiple cards can be open simultaneously
- [ ] Toggle icon changes (chevron down/up)
- [ ] Active state styling works

#### Status Updates
- [ ] Status updates when form fields are filled
- [ ] Empty → In Progress transition works
- [ ] In Progress → Complete transition works
- [ ] Status badge colors correct (warning/info/success)

#### Form Submission
- [ ] Collapsed cards still submit data
- [ ] All activity data saves correctly
- [ ] Status persists after save

---

## Form Submission Testing

### Create Report
- [ ] Submit report with all sections filled
- [ ] Verify data saves to database correctly
- [ ] Verify index numbers don't affect data structure
- [ ] Verify activity card data saves correctly
- [ ] Test with projects having many objectives/activities

### Edit Report
- [ ] Edit existing report
- [ ] Verify index numbers persist
- [ ] Verify activity cards work in edit mode
- [ ] Update report saves correctly
- [ ] Verify no data loss on update

---

## Status Management Testing

### Status Flow Testing
- [ ] **Draft → Submitted**
  - [ ] Executor can submit report
  - [ ] Status changes to `submitted_to_provincial`
  - [ ] Indexed fields remain intact

- [ ] **Submitted → Forwarded**
  - [ ] Provincial can forward report
  - [ ] Status changes to `forwarded_to_coordinator`
  - [ ] Indexed fields remain intact

- [ ] **Forwarded → Approved**
  - [ ] Coordinator can approve report
  - [ ] Status changes to `approved_by_coordinator`
  - [ ] Indexed fields remain intact

- [ ] **Revert Flow**
  - [ ] Provincial can revert with reason
  - [ ] Status changes to `reverted_by_provincial`
  - [ ] Executor can resubmit
  - [ ] Coordinator can revert with reason
  - [ ] Status changes to `reverted_by_coordinator`
  - [ ] Indexed fields remain intact

---

## Cross-Browser Testing

### Chrome
- [ ] All features work correctly
- [ ] No JavaScript errors in console
- [ ] Styling looks correct
- [ ] Form submission works

### Firefox
- [ ] All features work correctly
- [ ] No JavaScript errors in console
- [ ] Styling looks correct
- [ ] Form submission works

### Safari
- [ ] All features work correctly
- [ ] No JavaScript errors in console
- [ ] Styling looks correct
- [ ] Form submission works

### Edge
- [ ] All features work correctly
- [ ] No JavaScript errors in console
- [ ] Styling looks correct
- [ ] Form submission works

---

## Performance Testing

### Large Projects
- [ ] Test with project having 10+ objectives
- [ ] Test with project having 50+ activities
- [ ] Page loads in reasonable time
- [ ] Activity cards render correctly
- [ ] No performance degradation

### Many Dynamic Fields
- [ ] Test with 10+ outlook entries
- [ ] Test with 20+ account rows
- [ ] Test with 10+ photo groups
- [ ] Test with 10+ attachments
- [ ] Reindexing works efficiently

---

## Error Handling Testing

### Validation Errors
- [ ] Form validation works correctly
- [ ] Error messages display properly
- [ ] Index numbers don't interfere with validation
- [ ] Activity card data validates correctly

### Edge Cases
- [ ] Remove all outlook entries (keep at least one)
- [ ] Remove all account rows (keep at least one)
- [ ] Remove all activities (keep at least one)
- [ ] Remove all attachments (keep at least one)
- [ ] Test with empty form submission

---

## Regression Testing

### Existing Functionality
- [ ] Budget calculation still works correctly
- [ ] Project type specific data saves correctly
- [ ] Report export (PDF/Word) works correctly
- [ ] Report viewing works correctly
- [ ] Notifications work correctly

---

## Test Results Summary

### Test Execution Log

| Project Type | Create Report | Edit Report | Status Flow | Browser Test | Status |
|--------------|---------------|-------------|-------------|--------------|--------|
| Development Projects | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| Livelihood Development Projects | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| Institutional Ongoing Group Educational | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| Residential Skill Training | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| Crisis Intervention Center | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| ILP | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| IAH | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| IES | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| IIES | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| CHILD CARE INSTITUTION | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| Rural-Urban-Tribal | ⏳ | ⏳ | ⏳ | ⏳ | Pending |
| NEXT PHASE - DEVELOPMENT PROPOSAL | ⏳ | ⏳ | ⏳ | ⏳ | Pending |

**Legend:**
- ✅ Pass
- ❌ Fail
- ⏳ Pending
- ⚠️ Partial/Issues Found

---

## Issues Found

### Critical Issues
- [ ] None yet

### Medium Issues
- [ ] None yet

### Low Priority Issues
- [ ] None yet

---

## Test Completion Checklist

- [ ] All 12 project types tested for create report
- [ ] All 12 project types tested for edit report
- [ ] Status management flow tested
- [ ] Cross-browser testing completed
- [ ] Performance testing completed
- [ ] Error handling tested
- [ ] Regression testing completed
- [ ] All issues documented
- [ ] Test results documented

---

## Next Steps After Testing

1. **Fix any issues found** during testing
2. **Re-test** fixed issues
3. **Document** test results
4. **Proceed to Phase 12** (Documentation and Cleanup)

---

**Last Updated:** January 2025  
**Status:** Ready for Testing
