# Backend Tutor Worklog API - TODO

## 1) Routes
- [ ] Update `routes/api.php` tutor worklog routes to match:
  - GET  /api/tutor/worklogs
  - GET  /api/tutor/worklogs/{id}
  - POST /api/tutor/worklogs/{id}
- [ ] Ensure Sanctum auth + tutor role middleware

## 2) Database
- [ ] Create migration adding to `worklogs`:
  - `reviewer_id` (nullable FK to users)
  - `reviewed_at` (nullable timestamp)
- [ ] Update `worklogs` model fillable/casts if needed

## 3) Authorization
- [ ] Implement `WorklogPolicy` (or Gate) for:
  - view assigned worklogs
  - review assigned worklogs
  - prevent viewing/reviewing other tutors'
- [ ] Register policy in `AuthServiceProvider` (or ensure gate usage)

## 4) Form Request
- [ ] Add `ReviewWorklogRequest`:
  - `status` required in {approved,rejected}
  - `feedback` nullable|string|max:5000

## 5) Service Layer
- [ ] Create `WorklogService` methods:
  - listTutorWorklogs(user, filters, pagination)
  - getTutorWorklog(user, id)
  - reviewTutorWorklog(user, worklog, data)
- [ ] Map API statuses approved/rejected to DB enums Approved/Rejected
- [ ] Enforce business rules:
  - only pending/submitted may be reviewed
  - prevent duplicate review if already finalized

## 6) Controller refactor
- [ ] Refactor `TutorWorklogController` to be thin:
  - authorize
  - validate via Form Request
  - call service
  - return JSON with required shape

## 7) Response shaping + eager loading
- [ ] Ensure eager loading to avoid N+1:
  - worklog + student
  - attachments
  - company/internship/review info if relationships exist

## 8) Tests
- [ ] Add/extend feature tests for tutor worklog endpoints:
  - guest 401
  - non-tutor 403
  - tutor list assigned
  - tutor cannot access other tutor student
  - tutor can approve/reject pending
  - validation 422
  - DB updates reviewer_id/reviewed_at

## 9) Verify
- [ ] Run test suite and fix failures
- [ ] Confirm route list and migration status

