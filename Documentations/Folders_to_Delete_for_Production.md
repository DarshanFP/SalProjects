# Folders to Delete for Production - Attachment Files Cleanup

This document lists all folders and paths that should be deleted on the hosting server to remove test attachment files while maintaining the database structure.

---

## Important Notes

⚠️ **WARNING**: These operations will delete all physical files and folders. Make sure to:
- Backup your files before deleting (if needed)
- Verify that you want to delete all test attachments
- The database tables will remain intact (only physical files are deleted)

✅ **KEEP INTACT**: The following will NOT be deleted:
- Database tables and structure
- Core application files
- Configuration files
- Vendor files
- Public assets (CSS, JS, images used by the application)

---

## Base Path

All paths are relative to your Laravel project root. On the server, the full path will be:
```
/path/to/your/project/storage/app/public/
```

---

## Folders to Delete

### 1. Project Attachments

**Path**: `storage/app/public/project_attachments/`

**Description**: Contains all project-related attachments organized by project type and project ID.

**Structure**:
```
project_attachments/
├── CHILD_CARE_INSTITUTION/
│   ├── CCI-0007/
│   ├── CCI-0009/
│   └── CCI-0010/
├── Development_Projects/
│   ├── DP-0001/
│   ├── DP-0002/
│   └── ...
├── IAH/
│   └── IAH-0003/
├── IES/
│   ├── IOES-0001/
│   └── IOES-0002/
├── IIES/
│   ├── IIES-0001/
│   └── IIES-0002/
├── ILP/
│   ├── ILA-0001/
│   ├── ILA-0002/
│   └── ...
├── Institutional_Ongoing_Group_Educational_proposal/
│   ├── IOGEP-0001/
│   └── IOGEP-0002/
├── Livelihood_Development_Projects/
│   └── LDP-0003/
├── PROJECT_PROPOSAL_FOR_CRISIS_INTERVENTION_CENTER/
├── Residential_Skill_Training_Proposal_2/
└── Rural_Urban_Tribal/
```

**Delete**: Entire `project_attachments/` folder

---

### 2. Report Attachments and Data

**Path**: `storage/app/public/REPORTS/`

**Description**: Contains all report-related attachments, photos, and data organized by project ID and report ID.

**Structure**:
```
REPORTS/
├── CIC-0001/
│   └── CIC-0001-01/
│       ├── attachments/
│       └── photos/
├── DP-0001/
│   └── DP-0001-01/
│       ├── attachments/
│       └── photos/
├── IOGEP-0001/
└── Old/
```

**Delete**: Entire `REPORTS/` folder

---

### 3. Report Attachments (Alternative Location)

**Path**: `storage/app/public/report_attachments/`

**Description**: Alternative location for report attachments (may contain legacy files).

**Files**: Various PDF, DOC, DOCX files

**Delete**: Entire `report_attachments/` folder

---

### 4. Photos

**Path**: `storage/app/public/photos/`

**Description**: Contains photo files uploaded for reports.

**Files**: PNG, JPG, JPEG image files

**Delete**: Entire `photos/` folder

---

### 5. Report Images

**Path**: `storage/app/public/ReportImages/`

**Description**: Contains report images organized by report type (Monthly, Quarterly, Biannual, Annual).

**Structure**:
```
ReportImages/
├── Annual/
├── Biannual/
├── Monthly/
└── Quarterly/
```

**Delete**: Entire `ReportImages/` folder

---

### 6. General Attachments

**Path**: `storage/app/public/attachments/`

**Description**: Contains general attachment files (may include test files and various project attachments).

**Files**: Various PDF, DOC, DOCX, XLS, XLSX files

**Delete**: Entire `attachments/` folder

---

### 7. LDP Need Analysis Documents

**Path**: `storage/app/public/ldp/need_analysis/`

**Description**: Contains need analysis documents for Livelihood Development Projects.

**Delete**: Entire `ldp/` folder (or just `ldp/need_analysis/` if other subfolders exist)

---

## Complete List of Folders to Delete

For easy reference, here are all folders to delete:

```
storage/app/public/project_attachments/
storage/app/public/REPORTS/
storage/app/public/report_attachments/
storage/app/public/photos/
storage/app/public/ReportImages/
storage/app/public/attachments/
storage/app/public/ldp/
```

**Total: 7 main folders**

---

## Server Commands

### Option 1: Delete via SSH/Command Line

If you have SSH access to your server, you can use these commands:

```bash
# Navigate to your Laravel project root
cd /path/to/your/laravel/project

# Delete all attachment folders
rm -rf storage/app/public/project_attachments
rm -rf storage/app/public/REPORTS
rm -rf storage/app/public/report_attachments
rm -rf storage/app/public/photos
rm -rf storage/app/public/ReportImages
rm -rf storage/app/public/attachments
rm -rf storage/app/public/ldp
```

### Option 2: Delete via FTP/SFTP Client

1. Connect to your server via FTP/SFTP
2. Navigate to: `your-project/storage/app/public/`
3. Delete the following folders:
   - `project_attachments`
   - `REPORTS`
   - `report_attachments`
   - `photos`
   - `ReportImages`
   - `attachments`
   - `ldp`

### Option 3: Delete via cPanel File Manager

1. Log into cPanel
2. Open File Manager
3. Navigate to: `public_html/your-project/storage/app/public/` (or your project path)
4. Select and delete the folders listed above

---

## Verification Checklist

After deletion:

- [ ] Verify `storage/app/public/project_attachments/` is deleted or empty
- [ ] Verify `storage/app/public/REPORTS/` is deleted or empty
- [ ] Verify `storage/app/public/report_attachments/` is deleted or empty
- [ ] Verify `storage/app/public/photos/` is deleted or empty
- [ ] Verify `storage/app/public/ReportImages/` is deleted or empty
- [ ] Verify `storage/app/public/attachments/` is deleted or empty
- [ ] Verify `storage/app/public/ldp/` is deleted or empty
- [ ] Verify database tables are still intact
- [ ] Test creating a new project and uploading attachments (folders should be recreated automatically)
- [ ] Test creating a new report and uploading attachments (folders should be recreated automatically)

---

## Important Notes

1. **Automatic Recreation**: The application will automatically recreate these folders when new attachments are uploaded. You don't need to manually create them.

2. **Database Links**: The database tables (`project_attachments`, `report_attachments`, `DP_Photos`, etc.) will remain intact. Only the physical files are deleted.

3. **Storage Link**: Make sure the storage link is properly set up:
   ```bash
   php artisan storage:link
   ```

4. **Permissions**: After deletion, ensure the `storage/app/public/` directory has proper write permissions (usually 755 or 775) so the application can recreate folders when needed.

---

## Folder Structure After Cleanup

After deletion, your `storage/app/public/` directory should look like this:

```
storage/app/public/
├── .gitignore (if exists)
└── (empty or only system files)
```

The folders will be automatically recreated when users upload new attachments.

---

**Last Updated**: Generated from codebase analysis
**Application**: SalProjects Laravel Application

