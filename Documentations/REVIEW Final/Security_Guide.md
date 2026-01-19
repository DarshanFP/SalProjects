# Security Guide

**Date:** January 2025  
**Status:** ✅ **GUIDE COMPLETE**  
**Purpose:** Comprehensive security guide for the Laravel application

---

## Executive Summary

This guide documents all security measures implemented in the application, providing developers with a reference for security best practices and current implementations.

---

## Security Measures Overview

### 1. Authentication

**Implementation:**
- ✅ Session-based authentication
- ✅ Password hashing using `Hash::make()` (bcrypt)
- ✅ Session regeneration on login
- ✅ Proper logout handling with session invalidation
- ✅ Authentication middleware: `app/Http/Middleware/Authenticate.php`

**Routes Protection:**
- ✅ All protected routes use `middleware('auth')`
- ✅ Role-based access control via `middleware('role:...')`

**Best Practices:**
- ✅ Passwords never logged
- ✅ Session tokens regenerated on login
- ✅ Proper session invalidation on logout

---

### 2. Authorization

**Implementation:**
- ✅ Role-based access control (RBAC)
- ✅ `HandlesAuthorization` trait provides authorization helpers
- ✅ `ProjectPermissionHelper` for project-specific permissions
- ✅ Route middleware for role checking

**Roles:**
- `admin` - Full system access
- `general` - Coordinator + Provincial access
- `coordinator` - Project/report approval, user management
- `provincial` - Executor management, project oversight
- `executor` - Project creation, report submission
- `applicant` - Same access as executor

**Permission Checks:**
- ✅ Project ownership/in-charge checks
- ✅ Status-based permission checks
- ✅ Role-based route protection

---

### 3. CSRF Protection

**Implementation:**
- ✅ CSRF middleware enabled: `VerifyCsrfToken`
- ✅ CSRF token verification active for all web routes
- ✅ No routes excluded from CSRF protection
- ✅ Laravel's built-in CSRF protection working

**Configuration:**
- File: `app/Http/Middleware/VerifyCsrfToken.php`
- `$except` array is empty (all routes protected)

---

### 4. Input Validation

**Implementation:**
- ✅ FormRequest classes for validation
- ✅ Validation rules in place
- ✅ File upload validation comprehensive
- ✅ Input sanitization where needed

**FormRequest Classes:**
- `StoreProjectRequest`
- `UpdateProjectRequest`
- `SubmitProjectRequest`
- `ApproveProjectRequest`
- And more...

---

### 5. Sensitive Data Logging

**Implementation:**
- ✅ `LogHelper` class with `logSafeRequest()` method
- ✅ Sensitive fields excluded from logs
- ✅ Field whitelisting support
- ✅ Value truncation for long fields

**Sensitive Fields Excluded:**
- password, password_confirmation
- current_password, new_password
- token, api_token
- secret, private_key
- credit_card, cvv
- ssn, social_security_number
- bank_account, routing_number

**Usage:**
```php
// Safe logging
LogHelper::logSafeRequest('Message', $request, LogHelper::getProjectAllowedFields());

// Or log all non-sensitive fields
LogHelper::logSafeRequest('Message', $request);
```

---

### 6. File Upload Security

**Validation:**
- ✅ File type validation (extension + MIME type)
- ✅ File size limits (configurable)
- ✅ Filename sanitization
- ✅ Path traversal prevention
- ✅ Invalid file detection

**Configuration:**
- File: `config/attachments.php`
- Allowed file types per category
- Configurable size limits
- MIME type validation

**Security Features:**
- ✅ Filename sanitization prevents path traversal
- ✅ MIME type validation prevents file type spoofing
- ✅ File size limits prevent DoS attacks
- ✅ Storage path validation
- ✅ Transaction rollback on errors

---

### 7. Password Security

**Implementation:**
- ✅ Passwords hashed using `Hash::make()` (bcrypt)
- ✅ Password confirmation required
- ✅ Old password verification for changes
- ✅ Password reset functionality secure

**Best Practices:**
- ✅ Passwords never logged
- ✅ Passwords never returned in responses
- ✅ Password reset tokens properly handled

---

### 8. Session Security

**Implementation:**
- ✅ Session regeneration on login
- ✅ Session invalidation on logout
- ✅ CSRF token regeneration
- ✅ Cookie encryption

**Configuration:**
- Session driver: database/file (configurable)
- Session lifetime: configurable
- Secure cookies: enabled in production

---

### 9. SQL Injection Prevention

**Implementation:**
- ✅ Eloquent ORM used (parameterized queries)
- ✅ Query builder used (parameterized queries)
- ✅ Raw queries use parameter binding

**Best Practices:**
- ✅ Never use raw SQL with user input
- ✅ Always use Eloquent or Query Builder
- ✅ Use parameter binding for raw queries

---

### 10. XSS Protection

**Implementation:**
- ✅ Blade templating engine (auto-escaping)
- ✅ `{!! !!}` only used when safe
- ✅ `{{ }}` used for user input (auto-escaped)

**Best Practices:**
- ✅ Always use `{{ }}` for user input
- ✅ Use `{!! !!}` only for trusted HTML
- ✅ Sanitize user input before display

---

## Security Checklist

### For New Code

- [ ] Use `LogHelper::logSafeRequest()` for logging
- [ ] Validate all user input
- [ ] Use FormRequest classes for validation
- [ ] Check permissions before operations
- [ ] Use parameterized queries (Eloquent/Query Builder)
- [ ] Escape user input in views (`{{ }}`)
- [ ] Hash passwords with `Hash::make()`
- [ ] Regenerate session on login
- [ ] Use CSRF tokens in forms
- [ ] Validate file uploads (type, size, sanitize filename)

### For Existing Code

- [ ] Review logging statements
- [ ] Verify file upload validation
- [ ] Check permission checks
- [ ] Review input validation
- [ ] Verify password handling

---

## Security Best Practices

### Logging
```php
// ✅ Good: Use LogHelper
LogHelper::logSafeRequest('Message', $request, LogHelper::getProjectAllowedFields());

// ✅ Good: Log specific safe fields
Log::info('Message', ['project_id' => $project_id]);

// ❌ Bad: Log entire request
Log::info('Message', $request->all());
```

### File Uploads
```php
// ✅ Good: Validate file type, size, sanitize filename
$validated = $request->validate([
    'file' => 'required|file|max:7168',
]);
$filename = $this->sanitizeFilename($filename, $extension);

// ❌ Bad: No validation, use original filename
$file->storeAs($path, $file->getClientOriginalName());
```

### Authentication
```php
// ✅ Good: Hash password, regenerate session
$user->password = Hash::make($request->password);
$request->session()->regenerate();

// ❌ Bad: Store plain password, no session regeneration
$user->password = $request->password;
```

### Authorization
```php
// ✅ Good: Check permissions
if (!ProjectPermissionHelper::canEdit($project, $user)) {
    abort(403);
}

// ❌ Bad: No permission check
// Allow any user to edit
```

---

## Security Configuration

### Environment Variables
- `APP_KEY` - Application encryption key
- `APP_ENV` - Environment (local, production)
- `APP_DEBUG` - Debug mode (false in production)
- `DB_PASSWORD` - Database password
- `SESSION_DRIVER` - Session storage driver
- `SESSION_LIFETIME` - Session lifetime

### Middleware Stack
1. TrustProxies
2. HandleCors
3. PreventRequestsDuringMaintenance
4. ValidatePostSize
5. TrimStrings
6. ConvertEmptyStringsToNull
7. EncryptCookies
8. AddQueuedCookiesToResponse
9. StartSession
10. ShareErrorsFromSession
11. **VerifyCsrfToken** ← CSRF Protection
12. SubstituteBindings
13. ShareProfileData

---

## Security Audit Results

### ✅ Sensitive Data Logging: **EXCELLENT**
- No unsafe logging found
- LogHelper properly implemented
- Sensitive fields excluded

### ✅ File Upload Validation: **EXCELLENT**
- Comprehensive validation
- MIME type checking
- Size limits enforced
- Filename sanitization

### ✅ CSRF Protection: **GOOD**
- Enabled for all routes
- No exclusions

### ✅ Authentication: **GOOD**
- Properly implemented
- Session security in place

### ✅ Authorization: **GOOD**
- Role-based access control
- Permission checks in place

---

## Incident Response

### If Security Issue Found

1. **Immediate Actions:**
   - Assess severity
   - Contain the issue
   - Document the issue

2. **Fix Process:**
   - Create fix
   - Test thoroughly
   - Deploy fix
   - Update documentation

3. **Prevention:**
   - Review similar code
   - Update security guide
   - Add tests if applicable

---

## Security Resources

### Laravel Security Documentation
- [Laravel Security](https://laravel.com/docs/security)
- [Authentication](https://laravel.com/docs/authentication)
- [Authorization](https://laravel.com/docs/authorization)
- [CSRF Protection](https://laravel.com/docs/csrf)

### Internal Documentation
- `Security_Audit_Report.md` - Security audit results
- `PHPDoc_Standards.md` - Code documentation standards
- `Code_Style_Standards.md` - Code style guidelines

---

**Last Updated:** January 2025  
**Status:** ✅ **SECURITY GUIDE COMPLETE**
