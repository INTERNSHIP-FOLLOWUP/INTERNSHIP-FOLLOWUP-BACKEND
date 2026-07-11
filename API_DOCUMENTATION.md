# Tutor API Endpoints Documentation

## Overview
These endpoints are designed for admin monitoring and form dropdowns in the Student Internship Follow-up System.

---

## 1. GET /api/tutors

Returns a list of all users with role=tutor, excluding soft-deleted users.

### Purpose
- Used in dropdown selects for student assignment and internship assignment forms
- Provides a lightweight list with only essential fields

### Request
```
GET /api/tutors
Accept: application/json
```

### Response
**Status Code:** 200 OK

```json
[
    {
        "id": 5,
        "name": "Tutor User Updated",
        "email": "staff2@pnc.org"
    }
]
```

### Response Fields
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Unique identifier of the tutor |
| name | string | Full name of the tutor |
| email | string | Email address of the tutor |

### Notes
- Password is excluded by model's `$hidden` array
- Only includes users with `role = 'tutor'`
- Excludes soft-deleted users (`deleted_at IS NULL`)
- Non-paginated (returns all tutors)
- No authentication required

### Example Usage
```bash
curl -X GET http://127.0.0.1:8000/api/tutors -H "Accept: application/json"
```

---

## 2. GET /api/tutors/{id}/students

Returns a list of students currently assigned to a specific tutor.

### Purpose
- Admin monitoring of tutor workload
- View all students assigned to a particular tutor

### Request
```
GET /api/tutors/{id}/students
Accept: application/json
```

### Path Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | The tutor's user ID |

### Response
**Status Code:** 200 OK

**Success - With Students:**
```json
[
    {
        "id": 12,
        "student_code": "STU-2025-001",
        "name": "John Doe",
        "email": "john.doe@example.com",
        "status": "In Progress"
    },
    {
        "id": 15,
        "student_code": "STU-2025-004",
        "name": "Jane Smith",
        "email": "jane.smith@example.com",
        "status": "Assigned"
    }
]
```

**Success - No Students Assigned:**
```json
[]
```

### Response Fields
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Unique identifier of the student |
| student_code | string | Unique student code |
| name | string | Full name of the student |
| email | string | Email address of the student |
| status | string | Current internship status |

### Notes
- Excludes soft-deleted students (`deleted_at IS NULL`)
- Returns empty array if no students are assigned to the tutor
- Non-paginated (returns all assigned students)
- No authentication required

### Example Usage
```bash
# Get students assigned to tutor with ID 5
curl -X GET http://127.0.0.1:8000/api/tutors/5/students -H "Accept: application/json"
```

---

## Implementation Details

### Files Modified/Created

1. **app/Models/Tutor.php**
   - Updated `scopeTutors()` method to filter by `role` column and exclude soft-deleted users

2. **app/Http/Controllers/Api/TutorController.php**
   - Implemented `index()` method returning tutors with id, name, email only

3. **app/Http/Controllers/Api/UserController.php**
   - Implemented `students($id)` method returning students assigned to a tutor

4. **routes/api.php**
   - Registered `GET /api/tutors` → `TutorController@index`
   - Registered `GET /api/tutors/{id}/students` → `UserController@students`

5. **database/migrations/2026_07_09_014000_create_batches_table.php**
   - Created batches table migration (required for foreign key constraint)

### Database Schema

**users table (relevant fields):**
- `id` - Primary key
- `name` - User's full name
- `email` - Email address (unique)
- `password` - Hashed password
- `role` - String: 'admin', 'tutor', 'student', 'company representative'
- `deleted_at` - Soft delete timestamp (nullable)
- `avatar` - Profile photo path (nullable)

**students table (relevant fields):**
- `id` - Primary key
- `student_code` - Unique student identifier
- `tutor_id` - Foreign key to users table
- `name` - Student's full name
- `email` - Email address
- `status` - Internship status (nullable)
- `deleted_at` - Soft delete timestamp (nullable)

---

## Testing

### Test the tutors endpoint:
```bash
curl -X GET http://127.0.0.1:8000/api/tutors -H "Accept: application/json"
```

Expected Response:
```json
[{"id":5,"name":"Tutor User Updated","email":"staff2@pnc.org"}]
```

### Test the students endpoint:
```bash
curl -X GET http://127.0.0.1:8000/api/tutors/5/students -H "Accept: application/json"
```

Expected Response (when no students assigned):
```json
[]
```

---

## Acceptance Criteria

- [x] GET /api/tutors returns all users with role=tutor
- [x] Response includes only id, name, email (no password)
- [x] Soft-deleted users are excluded
- [x] Non-paginated response
- [x] GET /api/tutors/{id}/students returns students assigned to the tutor
- [x] Returns empty array if no students assigned
- [x] Soft-deleted students are excluded

---

## Notes for Frontend Integration

1. **Dropdown Selects:** Use `/api/tutors` for populating tutor selection dropdowns in forms
2. **Admin Monitoring:** Use `/api/tutors/{id}/students` to display tutor workload
3. **Error Handling:** Both endpoints return empty arrays when no data exists, not error responses
4. **CORS:** The application is configured to accept requests from any origin (HandleCors middleware)

---

## Last Updated
2026-07-11