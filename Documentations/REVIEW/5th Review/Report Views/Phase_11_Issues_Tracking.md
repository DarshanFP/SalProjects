# Phase 11: Issues Tracking Document
## Report Views Enhancement - Field Indexing & Card UI

**Date Started:** January 2025  
**Status:** In Progress  
**Last Updated:** _________________

---

## Issue Tracking Template

| ID | Project Type | Section | Severity | Description | Status | Fixed By | Date Fixed | Notes |
|----|--------------|---------|----------|-------------|--------|----------|------------|-------|
| #001 | | | | | | | | |
| #002 | | | | | | | | |

---

## Severity Levels

- **Critical:** Blocks functionality, prevents form submission, data loss
- **High:** Major functionality broken, affects user experience significantly
- **Medium:** Minor functionality issue, workaround available
- **Low:** Cosmetic issue, minor inconvenience

---

## Status Values

- **Open:** Issue identified, not yet fixed
- **In Progress:** Currently being worked on
- **Fixed:** Issue resolved, needs verification
- **Verified:** Issue fixed and tested
- **Won't Fix:** Issue acknowledged but not fixing (with reason)

---

## Issues Log

### Issue #001
**Project Type:**  
**Section:**  
**Severity:**  
**Description:**  
**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Behavior:**  

**Actual Behavior:**  

**Browser/Environment:**  

**Screenshot/Console Error:**  

**Status:** Open  
**Assigned To:**  
**Date Reported:**  

---

### Issue #002
**Project Type:**  
**Section:**  
**Severity:**  
**Description:**  
**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Behavior:**  

**Actual Behavior:**  

**Browser/Environment:**  

**Screenshot/Console Error:**  

**Status:** Open  
**Assigned To:**  
**Date Reported:**  

---

## Quick Issue Entry Template

```
### Issue #XXX
**Project Type:** [Project Type Name]
**Section:** [Outlook/Statements/Photos/Activities/Attachments/Annexure]
**Severity:** [Critical/High/Medium/Low]
**Description:** [Brief description]

**Steps to Reproduce:**
1. 
2. 
3. 

**Expected:** 
**Actual:** 
**Browser:** 
**Console Error:** 
**Status:** Open
```

---

## Common Issues Reference

### JavaScript Function Not Defined
**Symptoms:** Console error like "reindexOutlooks is not defined"  
**Likely Cause:** Function not included in script tag or scope issue  
**Fix:** Ensure function is defined before use, check script order

### Index Numbers Not Updating
**Symptoms:** Badges show wrong numbers after add/remove  
**Likely Cause:** Reindexing function not called or incorrect logic  
**Fix:** Verify reindex function is called after add/remove operations

### Cards Not Expanding/Collapsing
**Symptoms:** Clicking card header doesn't toggle form visibility  
**Likely Cause:** JavaScript event handler not attached or CSS issue  
**Fix:** Check toggleActivityCard function and CSS display properties

### Status Badge Not Updating
**Symptoms:** Status badge stays "Empty" even after filling fields  
**Likely Cause:** Event listeners not attached or updateActivityStatus not called  
**Fix:** Verify event listeners are attached to all activity fields

### Form Submission Fails
**Symptoms:** Form doesn't submit or validation errors  
**Likely Cause:** Field names incorrect after reindexing or missing required fields  
**Fix:** Verify field names update correctly in reindex functions

### Calculations Broken
**Symptoms:** Totals/balances incorrect after adding/removing rows  
**Likely Cause:** Calculation functions not called after reindexing  
**Fix:** Ensure calculateTotal() or similar functions called after reindex

---

## Testing Progress Tracker

### Individual Project Types (4 types)

- [ ] Individual - Livelihood Application (ILP)
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

- [ ] Individual - Access to Health (IAH)
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

- [ ] Individual - Ongoing Educational support (IES)
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

- [ ] Individual - Initial - Educational support (IIES)
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

### Institutional Project Types (8 types)

- [ ] Development Projects
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

- [ ] Livelihood Development Projects (LDP)
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Annexure section tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

- [ ] Residential Skill Training Proposal 2 (RST)
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

- [ ] PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER (CIC)
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

- [ ] CHILD CARE INSTITUTION
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

- [ ] Rural-Urban-Tribal
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

- [ ] Institutional Ongoing Group Educational proposal (IGE)
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

- [ ] NEXT PHASE - DEVELOPMENT PROPOSAL
  - [ ] Create mode tested
  - [ ] Edit mode tested
  - [ ] Issues found: ___
  - [ ] Status: ⬜ Pass / ⬜ Fail

---

## Summary Statistics

**Total Issues Found:** ___  
**Critical Issues:** ___  
**High Issues:** ___  
**Medium Issues:** ___  
**Low Issues:** ___

**Issues Fixed:** ___  
**Issues Remaining:** ___  
**Issues Verified:** ___

**Overall Testing Status:** ⬜ Complete / ⬜ In Progress / ⬜ Blocked

---

## Notes & Observations

### General Observations
- 

### Performance Notes
- 

### Browser-Specific Issues
- 

### Recommendations
- 

---

**Document Version:** 1.0  
**Last Updated:** January 2025
