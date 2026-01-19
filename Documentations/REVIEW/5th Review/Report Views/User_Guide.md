# User Guide: Report Views Enhancement
## Field Indexing & Activity Card UI

**Version:** 1.0  
**Date:** January 2025  
**Last Updated:** January 2025

---

## Overview

This user guide explains the new enhancements to the Monthly Report creation and editing interface:

1. **Field Indexing System**: Sequential index numbers for all dynamic fields
2. **Activity Card-Based UI**: Collapsible card interface for activities

These enhancements make it easier to track, organize, and manage report data across all sections.

---

## Field Indexing System

### What is Field Indexing?

Field indexing adds sequential numbers (1, 2, 3, ...) to all dynamically added fields or groups. These numbers help you:
- **Track items**: Easily identify and reference specific items
- **Maintain organization**: See the order of items at a glance
- **Understand relationships**: Understand which items belong together

### Where is Field Indexing Used?

Index numbers appear in the following sections:

1. **Outlook Section** - Index badges (1, 2, 3, ...) on outlook cards
2. **Statements of Account** - "No." column in the table (1, 2, 3, ...)
3. **Photos Section** - Index badges (1, 2, 3, ...) on photo groups
4. **Activities Section** - Index badges (1, 2, 3, ...) on activity cards
5. **Attachments Section** - Index badges (1, 2, 3, ...) on attachment groups
6. **LDP Annexure (Impact Section)** - Index badges (1, 2, 3, ...) on impact groups

### How Does It Work?

#### Automatic Updates

When you **add** a new item:
- The new item automatically gets the next sequential number
- All items remain correctly numbered

When you **remove** an item:
- All remaining items automatically renumber
- Numbers remain sequential without gaps

**Example:**
1. Start with 3 outlooks (numbered 1, 2, 3)
2. Remove outlook #2
3. Remaining outlooks automatically renumber to 1, 2

---

## Activity Card-Based UI

### What is the Activity Card UI?

Activities are now displayed as **collapsible cards** instead of always-open forms. This makes it easier to:
- **Navigate**: See all activities at a glance
- **Focus**: Work on one activity at a time
- **Track Progress**: See completion status instantly

### Card Features

Each activity card displays:

#### 1. Activity Header (Clickable)
- **Index Badge** (green) - Activity number (1, 2, 3, ...)
- **Activity Name** - The name of the activity
- **Scheduled Months Badge** (blue) - Shows scheduled months
- **Status Badge** - Shows completion state (see below)
- **Toggle Icon** (chevron) - Indicates expand/collapse state

#### 2. Status Badges

Activities show one of three status badges:

- **Empty** (yellow badge) - No fields filled
- **In Progress** (blue badge) - Some fields filled
- **Complete** (green badge) - All required fields filled

Status updates **automatically** as you fill in fields.

#### 3. Activity Form (Collapsed by Default)

The activity form contains:
- Activity description
- Reporting Month selector
- Summary of Activities
- Qualitative & Quantitative Data
- Intermediate Outcomes

### How to Use Activity Cards

#### Expanding a Card
1. Click on the activity card header
2. The form expands below the header
3. The toggle icon rotates to indicate expanded state

#### Collapsing a Card
1. Click on the activity card header again
2. The form collapses
3. The toggle icon returns to original state

#### Adding an Activity
1. Click "Add Other Activity" button
2. A new card appears with the next sequential number
3. Card is collapsed by default
4. Click header to expand and fill in details

#### Removing an Activity
1. Click the "Remove" button on the card header
2. Activity card is removed
3. Remaining activities automatically renumber

#### Tracking Progress
- Watch the status badge change as you fill fields:
  - Start: **Empty** (yellow)
  - Fill some fields: **In Progress** (blue)
  - Fill all fields: **Complete** (green)

---

## Step-by-Step Guides

### Creating a Report

#### Step 1: Fill Basic Information
1. Navigate to Create Monthly Report page
2. Fill in basic information (Project Type, Place, etc.)
3. Select Reporting Month & Year

#### Step 2: Add Outlook Items
1. Locate "Outlook" section
2. First outlook is pre-created (numbered 1)
3. Click "Add More Outlook" to add additional outlooks
4. Fill in Date and Action Plan for each outlook
5. Index numbers update automatically

#### Step 3: Complete Statements of Account
1. Locate "Statements of Account" section
2. Existing budget rows show index numbers (1, 2, 3, ...) in "No." column
3. Click "Add Row" or "Add Additional Expense Row" to add more rows
4. Fill in expense details
5. Index numbers update automatically
6. Totals calculate automatically

#### Step 4: Upload Photos
1. Locate "Photos" section
2. Click "Add More Photos" to add photo groups
3. Each group shows index badge (1, 2, 3, ...)
4. Upload up to 3 photos per group
5. Add description for each group
6. Index numbers update automatically

#### Step 5: Manage Activities (NEW!)
1. Locate "Monthly Summary" section under each objective
2. Existing activities appear as **collapsed cards**
3. Each card shows:
   - Index number (green badge)
   - Activity name
   - Scheduled months (blue badge)
   - Status (yellow/blue/green badge)
4. **Click card header** to expand and see/edit activity details
5. Status badge updates automatically as you fill fields
6. Click "Add Other Activity" to add new activities
7. New activities appear as collapsed cards
8. Remove activities by clicking "Remove" button

#### Step 6: Add Attachments
1. Locate "Attachments" section
2. Click "Add New Attachment" to add attachments
3. Each attachment shows index badge (1, 2, 3, ...)
4. Upload file, enter name and description
5. Index numbers update automatically

#### Step 7: Submit Report
1. Review all sections
2. Verify index numbers are correct
3. Check activity status badges
4. Click "Submit Report"
5. All data saves correctly with index numbers

---

### Editing a Report

#### Step 1: Open Report for Editing
1. Navigate to Edit Report page
2. Existing data loads correctly
3. All index numbers display correctly

#### Step 2: Review Existing Items
1. Check that index numbers are correct
2. Verify cards display correctly for activities
3. Verify status badges reflect current data

#### Step 3: Add New Items
1. Add new items using "Add" buttons
2. New items get correct index numbers
3. Index numbers update automatically

#### Step 4: Remove Items
1. Click "Remove" on items you want to delete
2. Remaining items automatically renumber
3. Index numbers remain sequential

#### Step 5: Edit Activities (NEW!)
1. Activity cards display existing activities
2. Status badges show current completion state
3. Click card header to expand/collapse
4. Edit activity details
5. Status badge updates automatically
6. Multiple cards can be open simultaneously

#### Step 6: Save Changes
1. Click "Update Report"
2. All changes save correctly
3. Index numbers persist correctly

---

## Tips & Best Practices

### General Tips
1. **Use Index Numbers**: Reference specific items by their index numbers
2. **Check Status Badges**: Use activity status badges to track progress
3. **Organize with Cards**: Use collapsed cards to see all activities at once
4. **Verify Before Submit**: Check that all index numbers are correct

### Activity Management Tips
1. **Start with Status**: Check status badges to see which activities need completion
2. **Focus on One**: Expand one card at a time to focus on a specific activity
3. **Track Progress**: Watch status badges change from Empty → In Progress → Complete
4. **Use Scheduled Months**: Check scheduled months badge to see when activities are planned

### Troubleshooting

**Issue: Index numbers are incorrect**
- **Solution**: Click the "Remove" button and re-add the item, or refresh the page

**Issue: Activity card won't expand**
- **Solution**: Click directly on the card header (not the remove button), or refresh the page

**Issue: Status badge not updating**
- **Solution**: Save the form and refresh, or click on the card header to refresh

**Issue: Form not submitting**
- **Solution**: Check browser console for errors, ensure all required fields are filled

---

## Frequently Asked Questions (FAQ)

### Q: Do I need to manually number items?
**A:** No, index numbers are automatic and update when items are added or removed.

### Q: Can I remove the first item?
**A:** The first item in most sections cannot be removed (remove button is hidden). You can edit it instead.

### Q: Do index numbers save to the database?
**A:** Index numbers are primarily for display. Form field names use array indices that save correctly.

### Q: Can I open multiple activity cards at once?
**A:** Yes, multiple cards can be expanded simultaneously. Cards operate independently.

### Q: What determines activity status?
**A:** Status is based on completion of:
- Reporting Month (select field)
- Summary of Activities (textarea)
- Qualitative & Quantitative Data (textarea)
- Intermediate Outcomes (textarea)

### Q: Do status badges save to database?
**A:** No, status badges are visual indicators only. They update dynamically based on form completion.

### Q: What happens to index numbers when I edit a report?
**A:** Index numbers are recalculated each time the page loads. They display correctly for existing data.

### Q: Can I change the order of items?
**A:** Currently, items maintain the order they were added. To change order, remove and re-add items in the desired order.

---

## Visual Guide

### Index Badge Colors

- **Primary (Blue)** - Outlook sections
- **Info (Light Blue)** - Photo groups, Scheduled months
- **Success (Green)** - Activity index numbers
- **Warning (Yellow)** - Activity status (Empty), Impact groups
- **Info (Blue)** - Activity status (In Progress)
- **Success (Green)** - Activity status (Complete)
- **Secondary (Gray)** - Attachment groups

### Card States

- **Collapsed** - Form hidden, chevron down icon
- **Expanded** - Form visible, chevron up icon, active border

---

## Keyboard Shortcuts

Currently, keyboard navigation is not implemented. Future enhancements may include:
- Arrow keys to navigate between cards
- Enter/Space to expand/collapse cards
- Tab to move between form fields

---

## Browser Support

### Supported Browsers
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

### Recommended
- Use latest browser version
- Enable JavaScript
- Clear cache if issues occur

---

## Support & Feedback

### Getting Help
- Check browser console (F12) for errors
- Review this user guide
- Contact support if issues persist

### Reporting Issues
When reporting issues, please provide:
1. Browser and version
2. Project type
3. Section where issue occurred
4. Steps to reproduce
5. Screenshot if possible

---

## Updates & Changes

### Version 1.0 (January 2025)
- Initial release
- Field indexing for all sections
- Activity card-based UI
- Status badges
- All 12 project types supported

### Future Updates
- Accordion behavior option (only one card open)
- Keyboard navigation
- Bulk operations
- Advanced filtering

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Current

---

**End of User Guide**
