# Sprint 1 Manual API Testing & Bug Fixes

## Testing Environment Setup

### Required Software
- XAMPP/WAMP with PHP 8.2+
- MySQL 8.x
- Composer
- Postman/Insomnia
- Git

### Setup Steps
1. Ensure MySQL is running
2. Configure `.env` file with database credentials
3. Run migrations: `php artisan migrate`
4. Seed database: `php artisan db:seed`
5. Start Laravel server: `php artisan serve`
6. Note down admin credentials from seeder

---

## Test Execution Log

### Date: _______________
### Tester: _______________
### Environment: Local / Staging / Production
### Laravel Version: _______________

---

## Authentication Module Tests

### Test ID: AUTH-001
**Endpoint:** POST /api/register
**Description:** User Registration

#### Test Steps
1. Open Postman
2. Set method to POST
3. URL: `http://localhost:8000/api/register`
4. Set Headers: `Content-Type: application/json`
5. Set Body (raw JSON):
```json
{
    "name": "Test User",
    "email": "testuser@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
}
```
6. Click Send

#### Expected Result
- **Status Code:** 201 Created
- **Response Body:**
```json
{
    "user": {
        "id": 1,
        "name": "Test User",
        "email": "testuser@example.com",
        "role": "student",
        "created_at": "2025-07-07T...",
        "updated_at": "2025-07-07T..."
    },
    "token": "1|laravel_sanctum_token_here"
}
```

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

#### Bugs Found
**Bug ID:** ________
**Description:** ________
**Severity:** 🔴 Critical / 🟡 Medium / 🟢 Low
**Steps to Reproduce:** ________
**Screenshot/Evidence:** ________

---

### Test ID: AUTH-002
**Endpoint:** POST /api/register
**Description:** Registration with missing required fields

#### Test Steps
1. Set Body:
```json
{
    "email": "test@example.com"
}
```
2. Click Send

#### Expected Result
- **Status Code:** 422 Unprocessable Entity
- **Response Body:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name field is required."],
        "password": ["The password field is required."]
    }
}
```

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

#### Bugs Found
**Bug ID:** ________
**Description:** ________
**Severity:** 🔴 Critical / 🟡 Medium / 🟢 Low

---

### Test ID: AUTH-003
**Endpoint:** POST /api/login
**Description:** User Login with Valid Credentials

#### Test Steps
1. Set method to POST
2. URL: `http://localhost:8000/api/login`
3. Set Body:
```json
{
    "email": "testuser@example.com",
    "password": "Password123!"
}
```
4. Click Send

#### Expected Result
- **Status Code:** 200 OK
- **Response Body:** User object + token

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: AUTH-004
**Endpoint:** POST /api/login
**Description:** Login with Invalid Credentials

#### Test Steps
1. Set Body:
```json
{
    "email": "testuser@example.com",
    "password": "WrongPassword"
}
```
2. Click Send

#### Expected Result
- **Status Code:** 401 Unauthorized
- **Response Body:**
```json
{
    "message": "Invalid credentials"
}
```

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: AUTH-005
**Endpoint:** POST /api/logout
**Description:** Logout (Revoke Token)

#### Test Steps
1. Copy token from login response
2. Set method to POST
3. URL: `http://localhost:8000/api/logout`
4. Set Headers: `Authorization: Bearer {token}`
5. Click Send
6. Try using the same token again to access protected route

#### Expected Result
- First request: 200 OK
- Second request with same token: 401 Unauthorized

#### Actual Result
```
First Request Status: _______
Second Request Status: _______
Response Body:
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: AUTH-006
**Endpoint:** PUT /api/profile
**Description:** Update Profile with Valid Data

#### Test Steps
1. Set method to PUT
2. URL: `http://localhost:8000/api/profile`
3. Set Headers: `Authorization: Bearer {valid_token}`
4. Set Body (form-data):
   - name: `Updated Name`
   - email: `updated@example.com`
5. Click Send

#### Expected Result
- **Status Code:** 200 OK
- **Response Body:** Updated user object

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: AUTH-007
**Endpoint:** PUT /api/profile
**Description:** Avatar Upload - Valid Image (JPG, 1MB)

#### Test Steps
1. Set method to PUT
2. URL: `http://localhost:8000/api/profile`
3. Set Headers: `Authorization: Bearer {valid_token}`
4. Set Body (form-data):
   - name: `Test User`
   - avatar: [Select valid JPG file < 2MB]
5. Click Send

#### Expected Result
- **Status Code:** 200 OK
- **Response Body:** User object with avatar path

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: AUTH-008
**Endpoint:** PUT /api/profile
**Description:** Avatar Upload - Invalid File Type (PDF)

#### Test Steps
1. Set Body (form-data):
   - avatar: [Select PDF file]
2. Click Send

#### Expected Result
- **Status Code:** 422 Unprocessable Entity
- **Response Body:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "avatar": ["The avatar must be a file of type: jpg, jpeg, png."]
    }
}
```

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: AUTH-009
**Endpoint:** PUT /api/profile
**Description:** Avatar Upload - File Too Large (>2MB)

#### Test Steps
1. Set Body (form-data):
   - avatar: [Select JPG file > 2MB]
2. Click Send

#### Expected Result
- **Status Code:** 422 Unprocessable Entity
- **Response Body:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "avatar": ["The avatar may not be greater than 2 MB."]
    }
}
```

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: AUTH-010
**Endpoint:** PUT /api/profile
**Description:** Update Profile Without Authentication

#### Test Steps
1. Set method to PUT
2. URL: `http://localhost:8000/api/profile`
3. No Authorization header
4. Set Body: `{"name": "New Name"}`
5. Click Send

#### Expected Result
- **Status Code:** 401 Unauthorized

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

## User Management Tests (Admin Only)

### Test ID: USER-001
**Endpoint:** GET /api/users
**Description:** List All Users (Admin)

#### Test Steps
1. Login as admin, get token
2. Set method to GET
3. URL: `http://localhost:8000/api/users`
4. Set Headers: `Authorization: Bearer {admin_token}`
5. Click Send

#### Expected Result
- **Status Code:** 200 OK
- **Response Body:** Paginated list of users

#### Actual Result
```
Status Code: _______
Response Body (first few lines):
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: USER-002
**Endpoint:** GET /api/users
**Description:** Filter Users by Role

#### Test Steps
1. URL: `http://localhost:8000/api/users?role=tutor`
2. Click Send

#### Expected Result
- **Status Code:** 200 OK
- **Response Body:** Only users with role=tutor

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: USER-003
**Endpoint:** GET /api/users
**Description:** Non-Admin Access (Should Fail)

#### Test Steps
1. Login as student, get token
2. Set Headers: `Authorization: Bearer {student_token}`
3. Click Send

#### Expected Result
- **Status Code:** 403 Forbidden

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: USER-004
**Endpoint:** POST /api/users
**Description:** Create New User (Admin)

#### Test Steps
1. Set method to POST
2. URL: `http://localhost:8000/api/users`
3. Set Headers: `Authorization: Bearer {admin_token}`
4. Set Body:
```json
{
    "name": "New Tutor",
    "email": "newtutor@example.com",
    "password": "Password123!",
    "role": "tutor"
}
```
5. Click Send

#### Expected Result
- **Status Code:** 201 Created
- **Response Body:** Created user object

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: USER-005
**Endpoint:** PUT /api/users/{id}
**Description:** Update Existing User

#### Test Steps
1. Set method to PUT
2. URL: `http://localhost:8000/api/users/1`
3. Set Headers: `Authorization: Bearer {admin_token}`
4. Set Body:
```json
{
    "name": "Updated Name",
    "email": "updated@example.com",
    "role": "tutor"
}
```
5. Click Send

#### Expected Result
- **Status Code:** 200 OK
- **Response Body:** Updated user object

#### Actual Result
```
Status Code: _______
Response Body:
_______________________________________________
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

### Test ID: USER-006
**Endpoint:** DELETE /api/users/{id}
**Description:** Soft Delete User

#### Test Steps
1. Set method to DELETE
2. URL: `http://localhost:8000/api/users/2`
3. Set Headers: `Authorization: Bearer {admin_token}`
4. Click Send
5. Verify by GET request: Should return 404

#### Expected Result
- First request: 200 OK
- GET request after deletion: 404 Not Found

#### Actual Result
```
Delete Status: _______
GET After Delete Status: _______
Response Body:
_______________________________________________
```

#### Test Status: ☐ PASS  ☐ FAIL

---

## Bug Report Template

```
=====================================
BUG REPORT
=====================================

Bug ID: _______________
Reported Date: _______________
Reported By: _______________
Environment: _______________

Title: _______________

Severity: 🔴 Critical / 🟡 Medium / 🟢 Low
Priority: 🔴 High / 🟡 Medium / 🟢 Low
Status: Open / In Progress / Fixed / Won't Fix

Description:
_______________________________________________
_______________________________________________

Steps to Reproduce:
1. _______________
2. _______________
3. _______________________

Actual Result:
_______________________________________________

Expected Result:
_______________________________________________

Screenshots/Evidence:
[Attach screenshots or curl commands]

Workaround (if any):
_______________________________________________

Assigned To: _______________
Fix Date: _______________
=====================================
```

---

## Bug Tracking Log

| ID | Severity | Description | Status | Assigned To | Fix Date |
|----|----------|-------------|--------|-------------|----------|
|    |          |             |        |             |          |
|    |          |             |        |             |          |
|    |          |             |        |             |          |
|    |          |             |        |             |          |
|    |          |             |        |             |          |

---

## Severity Definitions

### 🔴 Critical
- System crash or data loss
- Security vulnerability
- Complete feature failure
- Blocks all users

### 🟡 Medium
- Feature partially works
- Degraded performance
- Affects some users
- Has workaround

### 🟢 Low
- Minor UI/UX issues
- Cosmetic problems
- Rare edge cases
- Easy workaround

---

## Test Summary

### Tests Executed: ____
### Tests Passed: ____
### Tests Failed: ____
### Pass Rate: ____%

### Critical Bugs: ____
### Medium Bugs: ____
### Low Bugs: ____

### Ready for Sprint Review: ☐ YES / ☐ NO

---

## Bugs Found

### Bug #1: Role Hardcoded in Registration
**Severity:** High
**Status:** FIXED
**Reported Date:** 2026-07-07

**Description:** The `register` method in `AuthController` was hardcoding the role to 'student' regardless of the input provided. This prevented admins from creating users with different roles (admin, tutor, company).

**Steps to Reproduce:**
1. Send POST request to `/api/v1/register` with role: "admin"
2. Check the created user's role
3. User is created with role "student" instead of "admin"

**Expected Result:** User should be created with the role provided in the request
**Actual Result:** User was always created with role "student"

**Fix:** Modified AuthController line 24 from `'role' => 'student'` to `'role' => $validated['role'] ?? 'student'`

---

### Bug #2: Role Field Not in Fillable
**Severity:** Critical
**Status:** FIXED
**Reported Date:** 2026-07-07

**Description:** The `role` field was not added to the `$fillable` array in the User model, causing a "Column not found" SQL error when trying to save users with a role.

**Steps to Reproduce:**
1. Try to create a user with role parameter
2. SQL error occurs: "Unknown column 'role' in 'field list'"

**Expected Result:** User should be created successfully with role
**Actual Result:** SQLSTATE[42S22]: Column not found error

**Fix:** Added `'role'` to the `$fillable` array in `app/Models/User.php`

---

### Bug #3: Role Update Not Working
**Severity:** Medium
**Status:** FIXED
**Reported Date:** 2026-07-07

**Description:** The `updateProfile` method in `AuthController` was not saving the role field because it wasn't included in the update logic. The method only checked for name, email, and avatar.

**Steps to Reproduce:**
1. Send PUT request to `/api/v1/profile` with role: "admin"
2. Check user's role in the response
3. Role remains unchanged

**Expected Result:** User role should be updated to "admin"
**Actual Result:** Role remained "student"

**Fix:** Added role update logic to AuthController:
```php
if (array_key_exists('role', $validated)) {
    $user->role = $validated['role'];
}
```

---

### Bug #4: Missing Role Column in Database
**Severity:** Critical
**Status:** FIXED
**Reported Date:** 2026-07-07

**Description:** The users table was missing the `role` column entirely, which is required for role-based access control.

**Steps to Reproduce:**
1. Try to create any user with registration endpoint
2. Database error occurs

**Expected Result:** Users table should have role column
**Actual Result:** Column 'role' did not exist

**Fix:** Created and ran migration `2026_07_07_025734_add_role_to_users_table.php` to add role column

---

### Bug #5: Missing Avatar Column in Database
**Severity:** Medium
**Status:** FIXED
**Reported Date:** 2026-07-07

**Description:** The users table was missing the `avatar` column, causing file uploads to fail silently.

**Steps to Reproduce:**
1. Try to upload an avatar image via PUT /api/v1/profile
2. Check storage/app/public/avatars directory
3. Directory doesn't exist, file not saved

**Expected Result:** Avatar should be saved and path stored in database
**Actual Result:** File upload appeared to succeed but wasn't saved

**Fix:** Created and ran migration `2026_07_07_030809_add_avatar_to_users_table.php` to add avatar column


**Summary:**
- Total Bugs Found: 5
- Fixed: 5
- Open: 0

## Next Steps
1. Fix all critical bugs immediately
2. Fix medium bugs before deployment
3. Fix low bugs in next sprint or hotfix
4. Re-test all failed test cases after fixes
5. Document final test results
6. Get approval for production deployment

---

## Sign-off

### Tester:
- Name: ________________
- Signature: ________________
- Date: ________________

### Reviewer:
- Name: ________________
- Signature: ________________
- Date: ________________

---

## Notes
- Keep this document updated with test results
- Attach all evidence (screenshots, API responses)
- Re-test fixed bugs to ensure they're resolved
- Use this document for sprint review presentation