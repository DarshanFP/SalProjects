# Province Filter Selector Implementation

**Date:** 2026-01-13  
**Status:** ✅ Implementation Complete  
**Purpose:** Allow general users managing multiple provinces to filter data by selected provinces

---

## 📋 Problem

General users managing multiple provinces need the ability to:
- View data for **one specific province** (e.g., team for only one province)
- View data for **multiple selected provinces** (e.g., projects for 2 provinces)
- View data for **all provinces** (default behavior)
- Have the filter persist across all pages throughout the application

---

## ✅ Solution Implemented

### 1. Session-Based Province Filter

**Controller:** `app/Http/Controllers/ProvinceFilterController.php`

**Features:**
- Stores selected province IDs in session
- Validates that selected provinces are managed by the user
- Supports "all provinces" option
- Provides API endpoints for filter management

**Routes:**
```php
POST /province-filter/update  - Update province filter
GET  /province-filter/get      - Get current filter
POST /province-filter/clear    - Clear filter (show all)
```

### 2. Province Selector Component

**Component:** `resources/views/components/province-filter-selector.blade.php`

**Location:** Top navigation bar (header)

**Features:**
- Dropdown with checkboxes for each managed province
- "Select All" option
- Badge showing count of selected provinces
- Real-time filter info display
- Only visible for general users managing multiple provinces

**UI Elements:**
- Icon: Map pin icon
- Label: Shows "All Provinces" or "X Provinces"
- Badge: Shows count of selected provinces
- Dropdown: Checkbox list with apply/clear buttons

### 3. Updated Data Fetching Logic

**File:** `app/Http/Controllers/ProvincialController.php`

**Method:** `getAccessibleUserIds($provincial)`

**Updated Logic:**
```php
// Check if province filter is set in session
$filteredProvinceIds = session('province_filter_ids', []);
$filterAll = session('province_filter_all', true);

// If filter is set and not "all", use filtered provinces
if (!empty($filteredProvinceIds) && !$filterAll) {
    $provincesToUse = array_intersect($managedProvinces->toArray(), $filteredProvinceIds);
} else {
    // Use all managed provinces (default or "all" selected)
    $provincesToUse = $managedProvinces->toArray();
}
```

**Impact:**
- All methods in `ProvincialController` now respect the filter
- Dashboard, projects, reports, budgets all filtered
- Statistics and metrics calculated for selected provinces only

---

## 🔍 How It Works

### User Flow

1. **General User Logs In:**
   - If they manage multiple provinces, province selector appears in top bar
   - Default: "All Provinces" selected

2. **User Clicks Province Selector:**
   - Dropdown opens showing all managed provinces
   - Checkboxes for each province
   - "All Provinces" checkbox at top

3. **User Selects Provinces:**
   - Can select one, multiple, or all provinces
   - Can use "Select All" button
   - Real-time preview of selection

4. **User Clicks "Apply Filter":**
   - Selected province IDs saved to session
   - Page reloads with filtered data
   - Filter persists across all pages

5. **Data Display:**
   - Only data from selected provinces shown
   - Projects, reports, budgets filtered
   - Statistics calculated for selected provinces only

6. **User Can Clear Filter:**
   - Click "Clear" button
   - Returns to "All Provinces" view
   - All data from all provinces shown

### Technical Flow

```
User Selects Provinces
    ↓
JavaScript sends AJAX request
    ↓
ProvinceFilterController::updateFilter()
    ↓
Validate provinces are managed by user
    ↓
Store in session: province_filter_ids, province_filter_all
    ↓
Return success response
    ↓
JavaScript reloads page
    ↓
ProvincialController::getAccessibleUserIds()
    ↓
Reads session filter
    ↓
Filters managed provinces
    ↓
Returns filtered user IDs
    ↓
All queries use filtered user IDs
    ↓
Data displayed for selected provinces only
```

---

## 📊 Filter Behavior

### Default State (No Filter)
- Shows all managed provinces
- Badge shows total count
- Label: "All Provinces"

### Filtered State (Specific Provinces Selected)
- Shows only selected provinces
- Badge shows selected count
- Label: "X Provinces"
- Info text shows province names

### Examples

**Example 1: View Team for One Province**
1. User selects only "Divyodaya"
2. Clicks "Apply Filter"
3. Team page shows only users from Divyodaya

**Example 2: View Projects for Two Provinces**
1. User selects "Divyodaya" and "East Africa"
2. Clicks "Apply Filter"
3. Projects page shows projects from both provinces

**Example 3: View Reports for All Provinces**
1. User selects "All Provinces" (or clears filter)
2. Clicks "Apply Filter"
3. Reports page shows reports from all 3 provinces

---

## 🎨 UI Components

### Province Selector Button

**Location:** Top navigation bar, before profile dropdown

**Display:**
- Icon: Map pin (feather icon)
- Label: "All Provinces" or "X Provinces"
- Badge: Count of selected provinces

**Visibility:**
- Only shown for general users
- Only if user manages multiple provinces

### Dropdown Menu

**Contents:**
- Header: "Filter by Province" with "Select All" button
- "All Provinces" checkbox (top)
- List of managed provinces with checkboxes
- Footer: "Clear" and "Apply Filter" buttons
- Info text: Shows selected provinces

**Styling:**
- Max height: 300px with scroll
- Checkboxes with labels
- Primary button for "Apply Filter"
- Secondary button for "Clear"

---

## 🔧 Implementation Details

### Session Storage

**Keys:**
- `province_filter_ids`: Array of selected province IDs
- `province_filter_all`: Boolean (true if "all" selected)

**Default:**
- If not set: Shows all provinces
- If empty array: Shows all provinces
- If "all" in array: Shows all provinces

### Validation

**Security:**
- Only provinces managed by user can be selected
- Server-side validation in `updateFilter()`
- Filters out unauthorized province IDs

### Performance

**Optimization:**
- Filter stored in session (fast access)
- No database queries on every page load
- Filter applied at query level (efficient)

---

## 📝 Files Created/Modified

### New Files
1. `app/Http/Controllers/ProvinceFilterController.php`
   - Handles filter updates, retrieval, and clearing

2. `resources/views/components/province-filter-selector.blade.php`
   - Province selector UI component

### Modified Files
1. `app/Http/Controllers/ProvincialController.php`
   - Updated `getAccessibleUserIds()` to respect session filter

2. `resources/views/layoutAll/header.blade.php`
   - Added province selector component include

3. `routes/web.php`
   - Added province filter routes

---

## ✅ Features

- ✅ Province selector in top bar
- ✅ Multi-select checkboxes
- ✅ "Select All" option
- ✅ "Clear Filter" option
- ✅ Session-based persistence
- ✅ Filter applies to all provincial pages
- ✅ Dashboard respects filter
- ✅ Projects list respects filter
- ✅ Reports list respects filter
- ✅ Budgets respect filter
- ✅ Statistics respect filter
- ✅ Security validation
- ✅ Real-time UI updates
- ✅ Badge showing selected count
- ✅ Info text showing selected provinces

---

## 🧪 Testing Checklist

### Functional Testing
- [ ] Province selector appears for general users managing multiple provinces
- [ ] Selector does not appear for regular provincial users
- [ ] Selector does not appear for general users with one province
- [ ] Can select one province
- [ ] Can select multiple provinces
- [ ] Can select all provinces
- [ ] Filter persists across page navigation
- [ ] Dashboard shows filtered data
- [ ] Projects list shows filtered data
- [ ] Reports list shows filtered data
- [ ] Budgets show filtered data
- [ ] Statistics calculated for selected provinces only
- [ ] Clear filter returns to all provinces view

### Security Testing
- [ ] Cannot select provinces not managed by user
- [ ] Server validates province ownership
- [ ] Filter cleared on logout
- [ ] Session isolation between users

### UI Testing
- [ ] Dropdown opens/closes correctly
- [ ] Checkboxes work correctly
- [ ] Badge updates correctly
- [ ] Info text shows correct provinces
- [ ] Loading states work
- [ ] Error handling works
- [ ] Responsive design works

---

## 🚀 Usage Examples

### Example 1: View Team for One Province

1. Login as general user (User 12)
2. Click province selector in top bar
3. Uncheck "All Provinces"
4. Check only "Divyodaya"
5. Click "Apply Filter"
6. Navigate to Team page
7. See only users from Divyodaya

### Example 2: View Projects for Two Provinces

1. Click province selector
2. Select "Divyodaya" and "East Africa"
3. Click "Apply Filter"
4. Navigate to Projects page
5. See projects from both provinces

### Example 3: View Reports for All Provinces

1. Click province selector
2. Check "All Provinces" (or click "Clear")
3. Click "Apply Filter"
4. Navigate to Reports page
5. See reports from all 3 provinces

---

## ⚠️ Important Notes

### Filter Persistence

- Filter stored in session
- Persists across page navigation
- Cleared on logout
- User-specific (each user has own filter)

### Default Behavior

- If no filter set: Shows all provinces
- If filter cleared: Shows all provinces
- If "all" selected: Shows all provinces

### Performance

- Filter applied at query level (efficient)
- No additional database queries
- Session access is fast

---

## 🔮 Future Enhancements

### Potential Improvements

1. **Save Filter Preferences:**
   - Store in database
   - Persist across sessions
   - Remember last used filter

2. **Quick Filters:**
   - Preset filters (e.g., "My Top 3 Provinces")
   - Recent filters
   - Favorite provinces

3. **Filter by Province Name:**
   - Search/filter in dropdown
   - Group by region
   - Sort options

4. **Visual Indicators:**
   - Color coding by province
   - Province badges on data items
   - Province filter in data tables

---

**Last Updated:** 2026-01-13  
**Status:** ✅ Implementation Complete | Ready for Testing  
**Database:** `projectsReports` (Development)
