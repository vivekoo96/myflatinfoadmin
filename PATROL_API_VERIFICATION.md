# Patrol API Verification - Guard & BA Flow

## Backend Setup ✓

### 1. Database & Models
- ✓ Migration: `2026_04_27_000001_create_patrol_daily_logs_table.php`
- ✓ Model: `PatrolDailyLog.php`
- ✓ Relationships: schedule(), location(), guardUser()

### 2. API Routes (api.php - lines 259-261)
```php
Route::get('get-patrol-tasks', [PatrolTaskController::class, 'getPatrolTasks']);
Route::get('get-patrol-task-locations', [PatrolTaskController::class, 'getTaskLocations']);
Route::post('patrol-task-checkin', [PatrolTaskController::class, 'submitCheckin']);
```

### 3. API Endpoints
Located in `app/Http/Controllers/Api/PatrolTaskController.php`

---

## Guard (Security Personnel) Flow ✓

### Authentication
- Token-based API (Bearer {{token}})
- Must have `gate` assigned to their user account

### Endpoint 1: Get Patrol Tasks
**GET** `/api/get-patrol-tasks?date=2026-04-27`

**Who can access:** Guards with gate assigned  
**What they see:** All patrol schedules for their assigned gate

**Response includes:**
- Gate name
- Total tasks count
- Completed tasks count
- Each task shows: completed_locations, total_locations, task_status (Pending/onProgress/Completed/Missed)

### Endpoint 2: Get Task Locations
**GET** `/api/get-patrol-task-locations?schedule_id=66&date=2026-04-27`

**Who can access:** Guards with gate assigned  
**What they see:** All physical locations eligible for that schedule on that date

**Response includes:**
- Location details (id, name, description, qr_string)
- Whether location is completed (by this specific guard)
- Check-in time (if completed)

### Endpoint 3: Submit Check-In
**POST** `/api/patrol-task-checkin`

**Who can access:** ONLY Guards with gate assigned  
**What they can do:** Check in at a location (photo or QR)

**Request body (formdata):**
- patrol_task_id (required)
- patrol_location_id (required)
- gate_id (required)
- checkin_type (required: photo or qr)
- photo (if checkin_type=photo)
- qr_scanned_value (if checkin_type=qr)
- date (optional, defaults to today)

**Validation:**
- ✓ QR code must match location's qr_string
- ✓ Prevents duplicate check-ins
- ✓ Location must be created before patrol date
- ✓ Returns updated task progress

---

## Building Admin (BA) Flow ✓

### Authentication
- Token-based API (Bearer {{token}})
- Must provide `building_id` parameter in API calls

### Admin Panel - Manage Locations
**Access:** `{{ADMIN_URL}}/patrol-location`

**What BA can do:**
1. ✓ View all patrol locations and schedules
2. ✓ Create physical locations (no gate_id)
   - Name, description, etc.
3. ✓ Create patrol schedules (with gate_id)
   - Select gate + shift + patrol_time
4. ✓ Delete locations/schedules
5. ✓ View QR codes for locations

**Permission check:** Line 18 - `Auth::user()->role !== 'BA'`

### Endpoint 1: Get Patrol Tasks (Read-Only)
**GET** `/api/get-patrol-tasks?building_id=1&date=2026-04-27`

**Who can access:** BA (with building_id parameter)  
**What they see:** ALL patrol schedules for their building (across all gates)

**Response includes:**
- All schedules for the building
- Total completion count (by ALL guards, not one guard)
- Each task shows overall progress

**Difference from Guard:**
- Shows ALL gates' schedules (not just one)
- Shows aggregate completion (all guards combined)

### Endpoint 2: Get Task Locations (Read-Only)
**GET** `/api/get-patrol-task-locations?schedule_id=66&building_id=1&date=2026-04-27`

**Who can access:** BA (with building_id parameter)  
**What they see:** All locations for a task with aggregate completion

**Difference from Guard:**
- Shows completion status from ANY guard (not specific to one)

### Endpoint 3: Submit Check-In
❌ **BA CANNOT use this endpoint**

**Response:** `"Only guards can check in. Please select a gate first"`

---

## Complete End-to-End Flow

### For Guard:
1. Guard authenticates with token
2. Guard calls `/get-patrol-tasks` (automatic building from gate)
3. Guard sees 5 tasks for their gate on today's date
4. Guard clicks on Task #2 → calls `/get-patrol-task-locations?schedule_id=2`
5. Guard sees 4 physical locations
6. Guard checks in at Location #1 → calls `/patrol-task-checkin`
7. Guard gets updated progress: "2/4 Completed"
8. Task status updates to "onProgress"

### For BA:
1. BA authenticates with token
2. BA calls `/get-patrol-tasks?building_id=1` (building specified)
3. BA sees ALL patrol schedules across all gates
4. BA monitors progress: "Guard A: 2/4, Guard B: 1/4, Guard C: 0/4"
5. BA can view detailed locations for a schedule
6. BA CANNOT check in - only view
7. BA can go to admin panel to add new locations/schedules

---

## Postman Collection Structure

**Guard Patrol (API) - NEW** (old endpoints)
- Still available for backward compatibility
- 10 endpoints for legacy apps

**Guard Patrol (API) - Date Based - NEW** (new endpoints)
- get-patrol-tasks
- get-patrol-task-locations
- patrol-task-checkin

**Admin Panel - Guard Patrol - NEW**
- patrol-location management
- patrol-schedule creation
- QR code viewing

---

## Frontend Implementation Checklist

### For Guard App:
- [ ] Call `/get-patrol-tasks` on app open
- [ ] Show list of tasks with status badges
- [ ] On task click, call `/get-patrol-task-locations`
- [ ] Show location cards with completion status
- [ ] On location click, show camera/QR scanner
- [ ] Call `/patrol-task-checkin` with photo or QR
- [ ] Update UI with new progress
- [ ] Handle error: "Only guards can check in" → show "This feature is not available"

### For BA Admin:
- [ ] Call `/get-patrol-tasks?building_id=X` to monitor progress
- [ ] Show aggregate completion counts
- [ ] Call `/get-patrol-task-locations` to drill down
- [ ] Show which guard checked in where
- [ ] Use admin panel to create/manage locations
- [ ] Handle error gracefully if BA tries to submit check-in

---

## Testing Scenarios

### Test 1: Guard - Get Tasks
```
GET /api/get-patrol-tasks?date=2026-04-27
Header: Authorization: Bearer <guard_token>
Expected: 200 OK with gate_name and tasks array
```

### Test 2: Guard - Get Locations
```
GET /api/get-patrol-task-locations?schedule_id=66&date=2026-04-27
Header: Authorization: Bearer <guard_token>
Expected: 200 OK with locations and completion status
```

### Test 3: Guard - Check In (QR)
```
POST /api/patrol-task-checkin
Header: Authorization: Bearer <guard_token>
Body: patrol_task_id=66, patrol_location_id=1, gate_id=5, checkin_type=qr, qr_scanned_value=ABC123
Expected: 200 OK with updated progress
```

### Test 4: BA - Get Tasks
```
GET /api/get-patrol-tasks?building_id=1&date=2026-04-27
Header: Authorization: Bearer <ba_token>
Expected: 200 OK with ALL schedules and aggregate counts
```

### Test 5: BA - Get Locations
```
GET /api/get-patrol-task-locations?schedule_id=66&building_id=1&date=2026-04-27
Header: Authorization: Bearer <ba_token>
Expected: 200 OK with locations (completed by any guard)
```

### Test 6: BA - Try Check In (Should Fail)
```
POST /api/patrol-task-checkin
Header: Authorization: Bearer <ba_token>
Body: (any check-in data)
Expected: 403 Forbidden with "Only guards can check in"
```

### Test 7: No Gate + No Building ID (Should Fail)
```
GET /api/get-patrol-tasks
Header: Authorization: Bearer <token>
Expected: 403 Forbidden with "Please select a gate first or provide building_id"
```

---

## Status Summary

✓ Backend API: Implemented  
✓ Guard Flow: Complete  
✓ BA Read Access: Complete  
✓ BA Check-In Prevention: Implemented  
✓ Admin Panel Integration: Already available  
⏳ Frontend: Ready for implementation
