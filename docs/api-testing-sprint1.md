# Sprint 1 API Testing Guide

## Overview
This document provides manual testing procedures for all Sprint 1 backend endpoints.

## Prerequisites
- Laravel 12 project running locally
- MySQL database configured and migrated
- Postman/Insomnia installed
- Sanctum tokens available for authenticated routes

---

## Authentication Endpoints

### 1. POST /api/register

**Purpose:** Create a new user account

**Test Case 1: Valid Registration**
- Method: POST
- URL: `/api/register`
- Body (JSON):
```json
{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```
- Expected Response: 201 Created
- Expected Body: User data + access_token

**Test Case 2: Missing Required Fields**
- Body: `{}`
- Expected Response: 422 UnprocessableEntity
- Expected Errors: name, email, password required

**Test Case 3: Invalid Email**
- Body: `{"name": "Test", "email": "invalid", "password": "pass123", "password_confirmation": "pass123"}`
- Expected Response: 422
- Expected Error: email must be valid email

**Test Case 4: Password Mismatch**
- Body: `{"name": "Test", "email": "test@example.com", "password": "pass123", "password_confirmation": "different"}`
- Expected Response: 422
- Expected Error: password confirmation does not match

**Test Case 5: Duplicate Email**
- Body: Use already registered email
- Expected Response: 422
- Expected Error: email already taken

---

### 2. POST /api/login

**Purpose:** Authenticate user and receive token

**Test Case 1: Valid Login**
- Method: POST
- URL: `/api/login`
- Body (JSON):
```json
{
    "email": "admin@example.com",
    "password": "password"
}
```
- Expected Response: 200 OK
- Expected Body: user data + token

**Test Case 2: Missing Email**
- Body: `{"password": "password"}`
- Expected Response: 422
- Expected Error: email is required

**Test Case 3: Invalid Credentials**
- Body: `{"email": "wrong@example.com", "password": "wrongpass"}`
- Expected Response: 401 Unauthorized
- Expected Message: Invalid credentials

**Test Case 4: Missing Password**
- Body: `{"email": "test@example.com"}`
- Expected Response: 422
- Expected Error: password is required

---

### 3. POST /api/logout

**Purpose:** Revoke current token

**Test Case 1: Valid Logout**
- Method: POST
- URL: `/api/logout`
- Headers: `Authorization: Bearer {valid_token}`
- Expected Response: 200 OK
- Expected Message: Successfully logged out

**Test Case 2: Without Token**
- Headers: None
- Expected Response: 401 Unauthorized

**Test Case 3: Invalid Token**
- Headers: `Authorization: Bearer invalid_token`
- Expected Response: 401 Unauthorized

---

### 4. POST /api/forgot-password

**Purpose:** Stub endpoint for password reset

**Test Case 1: Valid Request**
- Method: POST
- URL: `/api/forgot-password`
- Body (JSON):
```json
{
    "email": "user@example.com"
}
```
- Expected Response: 200 OK
- Expected Message: Success response

**Test Case 2: Invalid Email Format**
- Body: `{"email": "invalid"}`
- Expected Response: 422

---

### 5. PUT /api/profile

**Purpose:** Update authenticated user profile

**Test Case 1: Valid Update**
- Method: PUT
- URL: `/api/profile`
- Headers: `Authorization: Bearer {valid_token}`
- Body (form-data):
```
name: New Name
email: newemail@example.com
```
- Expected Response: 200 OK
- Expected Body: Updated user data

**Test Case 2: Avatar Upload (Valid)**
- Body (form-data):
```
name: Test User
email: test@example.com
avatar: [jpg/png file, max 2MB]
```
- Expected Response: 200 OK
- Expected Body: Updated user with avatar path

**Test Case 3: Invalid File Type**
- Body (form-data):
```
avatar: [pdf/file.txt]
```
- Expected Response: 422
- Expected Error: avatar must be jpg, jpeg, png

**Test Case 4: File Too Large**
- Body (form-data):
```
avatar: [file > 2MB]
```
- Expected Response: 422
- Expected Error: avatar may not be greater than 2 MB

**Test Case 5: Unauthenticated**
- Headers: None
- Expected Response: 401 Unauthorized

---

## User Management Endpoints (Admin)

### 6. GET /api/users

**Purpose:** List all users with pagination

**Test Case 1: Admin Access**
- Method: GET
- URL: `/api/users?page=1`
- Headers: `Authorization: Bearer {admin_token}`
- Expected Response: 200 OK
- Expected Body: Paginated user list

**Test Case 2: Filter by Role**
- URL: `/api/users?role=tutor`
- Expected Response: 200 OK
- Expected Body: Users filtered by role

**Test Case 3: Non-Admin Access**
- Headers: `Authorization: Bearer {student_token}`
- Expected Response: 403 Forbidden

---

### 7. POST /api/users

**Purpose:** Create new user (admin only)

**Test Case 1: Valid User Creation**
- Method: POST
- URL: `/api/users`
- Headers: `Authorization: Bearer {admin_token}`
- Body (JSON):
```json
{
    "name": "New Tutor",
    "email": "tutor@example.com",
    "password": "password123",
    "role": "tutor"
}
```
- Expected Response: 201 Created
- Expected Body: Created user data

**Test Case 2: Missing Fields**
- Body: `{"name": "Test"}`
- Expected Response: 422

**Test Case 3: Invalid Role**
- Body: `{"name": "Test", "email": "test@example.com", "password": "pass123", "role": "invalid"}`
- Expected Response: 422

---

### 8. PUT /api/users/{id}

**Purpose:** Update existing user

**Test Case 1: Valid Update**
- Method: PUT
- URL: `/api/users/1`
- Headers: `Authorization: Bearer {admin_token}`
- Body (JSON):
```json
{
    "name": "Updated Name",
    "email": "updated@example.com",
    "role": "tutor"
}
```
- Expected Response: 200 OK
- Expected Body: Updated user data

**Test Case 2: Non-existent User**
- URL: `/api/users/9999`
- Expected Response: 404 Not Found

---

### 9. DELETE /api/users/{id}

**Purpose:** Soft delete user

**Test Case 1: Valid Delete**
- Method: DELETE
- URL: `/api/users/2`
- Headers: `Authorization: Bearer {admin_token}`
- Expected Response: 200 OK
- Expected Body: Deleted user data

**Test Case 2: Verify Soft Delete**
- After deletion, GET `/api/users/2`
- Expected Response: 404 Not Found

**Test Case 3: Delete Non-existent User**
- URL: `/api/users/9999`
- Expected Response: 404 Not Found

---

## Role-Based Access Control Tests

### Admin Role Tests
- Token: admin@example.com
- Expected: Full access to all endpoints

### Tutor Role Tests
- Token: tutor@example.com
- Expected: Can access student/worklog endpoints
- Expected: 403 on user management

### Student Role Tests
- Token: student@example.com
- Expected: Can access own profile
- Expected: 403 on admin endpoints

### Company Role Tests
- Token: company@example.com
- Expected: Can view assigned students
- Expected: 403 on user management

---

## Bug Tracking

### Known Issues
| ID | Description | Status | Priority |
|----|-------------|--------|----------|
| - | No issues found yet | Open | - |

### Testing Checklist
- [ ] All registration test cases pass
- [ ] All login test cases pass
- [ ] All logout test cases pass
- [ ] All forgot-password test cases pass
- [ ] All profile update test cases pass
- [ ] Avatar upload validation works correctly
- [ ] User CRUD operations work for admin
- [ ] Role-based access control enforced
- [ ] All error responses return appropriate status codes
- [ ] All validation errors return 422 with clear messages
- [ ] Unauthorized requests return 401
- [ ] Forbidden requests return 403

---

## Postman Collection

Import the `postman/sprint1-collection.json` file into Postman to run all tests automatically.

## Notes
- Ensure database is seeded with test users before testing
- Use environment variables in Postman for base URL and tokens
- Test with both valid and invalid inputs
- Verify response formats match API documentation