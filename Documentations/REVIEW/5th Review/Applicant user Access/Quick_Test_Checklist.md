# Quick Test Checklist - Applicant Access

## ⚡ Quick Start (5-Minute Test)

### Step 1: Verify Test Data
- [ ] Find an applicant user ID: `SELECT id FROM users WHERE role = 'applicant' LIMIT 1;`
- [ ] Find a project where applicant is NOT owner: `SELECT project_id FROM projects WHERE user_id != [APPLICANT_ID] LIMIT 1;`
- [ ] Make applicant in-charge: `UPDATE projects SET in_charge = [APPLICANT_ID] WHERE project_id = '[PROJECT_ID]';`

### Step 2: Critical Tests (Must Pass)

#### Test A: Edit In-Charge Project
1. [ ] Log in as applicant
2. [ ] Go to `/executor/projects`
3. [ ] Find project where applicant is in-charge (not owner)
4. [ ] Click "Edit"
5. [ ] **Expected:** ✅ Can edit (this is NEW behavior)

#### Test B: Dashboard Shows In-Charge Projects
1. [ ] Log in as applicant
2. [ ] Go to `/executor/dashboard`
3. [ ] **Expected:** ✅ Shows approved projects where applicant is in-charge

#### Test C: Create Report for In-Charge Project
1. [ ] Log in as applicant
2. [ ] Go to project where applicant is in-charge
3. [ ] Create monthly report
4. [ ] **Expected:** ✅ Can create report

### Step 3: Security Test
- [ ] Try to access a project where applicant has NO access
- [ ] **Expected:** ❌ Access denied (403)

---

## ✅ All Tests Pass Checklist

### Project Access
- [ ] Edit own project
- [ ] Edit in-charge project ⭐ **KEY TEST**
- [ ] View own project
- [ ] View in-charge project
- [ ] Submit own project
- [ ] Submit in-charge project ⭐ **KEY TEST**

### Dashboard
- [ ] Shows owned projects
- [ ] Shows in-charge projects ⭐ **KEY TEST**
- [ ] Budget summaries correct

### Reports
- [ ] Create report for owned project
- [ ] Create report for in-charge project ⭐ **KEY TEST**
- [ ] Edit report for in-charge project ⭐ **KEY TEST**
- [ ] Submit report for in-charge project
- [ ] View reports list (includes in-charge projects) ⭐ **KEY TEST**

### Aggregated Reports
- [ ] Generate quarterly report for in-charge project
- [ ] View aggregated reports list (includes in-charge projects)
- [ ] Export aggregated report for in-charge project

### Security
- [ ] Cannot access unauthorized project
- [ ] Cannot access unauthorized report

---

## 🐛 Common Issues

### Issue: "Still can't edit in-charge project"
**Check:**
```sql
-- Verify in_charge is set correctly
SELECT project_id, user_id, in_charge 
FROM projects 
WHERE project_id = '[PROJECT_ID]';
```

**Fix:** Clear cache if using Laravel cache:
```bash
php artisan cache:clear
php artisan config:clear
```

### Issue: "Dashboard not showing in-charge projects"
**Check:**
```sql
-- Verify project status
SELECT project_id, status 
FROM projects 
WHERE in_charge = [APPLICANT_ID] 
  AND status = 'approved_by_coordinator';
```

### Issue: "Reports not showing"
**Check:**
```sql
-- Verify reports exist for the project
SELECT * FROM DP_Reports 
WHERE project_id = '[PROJECT_ID]';
```

---

## 📝 Test Results

**Date:** _______________
**Tester:** _______________

### Critical Tests
- [ ] Test A: Edit In-Charge Project
- [ ] Test B: Dashboard Shows In-Charge Projects  
- [ ] Test C: Create Report for In-Charge Project
- [ ] Security Test: Cannot Access Unauthorized

### Overall Status
- [ ] All critical tests passed
- [ ] Ready for production

**Notes:**
_________________________________________________
_________________________________________________
_________________________________________________
