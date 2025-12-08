# Guide to Add New Provinces

## Overview
This guide provides step-by-step instructions for adding new provinces to the system. Currently, the system has hardcoded provinces: **Bangalore**, **Vijayawada**, **Visakhapatnam**, and **Generalate**. This document outlines all the files and locations that need to be updated when adding a new province.

## Important Notes

### Generalate Province Special Handling
- For provinces under **Generalate**, the province head should have the **"general"** user role (not "provincial" role)
- The president name for Generalate provinces should come from the user with role **"general"**
- For other provinces, the president name comes from the provincial (parent) user

### Society Name
- Society names are already user-selectable in project create/edit forms
- The society dropdown is located in project forms and can be extended as needed

---

## Files That Need to Be Updated

### 1. Controller Files

#### A. `app/Http/Controllers/CoordinatorController.php`

**Location 1: `createProvincial()` method (Line ~387-416)**
- **Action**: Add the new province to the `$centersMap` array
- **What to do**: Add a new key-value pair for your province with its associated centers

```php
$centersMap = [
    'VIJAYAWADA' => [...],
    'VISAKHAPATNAM' => [...],
    'BANGALORE' => [...],
    'YOUR_NEW_PROVINCE' => [  // Add this
        'Center 1',
        'Center 2',
        // ... add all centers for this province
    ],
];
```

**Location 2: `storeProvincial()` method (Line ~418-449)**
- **Action**: Update the validation rule for province
- **What to do**: Add your new province to the `in:` validation rule

```php
'province' => 'required|in:Bangalore,Vijayawada,Visakhapatnam,Generalate,YourNewProvince',
```

**Location 3: `editProvincial()` method (Line ~510-541)**
- **Action**: Add the new province to the `$centersMap` array (same as Location 1)
- **What to do**: Same as Location 1 - add your province and centers

**Location 4: `updateProvincial()` method (Line ~543-572)**
- **Action**: Update the validation rule for province
- **What to do**: Same as Location 2 - add your new province to the validation rule

```php
'province' => 'required|in:Bangalore,Vijayawada,Visakhapatnam,Generalate,YourNewProvince',
```

#### B. `app/Http/Controllers/ProvincialController.php`

**Location 1: `CreateExecutor()` method (Line ~343-378)**
- **Action**: Add the new province to the `$centersMap` array
- **What to do**: Add your province and its centers (same structure as CoordinatorController)

**Location 2: `editExecutor()` method (Line ~451-487)**
- **Action**: Add the new province to the `$centersMap` array
- **What to do**: Same as Location 1

---

### 2. View Files

#### A. `resources/views/coordinator/createProvincial.blade.php`

**Location: Province dropdown (Line ~39-46)**
- **Action**: Add a new `<option>` for your province
- **What to do**: Add this line in the select dropdown:

```blade
<option value="YourNewProvince">YourNewProvince</option>
```

**Complete example:**
```blade
<select name="province" class="form-control" required id="province">
    <option value="" disabled selected>Choose one</option>
    <option value="Bangalore">Bangalore</option>
    <option value="Vijayawada">Vijayawada</option>
    <option value="Visakhapatnam">Visakhapatnam</option>
    <option value="Generalate">Generalate</option>
    <option value="YourNewProvince">YourNewProvince</option>  <!-- Add this -->
</select>
```

#### B. `resources/views/coordinator/editProvincial.blade.php`

**Location: Province dropdown (Line ~31-38)**
- **Action**: Add a new `<option>` for your province with proper selected logic
- **What to do**: Add this line:

```blade
<option value="YourNewProvince" {{ $provincial->province == 'YourNewProvince' ? 'selected' : '' }}>YourNewProvince</option>
```

---

### 3. Project Form Files (For President Name Logic)

#### A. `resources/views/projects/partials/general_info.blade.php`

**Location: President/Chair Person field (Line ~50-51)**
- **Current Logic**: Uses `$user->parent->name` (provincial name)
- **Action**: Update logic to handle Generalate provinces differently
- **What to do**: Modify to check if province is Generalate and use general role user instead

**Suggested update:**
```blade
@php
    // For Generalate provinces, get the general role user
    if($user->province == 'Generalate') {
        $president = $users->where('role', 'general')->first();
        $presidentName = $president ? $president->name : ($user->parent ? $user->parent->name : 'N/A');
    } else {
        $presidentName = $user->parent ? $user->parent->name : 'N/A';
    }
@endphp
<label for="president_name" class="form-label">President / Chair Person</label>
<input type="text" name="president_name" class="form-control readonly-input" value="{{ $presidentName }}" readonly>
```

**Location: Coordinator India field (Line ~125)**
- **Current Logic**: Finds coordinator with province 'Generalate'
- **Action**: If adding new Generalate-like provinces, may need to update this logic
- **What to do**: Review if your new province needs special coordinator handling

#### B. `resources/views/projects/partials/Edit/general_info.blade.php`

**Location 1: President/Chair Person field (Line ~123-125)**
- **Action**: Similar update as above for create form
- **What to do**: Apply the same Generalate province logic

**Location 2: Coordinator India field (Line ~264-270)**
- **Current Logic**: Finds coordinator with province 'Generalate'
- **Action**: Review if updates needed for new provinces

---

### 4. Additional Considerations

#### A. Database
- **No migration needed**: The `province` field in the `users` table is a string/varchar field, so it can accept any province name
- **Note**: Ensure consistency in province name casing (recommend using title case: "YourNewProvince")

#### B. User Role Assignment
- **For Generalate provinces**: When creating a province head for a Generalate province, assign role **"general"** instead of **"provincial"**
- **For other provinces**: Use role **"provincial"** as normal
- **Important**: The validation in `storeProvincial()` allows role selection, but you need to manually ensure Generalate province heads get "general" role

#### C. Centers Mapping
- Each province has a predefined list of centers
- When adding a new province, you must define all its associated centers
- Centers are used in dropdowns when creating/editing executors and provincials

#### D. Society Names
- Society names are already in dropdown format in project forms
- Located in:
  - `resources/views/projects/partials/general_info.blade.php` (Line ~39-47)
  - `resources/views/projects/partials/Edit/general_info.blade.php` (Line ~96-120, ~443-450, ~554-563)
- To add new society names, add new `<option>` elements in these locations

---

## Step-by-Step Process to Add a New Province

### Example: Adding "Mumbai" Province

#### Step 1: Update CoordinatorController.php

1. **In `createProvincial()` method**, add to `$centersMap`:
```php
$centersMap = [
    // ... existing provinces ...
    'MUMBAI' => [
        'Mumbai Center 1',
        'Mumbai Center 2',
        'Mumbai Center 3',
        // Add all Mumbai centers
    ],
];
```

2. **In `storeProvincial()` method**, update validation:
```php
'province' => 'required|in:Bangalore,Vijayawada,Visakhapatnam,Generalate,Mumbai',
```

3. **In `editProvincial()` method**, add to `$centersMap` (same as step 1)

4. **In `updateProvincial()` method**, update validation (same as step 2)

#### Step 2: Update ProvincialController.php

1. **In `CreateExecutor()` method**, add to `$centersMap`:
```php
$centersMap = [
    // ... existing provinces ...
    'MUMBAI' => [
        'Mumbai Center 1',
        'Mumbai Center 2',
        // ... same centers as in CoordinatorController
    ],
];
```

2. **In `editExecutor()` method**, add to `$centersMap` (same as above)

#### Step 3: Update View Files

1. **In `createProvincial.blade.php`**, add option:
```blade
<option value="Mumbai">Mumbai</option>
```

2. **In `editProvincial.blade.php`**, add option:
```blade
<option value="Mumbai" {{ $provincial->province == 'Mumbai' ? 'selected' : '' }}>Mumbai</option>
```

#### Step 4: Update Project Forms (If Needed)

1. **If Mumbai should use "general" role for president** (like Generalate), update:
   - `resources/views/projects/partials/general_info.blade.php`
   - `resources/views/projects/partials/Edit/general_info.blade.php`

   Modify the president name logic to include Mumbai:
```blade
@php
    // For Generalate and Mumbai provinces, get the general role user
    if(in_array($user->province, ['Generalate', 'Mumbai'])) {
        $president = $users->where('role', 'general')->first();
        $presidentName = $president ? $president->name : ($user->parent ? $user->parent->name : 'N/A');
    } else {
        $presidentName = $user->parent ? $user->parent->name : 'N/A';
    }
@endphp
```

#### Step 5: Test

1. Create a new provincial user with the new province
2. Verify centers dropdown populates correctly
3. Create an executor under the new provincial
4. Create a project and verify president name displays correctly
5. Test edit functionality

---

## Special Case: Adding Generalate-Type Provinces

If you're adding a province that should behave like Generalate (using "general" role for province head):

### Additional Steps:

1. **When creating the province head user:**
   - Select role: **"general"** (not "provincial")
   - Select province: Your new province name
   - This user will serve as the president for this province

2. **Update project forms** to include your new province in the Generalate logic:
   - In both `general_info.blade.php` and `Edit/general_info.blade.php`
   - Update the condition to include your new province:
   ```php
   if(in_array($user->province, ['Generalate', 'YourNewProvince'])) {
       // Use general role user
   }
   ```

3. **Update Coordinator India logic** (if applicable):
   - In `general_info.blade.php` line ~125
   - In `Edit/general_info.blade.php` line ~269
   - Add your province if it needs special coordinator handling

---

## Checklist for Adding a New Province

- [ ] Update `CoordinatorController::createProvincial()` - add to `$centersMap`
- [ ] Update `CoordinatorController::storeProvincial()` - add to validation rule
- [ ] Update `CoordinatorController::editProvincial()` - add to `$centersMap`
- [ ] Update `CoordinatorController::updateProvincial()` - add to validation rule
- [ ] Update `ProvincialController::CreateExecutor()` - add to `$centersMap`
- [ ] Update `ProvincialController::editExecutor()` - add to `$centersMap`
- [ ] Update `createProvincial.blade.php` - add option to dropdown
- [ ] Update `editProvincial.blade.php` - add option to dropdown
- [ ] Update project forms if province needs special handling (Generalate-type)
- [ ] Test creating provincial user with new province
- [ ] Test creating executor under new provincial
- [ ] Test project creation and verify president name
- [ ] Test all edit functionalities

---

## Notes on Society Names

Society names are currently hardcoded in project forms but are user-selectable. To add new society names:

1. **Locations to update:**
   - `resources/views/projects/partials/general_info.blade.php` (Line ~39-47)
   - `resources/views/projects/partials/Edit/general_info.blade.php` (Multiple locations: ~96-120, ~443-450, ~554-563)

2. **Current society names:**
   - ST. ANN'S EDUCATIONAL SOCIETY
   - SARVAJANA SNEHA CHARITABLE TRUST
   - WILHELM MEYERS DEVELOPMENTAL SOCIETY
   - ST. ANNS'S SOCIETY, VISAKHAPATNAM
   - ST.ANN'S SOCIETY, SOUTHERN REGION

3. **To add a new society:**
   - Simply add a new `<option>` element in all the locations mentioned above
   - Example:
   ```blade
   <option value="NEW SOCIETY NAME" {{ $project->society_name == "NEW SOCIETY NAME" ? 'selected' : '' }}>NEW SOCIETY NAME</option>
   ```

---

## Troubleshooting

### Issue: Centers not showing in dropdown
- **Solution**: Ensure province name in `$centersMap` matches exactly (case-sensitive, use UPPERCASE in array key)
- **Check**: JavaScript in views uses `selectedProvince.toUpperCase()` to match

### Issue: Validation fails when saving
- **Solution**: Ensure province name is added to ALL validation rules in both `storeProvincial()` and `updateProvincial()`
- **Check**: Province name casing must match exactly in validation and dropdown

### Issue: President name not showing correctly for Generalate-type province
- **Solution**: Ensure the province is included in the condition that checks for Generalate
- **Check**: Verify a user with "general" role exists in the system

### Issue: Centers not populating in ProvincialController
- **Solution**: Ensure `$centersMap` in ProvincialController matches the one in CoordinatorController
- **Check**: Province name must be UPPERCASE in the array key

---

## Summary

Adding a new province requires updates in:
1. **2 Controller files** (CoordinatorController and ProvincialController) - 4 locations each
2. **2 View files** (createProvincial and editProvincial) - 1 location each
3. **2 Project form files** (if special handling needed) - multiple locations

**Total: Minimum 10 file locations to update** (more if special Generalate-type handling is needed)

Always test thoroughly after making changes, especially:
- Creating provincial users
- Creating executor users
- Creating projects
- Editing all user types
- Verifying president names display correctly

