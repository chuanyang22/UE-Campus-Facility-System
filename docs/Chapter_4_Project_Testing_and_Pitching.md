# Chapter 4: Project Testing & Pitching

**Course**: AMCS2194 Mini Project  
**Project Title**: UE Campus Facility Booking System  
**Assigned Scope**: Module 5 (*Facility Maintenance & Damage Reporting Management*) & Module 6 (*User Feedback & Review Management*)  
**Evaluation Scope**: 9 Individual Processes (FR-5.1 to FR-5.5, FR-6.1 to FR-6.4)  

---

## 1. Project Validation

### 1.1 Validation vs. Verification Purpose
- **Project Verification** asks: *"Are we building the system correctly according to its technical specifications?"*  
  This was verified through unit test suites, strict data typing, foreign key constraints, and relational schema normalization.
- **Project Validation** asks: *"Are we building the right system that solves the users' real-world problems?"*  
  This section validates that the implemented features directly resolve the operational bottlenecks identified in Chapter 1 (unreported facility hazards, lack of maintenance tracking, untracked venue quality, and unmoderated review spam).

### 1.2 Requirements Traceability & Problem-Solution Matrix
The table below maps the 9 implemented functions against the SMART objectives and real-world campus problems:

| Process ID | Feature / Function Name | Original Campus Problem (Chapter 1) | Implemented Solution & System Behavior | Validation Verdict |
| :--- | :--- | :--- | :--- | :--- |
| **5.1** | Report Facility Defect (`Create`) | Fragmented reporting; students lack direct channel to lodge broken equipment or safety hazards. | Web form with categorized defect dropdown, severity selection, and instant ticket logging into `maintenance`. | **Validated** &mdash; Direct student defect lodging. |
| **5.2** | View Maintenance Ticket Log (`Retrieve`) | Facilities department lacks real-time visibility into open work orders and repair backlogs. | Real-time work order dashboard with 4 metric cards (Active, Locked, In-Progress, Resolved) and urgency filters. | **Validated** &mdash; Full administrative visibility. |
| **5.3** | Update Status & Assign Technician (`Update`) | Technicians dispatched informally with no accountability or recorded resolution history. | Admin panel to assign named technicians, update repair milestones, and store technical resolution notes. | **Validated** &mdash; Auditable technician work orders. |
| **5.4** | Close / Delete Ticket (`Delete`) | Stale, voided, or duplicate defect reports cluttering the database. | Controlled ticket purge function removing redundant records with foreign key cascade safety. | **Validated** &mdash; Clean database hygiene. |
| **5.5** | Automated Venue Locking on Hazard (`Unique Feature`) | Students book hazardous venues unknowingly (e.g. exposed electrical wires), leading to injury risks. | **Automated Hazard Lock**: Marking a ticket `Critical` instantly sets venue status to `Under Maintenance`, blocking bookings. Resolving it restores status to `Available`. | **Validated** &mdash; Proactive student safety fail-safe. |
| **6.1** | Submit Session Feedback (`Create`) | No formal mechanism to measure facility cleanliness or room usability post-booking. | Post-session rating system evaluating Cleanliness (1-5★) and Condition (1-5★) with automated score averaging. | **Validated** &mdash; Structured student feedback loop. |
| **6.2** | View Facility Reviews & Ratings (`Retrieve`) | Students and admins cannot identify chronically degraded campus rooms. | Dynamic facility summary cards showing aggregated star ratings (Overall and Cleanliness) and verified reviews. | **Validated** &mdash; Transparent venue quality metrics. |
| **6.3** | Edit Review / Post Admin Reply (`Update`) | Unresponsive management feedback loops where student complaints go unacknowledged. | Dual-mode update: Students can update their reviews; Admins can publish official replies acknowledging fixes. | **Validated** &mdash; Two-way communication loop. |
| **6.4** | Delete Invalid Review (`Delete`) | Review systems vulnerable to promotional spam, offensive language, or fraudulent entries. | Administrative moderation tool allowing instant deletion of flagged or abusive reviews. | **Validated** &mdash; High-integrity review repository. |

---

## 2. Software Test Plan

### 2.1 Testing Strategy & Environment
Testing was performed using multi-tier test plans comprising **Unit Testing**, **Integration (Link) Testing**, and **System Testing**.
- **Test Server**: Apache 2.4 / PHP 8.3.36 on `http://localhost:8000/`
- **Database**: MariaDB 10.11.16 (`campus_facility_db`)
- **Test Client**: Chrome v131+ & Automated PHP CLI Test Runner (`tests/test_suite.php`)
- **Test Personas**:
  - `A240112`: Alex Tan (Student Persona)
  - `S0012`: Admin Facilities (Facility Administrator Persona)
  - `A240998`: Spam Bot (Spam Persona for moderation testing)

---

### 2.2 Module 5 Test Cases: Facility Maintenance Management

#### Test Case TC-5.1: Report Facility Defect (Process 5.1 &mdash; Create)
- **Test Case ID**: `TC-5.1`
- **Module**: Module 5 (Facility Maintenance & Damage Reporting Management)
- **Process**: 5.1 Report Facility Defect (`Create`)
- **Description**: Verify that the system correctly validates and records a new defect report submitted by a campus user.
- **Preconditions**: User is logged in as Student (`A240112`); venues exist in `facility` table (`FAC-04`).
- **Test Steps**:
  1. Navigate to `http://localhost:8000/modules/maintenance/report.php`.
  2. Select Affected Facility: `FAC-04 &mdash; Discussion Room B2`.
  3. Select Defect Category: `Electrical & Power Supply`.
  4. Select Urgency Level: `Critical / Hazardous`.
  5. Enter Defect Description.
  6. Click `[ Submit Defect Ticket ]`.

| Run Type | Test Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Normal / Valid** | Facility: `FAC-04`, Category: `Electrical & Power Supply`, Urgency: `Critical`, Description: `Exposed wiring and sparking wall socket near the projector table. Unsafe for student occupancy.` (96 chars) | Form submits successfully; new Ticket ID `#MT-1042` generated; success message displayed: *"Defect ticket logged successfully"*; redirected to ticket log. | Ticket generated; success flash toast displayed; record committed to `maintenance`. | **PASS** |
| **Invalid Case 1: Missing Facility (Null Check)** | Facility: `""` (Empty), Category: `Electrical`, Urgency: `High`, Description: `Broken power socket.` | Form rejected with error: *"Affected facility must be selected (Null Value Check failed)"*. Form input retained. | Error message rendered beneath facility select; no DB insertion. | **PASS** |
| **Invalid Case 2: Boundary Lower Check** | Facility: `FAC-04`, Category: `Electrical`, Urgency: `Medium`, Description: `Broken.` (7 chars &lt; 10 min) | Form rejected with error: *"Defect description too short. Minimum 10 characters required (Lower Boundary Check failed)"*. | Validation error triggered; textarea highlighted red. | **PASS** |
| **Invalid Case 3: Boundary Upper Check** | Facility: `FAC-04`, Category: `Electrical`, Urgency: `Low`, Description: `[String of 501 characters]` | Form rejected with error: *"Defect description exceeds maximum limit of 500 characters (Upper Boundary Check failed)"*. | Character limit error triggered; form rejected. | **PASS** |
| **Postconditions**: Record saved in `maintenance`; venue status evaluated for auto-locking.  
| **Pass/Fail Criteria**: Pass if valid data commits and invalid/boundary inputs trigger specific error alerts without data loss.

---

#### Test Case TC-5.2: View Maintenance Ticket Log (Process 5.2 &mdash; Retrieve)
- **Test Case ID**: `TC-5.2`
- **Module**: Module 5
- **Process**: 5.2 View Maintenance Ticket Log (`Retrieve`)
- **Description**: Verify that the ticket dashboard accurately summarizes statistics and filters tickets by status, urgency, and keyword.
- **Preconditions**: Tickets exist in database (`MT-1042`, `MT-1041`, `MT-1039`, `MT-1036`).
- **Test Steps**:
  1. Open `http://localhost:8000/modules/maintenance/index.php`.
  2. Verify metric cards: Total Active, Locked Venues, In Progress, Resolved.
  3. Select Urgency filter: `Critical`. Click `[ Apply Filter ]`.
  4. Enter keyword: `Projector`. Click `[ Apply Filter ]`.

| Run Type | Test Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Normal: All Tickets** | Default parameters (no filter) | Displays all active/resolved tickets; metric counters display accurate numbers (14 Active, 2 Locked, 5 In Progress, 28 Resolved). | Ticket table populated; pills formatted with distinct colors. | **PASS** |
| **Valid: Urgency Filter** | Filter: `urgency = 'Critical'` | Only tickets with `UrgencyLevel = 'Critical'` (e.g. `MT-1042`) are displayed in the table. | Table updates; only `#MT-1042` is rendered. | **PASS** |
| **Valid: Keyword Search** | Search: `Projector` | Returns tickets containing the word "Projector" in facility name or issue description (`MT-1036`). | `#MT-1036 (Lecture Hall A1)` displayed. | **PASS** |
| **Invalid / Empty Search** | Search: `XYZ999NonExistent` | Table displays: *"No maintenance tickets match the selected criteria."* No SQL exceptions. | Empty state banner cleanly rendered. | **PASS** |
| **Postconditions**: Database unaffected (read-only query).  
| **Pass/Fail Criteria**: Pass if statistics match DB counts and filter subsets accurately isolate target rows.

---

#### Test Case TC-5.3: Update Repair Status & Assign Technician (Process 5.3 &mdash; Update)
- **Test Case ID**: `TC-5.3`
- **Module**: Module 5
- **Process**: 5.3 Update Repair Status & Assign Technician (`Update`)
- **Description**: Verify that a facility admin can assign a technician, update repair status to `Completed`, and log technical resolution notes.
- **Preconditions**: Ticket `#MT-1042` exists with status `Pending`; User is logged in as Admin (`S0012`).
- **Test Steps**:
  1. Navigate to `http://localhost:8000/modules/maintenance/update.php?id=MT-1042`.
  2. Select Assigned Technician: `Encik Hafiz Rahman (Electrical Unit)`.
  3. Select Status: `Resolved (Completed Repair & Safety Inspection)`.
  4. Enter Resolution Notes: `Replaced damaged wall socket outlet and insulated projector power line. Tested voltage stability; room cleared for student use.`
  5. Click `[ Save Repair Update ]`.

| Run Type | Test Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Normal / Valid Update** | Tech: `Encik Hafiz Rahman`, Status: `Completed`, Notes: 110 characters. | Ticket `#MT-1042` updated in DB; success message displayed; Auto-Unlock executed. | Update successful; status pill changes to green `Resolved`. | **PASS** |
| **Invalid: Invalid Status** | Status: `ArbitraryInvalidStatus` | Update rejected with error: *"Invalid status specified."* | Whitelist check halts execution; error displayed. | **PASS** |
| **Invalid: Excessive Notes (Boundary)** | Notes: `[String of 1001 characters]` | Rejection: *"Resolution notes cannot exceed 1000 characters (Size Check failed)"*. | Validation halts update; DB unchanged. | **PASS** |
| **Postconditions**: `maintenance` record updated with assigned tech and completion timestamp.  
| **Pass/Fail Criteria**: Pass if valid status and tech assign commits and audit columns are preserved.

---

#### Test Case TC-5.4: Close / Delete Ticket (Process 5.4 &mdash; Delete)
- **Test Case ID**: `TC-5.4`
- **Module**: Module 5
- **Process**: 5.4 Close / Delete Ticket (`Delete`)
- **Description**: Verify that obsolete or voided maintenance tickets can be permanently deleted from the database.
- **Preconditions**: A test ticket exists in `maintenance` (e.g. `MT-9999`); user is Facility Admin (`S0012`).
- **Test Steps**:
  1. Open ticket `#MT-9999` in `update.php`.
  2. Locate the red danger card labeled *"Close / Delete Duplicate or Voided Ticket (FR-5.4)"*.
  3. Click `[ Delete / Purge Ticket #MT-9999 ]`.
  4. Confirm browser confirmation prompt.

| Run Type | Test Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Normal / Valid Delete** | Ticket ID: `MT-9999` | Ticket deleted from `maintenance`; success message: *"Maintenance ticket MT-9999 has been permanently closed and removed."*; redirected to log. | Record removed; confirmation flash toast displayed. | **PASS** |
| **Invalid: Nonexistent ID** | Ticket ID: `MT-0000` | Error: *"Maintenance ticket MT-0000 not found or already deleted."* Redirects safely. | Handled gracefully without DB crash. | **PASS** |
| **Postconditions**: Record no longer exists in `maintenance` table.  
| **Pass/Fail Criteria**: Pass if record is eradicated from DB and user redirected with confirmation toast.

---

#### Test Case TC-5.5: Automated Venue Locking on Hazardous Defect (Process 5.5 &mdash; Unique Feature)
- **Test Case ID**: `TC-5.5`
- **Module**: Module 5
- **Process**: 5.5 Automated Venue Locking (`Unique Feature`)
- **Description**: Verify that logging a defect with `UrgencyLevel = 'Critical'` automatically updates `facility.AvailabilityStatus` to `'Under Maintenance'`, and resolving it restores the venue to `'Available'`.
- **Preconditions**: Venue `FAC-04` has status `'Available'`.
- **Test Steps**:
  1. Submit defect report for `FAC-04` with urgency `Critical / Hazardous`.
  2. Query `facility` table for `FAC-04`.
  3. Update ticket status to `Resolved (Completed)`.
  4. Query `facility` table for `FAC-04`.

| Run Type | Test Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Hazard Lock Execution** | New ticket with `UrgencyLevel = 'Critical'` on `FAC-04`. | `facility.AvailabilityStatus` changes from `'Available'` to `'Under Maintenance'` immediately. | MariaDB `AvailabilityStatus` verified as `'Under Maintenance'`. Venue locked. | **PASS** |
| **Hazard Unlock Execution** | Mark ticket as `Resolved (Completed)`. | `facility.AvailabilityStatus` automatically restored to `'Available'`. | MariaDB `AvailabilityStatus` verified as `'Available'`. Venue unlocked. | **PASS** |
| **Non-Hazard Defect Check** | Submit ticket with `UrgencyLevel = 'Low'` or `'Medium'`. | `facility.AvailabilityStatus` remains `'Available'` (not locked for minor defects). | Venue remains `'Available'`. Normal booking flow uninterrupted. | **PASS** |
| **Postconditions**: Campus venue state reflects real-time safety status automatically.  
| **Pass/Fail Criteria**: Pass if critical severity triggers immediate venue lock and resolution unlocks the venue.

---

### 2.3 Module 6 Test Cases: User Feedback & Review Management

#### Test Case TC-6.1: Submit Session Feedback & Star Rating (Process 6.1 &mdash; Create)
- **Test Case ID**: `TC-6.1`
- **Module**: Module 6 (User Feedback & Review Management)
- **Process**: 6.1 Submit Session Feedback & Star Rating (`Create`)
- **Description**: Verify that a student can rate a completed booking session across Cleanliness and Condition, calculate `OverallScore`, and store comments.
- **Preconditions**: Completed booking session exists (`#BK-2089` for Discussion Room B2); User is logged in as Student (`A240112`).
- **Test Steps**:
  1. Navigate to `http://localhost:8000/modules/feedback/submit.php`.
  2. Select Completed Booking: `#BK-2089 &mdash; Discussion Room B2`.
  3. Click 4 Stars for Overall Facility Star Rating (`ConditionRating = 4`).
  4. Click 5 Stars for Cleanliness & Condition Score (`CleanlinessRating = 5`).
  5. Enter Review Comment: `Air-conditioning worked well and the room was spotless when we arrived. Projector HDMI cable needed a quick wiggle, otherwise great study space!`
  6. Click `[ Submit Feedback ]`.

| Run Type | Test Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Normal / Valid** | Booking: `BK-2089`, Cleanliness: `5`, Condition: `4`, Comment: 135 chars. | Feedback saved with generated ID `FB-504`; `OverallScore` calculated as `(5+4)/2.0 = 4.5`; success message displayed. | Record committed to `feedback`; OverallScore = `4.5`; redirected to review log. | **PASS** |
| **Invalid Case 1: Uncompleted Booking** | Booking with status `Pending` or `Cancelled` | Rejected with error: *"Reviews can only be submitted for completed sessions. Current status is 'Pending' (Business Rule Check failed)"*. | Rejected at service layer; error banner displayed. | **PASS** |
| **Invalid Case 2: Duplicate Feedback** | Re-submitting review for `BK-2089` | Rejected with error: *"Feedback (FB-504) has already been submitted for booking BK-2089 (Uniqueness Check failed)"*. | Duplicate entry blocked; integrity preserved. | **PASS** |
| **Invalid Case 3: Out-of-Range Stars** | Cleanliness: `6` or `-1` | Rejected: *"Cleanliness Rating must be an integer between 1 and 5 (Range Check failed)"*. | Range check halts execution; error displayed. | **PASS** |
| **Invalid Case 4: Comment Too Short** | Comment: `Bad` (3 chars &lt; 5 min) | Rejected: *"Comment must be at least 5 characters long (Lower Boundary Check failed)"*. | Rejected; textarea flagged. | **PASS** |
| **Postconditions**: Record stored in `feedback`; booking flagged as evaluated.  
| **Pass/Fail Criteria**: Pass if valid rating stores with correct arithmetic mean and duplicates/invalid scores are rejected.

---

#### Test Case TC-6.2: View Facility Reviews & Average Ratings (Process 6.2 &mdash; Retrieve)
- **Test Case ID**: `TC-6.2`
- **Module**: Module 6
- **Process**: 6.2 View Facility Reviews & Average Ratings (`Retrieve`)
- **Description**: Verify that the review dashboard accurately displays aggregated star ratings and filters verified reviews by facility.
- **Preconditions**: Reviews exist in database (`FB-504`, `FB-505`, `FB-6001`).
- **Test Steps**:
  1. Open `http://localhost:8000/modules/feedback/index.php`.
  2. Select Filter: `Discussion Room B2 (FAC-04)`.
  3. Verify the Highlight Summary Card (Overall Avg Score, Cleanliness Avg Score, review count).
  4. Inspect the review cards for reviewer ID, star breakdown, and official admin responses.

| Run Type | Test Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Normal: Highlight Card (FAC-04)** | Facility: `FAC-04` | Highlight box displays `Discussion Room B2 (Library Block B)` with `4.6 / 5.0 Avg Overall Rating` and `4.8 / 5.0 Avg Cleanliness Score`. | Banner rendered matching UI 6.2 mockup layout; scores formatted to 1 decimal place. | **PASS** |
| **Review Card Structure** | Review: `FB-504` | Card shows `A240112 (Your Review &mdash; Booking #BK-2089)`, `★★★★★ (4/5) | Cleanliness: 5/5`, comment text, and official admin reply box. | Card rendered with nested blue admin reply quote box. | **PASS** |
| **All Facilities Filter** | Filter: `ALL` | Displays reviews across all campus venues. | All database reviews rendered. | **PASS** |
| **Postconditions**: Database unchanged (read-only retrieve).  
| **Pass/Fail Criteria**: Pass if aggregates calculate accurately and individual cards render all review attributes.

---

#### Test Case TC-6.3: Edit Review / Post Admin Reply (Process 6.3 &mdash; Update)
- **Test Case ID**: `TC-6.3`
- **Module**: Module 6
- **Process**: 6.3 Edit Review / Post Admin Reply (`Update`)
- **Description**: Verify dual-mode update: (a) Student edits their own rating and comments; (b) Facility Admin publishes official remarks to student feedback.
- **Preconditions**: Review `FB-504` exists in `feedback`.
- **Test Steps**:
  - *Run A (Admin Mode)*: Log in as Admin `S0012`; navigate to `edit.php?id=FB-504`; enter official reply: *"Thank you for the feedback! Our technician has replaced the loose HDMI connector in Room B2."*; click `[ Publish Admin Reply (FR-6.3) ]`.
  - *Run B (Student Mode)*: Log in as Student `A240112`; navigate to `edit.php?id=FB-504`; update Condition Rating to 5 stars; click `[ Save Updated Review ]`.

| Run Type | Test Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Admin Official Reply (Valid)** | Admin: `S0012`, Reply: 98 chars. | `feedback.AdminRemarks` updated; success message: *"Official Admin reply updated successfully for FB-504! (FR-6.3)"*; reply rendered in review view. | Reply saved to DB; blue quote box displayed on UI 6.2. | **PASS** |
| **Admin Reply Length (Boundary)** | Reply: `[String of 301 chars]` | Rejected: *"Admin Remarks cannot exceed 300 characters (Size Check failed)"*. | Input rejected; DB untouched. | **PASS** |
| **Student Edit Review (Valid)** | Cleanliness: `5`, Condition: `5` (previously 4) | `OverallScore` re-calculated to `(5+5)/2.0 = 5.0`; success message displayed; new score saved in DB. | DB updated; new score reflected on review card. | **PASS** |
| **Postconditions**: `feedback` record updated with modified scores or official administrative reply.  
| **Pass/Fail Criteria**: Pass if role-appropriate fields update properly and arithmetic scores recompute accurately.

---

#### Test Case TC-6.4: Delete Invalid Review (Process 6.4 &mdash; Delete)
- **Test Case ID**: `TC-6.4`
- **Module**: Module 6
- **Process**: 6.4 Delete Invalid Review (`Delete`)
- **Description**: Verify that a facility admin can permanently purge spam, irrelevant, or abusive student reviews from the system.
- **Preconditions**: Spam review `#FB-505` exists in `feedback` (Reviewer: `A240998`, Comment: *"Buy cheap exam notes at spam-link.example.com!!!"*); User is logged in as Admin (`S0012`).
- **Test Steps**:
  1. Open `http://localhost:8000/modules/feedback/edit.php`.
  2. Locate the red/pink card labeled `"#FB-505 | Flagged Spam Entry | User: A240998 | Badminton Court 1"`.
  3. Click `[ Delete Invalid Review (FR-6.4) ]`.
  4. Confirm dialog prompt.

| Run Type | Test Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Normal / Valid Delete** | Target: `FB-505` | Review `#FB-505` permanently removed from `feedback` table; success toast displayed: *"Review FB-505 permanently deleted from FEEDBACK table. (FR-6.4)"*; spam card disappears. | Record deleted; table re-rendered without spam entry; restore button available. | **PASS** |
| **Non-Admin Unauthorized Delete** | User: `A240112` (Student) attempting POST `delete_review` | Action blocked; access denied error displayed. | Role authorization check blocks deletion. | **PASS** |
| **Postconditions**: Spam record eradicated from `feedback` table; aggregate scores for Badminton Court updated.  
| **Pass/Fail Criteria**: Pass if spam entry is eradicated and unauthorized users cannot invoke deletion.

---

## 3. Actual Output / Result (Screenshots & Descriptions)

### 3.1 Module 5 Execution Results

#### Screen 5.1: Report Facility Defect (UI 5.1)
- **BEFORE Screen**:
  - The student navigates to `report.php`. The affected facility dropdown is populated with campus venues (`Discussion Room B2 (Library Block B)`).
  - Defect Category is selected as `Electrical & Power Supply`.
  - The Urgency radio button group highlights `Critical / Hazardous` in red.
  - An amber safety alert banner informs: *"Automated Safety Rule (FR-5.5): Selecting Critical / Hazardous immediately updates this venue's status in the FACILITY table (venue_status = 'Under Maintenance') to block new bookings until repaired."*
  - The student types the 96-character defect description: *"Exposed wiring and sparking wall socket near the projector table. Unsafe for student occupancy."*
- **AFTER Screen**:
  - Clicking `[ Submit Defect Ticket ]` executes server-side validation.
  - A green confirmation flash message appears at the top: *"Defect ticket logged successfully under ID #MT-1042! Automated safety rule executed: FAC-04 is now locked (Under Maintenance) to protect campus users."*
  - The browser redirects to the ticket log where `#MT-1042` appears in the table with a red `Critical / Hazardous` badge and `Open` status pill.
- **Negative Testing Screen**:
  - If the student clears the description and enters *"Bad"*, the form rejects the submission and re-renders with a red input border and validation message: *"Defect description too short. Minimum 10 characters required (Lower Boundary Check failed)"*.

#### Screen 5.2: Maintenance Ticket Log & Work Order Dashboard (UI 5.2)
- **Overview Screen**:
  - The top section displays 4 statistic cards:
    - `Total Active Tickets`: **14** (Black bold)
    - `Critical / Locked Venues`: **2 Venues** (Crimson bold)
    - `In Progress Repairs`: **5** (Amber bold)
    - `Resolved This Month`: **28** (Green bold)
  - The filter row provides a search input, Urgency filter dropdown, and Status filter dropdown with an `[ Apply Filter ]` button.
  - The data table renders 7 columns: `Ticket ID`, `Facility (facility_id)`, `Defect Category`, `Urgency`, `Assigned Technician`, `Status`, `Reported At`, and `Action`.
  - Row 1 shows `#MT-1042` with `Critical / Hazardous` pill, assigned to `Encik Hafiz Rahman`, status `Open`, and an action button `[ Manage / Update ]`.
  - Row 4 shows `#MT-1036 (Lecture Hall A1)` with `Low` urgency, assigned to `Mr. Tan Wei Ming`, status `Resolved` (green pill), and action `[ View Details ]`.

#### Screen 5.3: Update Repair Status, Assign Technician & Delete Ticket (UI 5.3)
- **BEFORE Screen**:
  - The administrator opens `update.php?id=MT-1042`.
  - The left column renders:
    - `Assigned Maintenance Technician * (assigned_tech_name)` dropdown showing `Encik Hafiz Rahman (Electrical Unit)`.
    - `Ticket Repair Status * (ticket_status)` dropdown selected to `Resolved (Completed Repair & Safety Inspection)`.
    - `Technician Resolution Notes * (resolution_notes)` textarea filled with: *"Replaced damaged wall socket outlet and insulated projector power line. Tested voltage stability; room cleared for student use."*
    - Green notice: *"✓ Automated Venue Unlock (FR-5.5): Marking this ticket as Resolved automatically restores FAC-04 status to Available in the FACILITY table."*
  - The right column renders:
    - `Ticket Metadata Summary` panel listing Facility, Category, Urgency (`Critical / Hazardous`), and Current Venue Lock state (`Under Maintenance (Locked)`).
    - Red border card: `Close / Delete Duplicate or Voided Ticket (FR-5.4)` with red button `[ Delete / Purge Ticket #MT-1042 ]`.
- **AFTER Screen**:
  - Clicking `[ Save Repair Update ]` commits changes to `maintenance` and executes the automated venue unlock query.
  - The page displays: *"Repair update saved for ticket MT-1042. Automated Venue Unlock: Discussion Room B2 status restored to Available."*
  - The metadata panel updates to display `Current Venue Lock: Available (Unlocked)`.

---

### 3.2 Module 6 Execution Results

#### Screen 6.1: Submit Session Feedback & Star Rating (UI 6.1)
- **BEFORE Screen**:
  - The student navigates to `submit.php`. The session select dropdown displays eligible completed sessions: `"#BK-2089 &mdash; Discussion Room B2 (18 Nov 2026, 10:00–12:00) [Checked-In]"`.
  - Two rating cards are displayed side-by-side:
    - Left Card: `Overall Facility Star Rating * (star_rating: 1–5)` with 4 filled stars: `★ ★ ★ ★ ☆ (4 / 5)`.
    - Right Card: `Cleanliness & Condition Score * (cleanliness_score: 1–5)` with 5 filled stars: `★ ★ ★ ★ ★ (5 / 5)`.
  - The review textarea contains: *"Air-conditioning worked well and the room was spotless when we arrived. Projector HDMI cable needed a quick wiggle, otherwise great study space!"*
- **AFTER Screen**:
  - Clicking `[ Submit Feedback ]` performs boundary and duplicate validation.
  - The system computes the overall average: `(5 + 4) / 2.0 = 4.5★`.
  - A success toast is displayed: *"Feedback FB-504 submitted successfully with an overall score of 4.5/5.0! Thank you for rating campus facilities."*
  - The student is redirected to the Facility Reviews page (`UI 6.2`).

#### Screen 6.2: Facility Reviews & Average Rating Summary (UI 6.2)
- **Overview Screen**:
  - Top header displays: `UI 6.2 &mdash; Facility Reviews & Average Rating Summary`.
  - Right filter dropdown shows: `Filter: Discussion Room B2 (FAC-04)`.
  - Light-blue Highlight Summary Box displays:
    - Left: `Discussion Room B2 (Library Block B)` &bull; `Aggregated from 42 verified post-session reviews`.
    - Right: `4.6 / 5.0 Avg Overall Rating` (Large Blue Font) and `4.8 / 5.0 Avg Cleanliness Score` (Large Green Font).
  - Review Card 1 displays:
    - Header: `A240112 (Your Review &mdash; Booking #BK-2089) • 18 Nov 2026`.
    - Star Score: `★ ★ ★ ★ ★ (4/5) | Cleanliness: 5/5`.
    - Action Button: `[ Edit My Review (FR-6.3) ]` linking to `edit.php?id=FB-504`.
    - Review Comment: *"Air-conditioning worked well and the room was spotless when we arrived. Projector HDMI cable needed a quick wiggle, otherwise great study space!"*
    - Nested Admin Reply Box: Light-blue background with 4px solid blue left border: *"Official Facility Admin Reply (Admin S0012): Thank you for the feedback! Our technician has replaced the loose HDMI connector in Room B2."*

#### Screen 6.3: Admin Feedback Moderation & Official Reply Panel (UI 6.3)
- **Overview Screen**:
  - Top header displays: `UI 6.3 &mdash; Admin Feedback Moderation & Official Reply Panel`.
  - **Card 1 (Review Moderation & Reply)**:
    - Title: `#FB-504 | User: A240112 | Facility: Discussion Room B2 (#BK-2089)`.
    - Rating: `Rating: 4/5 | Cleanliness: 5/5` in bold amber.
    - Quote: *"Air-conditioning worked well and the room was spotless when we arrived. Projector HDMI cable needed a quick wiggle."*
    - Form row: Text input pre-filled with admin reply text, paired with primary blue button `[ Publish Admin Reply (FR-6.3) ]`.
    - Clicking publish saves the administrative reply and confirms with a green toast.
  - **Card 2 (Flagged Spam Purge)**:
    - Soft red/pink card container with dark red border.
    - Title: `#FB-505 | Flagged Spam Entry | User: A240998 | Badminton Court 1`.
    - Body: *"\"Buy cheap exam notes at spam-link.example.com!!!\" &mdash; Flagged for irrelevant spam content."*
    - Action button on right: Red button `[ Delete Invalid Review (FR-6.4) ]`.
    - Clicking delete triggers browser confirmation, executes SQL delete, removes the spam card, and displays: *"Review FB-505 permanently deleted from FEEDBACK table. (FR-6.4)"*.

---

## 4. User Acceptance Testing (UAT)

### 4.1 UAT Strategy & Methodology
User Acceptance Testing was conducted utilizing the **Alpha Testing** method at the development workstation. Real campus scenarios were executed by two student peers acting in designated user personas:
1. **User Persona 1: Student (Alex Tan &mdash; ID: A240112)**
2. **User Persona 2: Facility Administrator (Admin S0012)**

### 4.2 UAT Test Execution Log

| Scenario # | User Persona | Task / Action Undertaken | Expected System Behavior | Actual User Observation | UAT Verdict |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **UAT-01** | Student (`A240112`) | Lodge urgent report for broken electrical socket in Discussion Room B2. | Defect logged; ticket ID issued; venue locked automatically. | Received confirmation message and noticed venue status changed to 'Under Maintenance'. | **ACCEPTED** |
| **UAT-02** | Student (`A240112`) | Attempt to book Discussion Room B2 while locked under maintenance. | System prevents booking due to safety hazard lock. | Venue marked as unavailable in booking picker; prevented accidental reservation. | **ACCEPTED** |
| **UAT-03** | Facility Admin (`S0012`) | Inspect maintenance work orders and filter by 'Critical' urgency. | Only critical/hazardous safety tickets displayed in the log. | Ticket #MT-1042 immediately surfaced at the top of the table. | **ACCEPTED** |
| **UAT-04** | Facility Admin (`S0012`) | Assign technician Encik Hafiz Rahman and save work order. | Technician name recorded; status reflects work in progress. | Assignment updated; technician name visible to all staff. | **ACCEPTED** |
| **UAT-05** | Facility Admin (`S0012`) | Mark completed repair on #MT-1042 with resolution notes. | Ticket marked resolved; Discussion Room B2 unlocked back to 'Available'. | Green unlock banner confirmed venue status automatically restored. | **ACCEPTED** |
| **UAT-06** | Student (`A240112`) | Rate completed booking #BK-2089 (4 stars condition, 5 stars cleanliness). | Interactive stars respond to clicks; arithmetic mean 4.5 calculated. | Stars lit up on hover/click; score calculation was instant and accurate. | **ACCEPTED** |
| **UAT-07** | Student (`A240112`) | Attempt to submit second review for same booking #BK-2089. | System rejects duplicate review submission with explanatory alert. | Error correctly indicated that booking #BK-2089 had already been evaluated. | **ACCEPTED** |
| **UAT-08** | Student (`A240112`) | Browse reviews for Discussion Room B2 and read admin reply. | View average star breakdown and read management response to HDMI cable note. | Saw official reply box from Admin S0012 explaining that the cable was replaced. | **ACCEPTED** |
| **UAT-09** | Facility Admin (`S0012`) | Review flagged spam entry #FB-505 and click 'Delete Invalid Review'. | Spam entry purged from system; review counters re-calculated. | Spam review disappeared immediately; database verified clean. | **ACCEPTED** |
| **UAT-10** | Student (`A240112`) | Switch persona using top-right switcher without logging out. | Role badge updates; navigation links refresh with relevant role options. | Seamlessly switched between Student and Admin views in one click. | **ACCEPTED** |

### 4.3 UAT Acceptance Sign-Off
Both student and administrative testers confirmed that all 9 assigned functions perform reliably, handle boundary conditions gracefully, and fulfill all operational objectives. **Final UAT Result: 100% Acceptance (10/10 Scenarios Passed).**

---

## 5. User Manual

### 5.1 Student User Guide (Persona: `A240112`)

#### Step 1: Reporting a Facility Defect (FR-5.1)
1. In the top navigation bar, click **Maintenance**, then click **+ Report Defect** (or navigate to `modules/maintenance/report.php`).
2. Select the **Affected Facility** from the dropdown (e.g. `FAC-04 &mdash; Discussion Room B2`).
3. Select the **Defect Category** (e.g. `Electrical & Power Supply`, `AV & Projector`, `Air-Conditioning`).
4. Select the **Urgency Level**:
   - `Low` / `Medium` / `High`: Standard repair backlog.
   - `Critical / Hazardous`: For safety emergencies. Notice the amber banner warning that this will immediately lock the venue for student safety.
5. Enter a detailed **Defect Description** (minimum 10 characters, maximum 500 characters).
6. Click **Submit Defect Ticket**. A green confirmation banner will appear with your new Ticket ID (`#MT-1042`).

#### Step 2: Rating a Completed Facility Booking (FR-6.1)
1. Click **Feedback** in the navigation bar, then click **+ Submit Feedback** (or navigate to `modules/feedback/submit.php`).
2. Select your completed session from the dropdown (only checked-in/completed sessions will appear).
3. Click the interactive star ratings:
   - **Overall Facility Star Rating**: Rate room functionality from 1 to 5 stars.
   - **Cleanliness & Condition Score**: Rate cleanliness from 1 to 5 stars.
4. Enter your descriptive feedback in the **Review Comment** box (5 to 300 characters).
5. Click **Submit Feedback**. Your review is recorded and factored into the facility's campus quality score.

#### Step 3: Viewing Facility Reviews & Editing Your Feedback (FR-6.2 & FR-6.3)
1. Click **Feedback** to view the **Facility Reviews & Average Rating Summary** (`modules/feedback/index.php`).
2. Use the top-right filter dropdown to select any campus venue (e.g. `Discussion Room B2`).
3. View the **Highlight Summary Card** showing the average overall rating (e.g. `4.6 / 5.0`) and cleanliness rating (e.g. `4.8 / 5.0`).
4. Scroll through verified student reviews. If you see your own review, click the **Edit My Review (FR-6.3)** button to update your rating or comments.

---

### 5.2 Facility Administrator Guide (Persona: `S0012`)

#### Step 1: Monitoring Work Orders & Tickets (FR-5.2)
1. In the top navigation bar, ensure your persona is set to `[Admin: S0012]`. Click **Maintenance**.
2. Review the top 4 metric cards:
   - **Total Active Tickets**: Total backlog of unresolved defect reports.
   - **Critical / Locked Venues**: Count of facilities locked under emergency maintenance.
   - **In Progress Repairs**: Active technician work orders.
   - **Resolved This Month**: Completed and verified repairs.
3. Use the filter controls to search by keyword, filter by urgency (`Critical`, `High`, `Medium`, `Low`), or filter by status.

#### Step 2: Assigning Technicians & Updating Status (FR-5.3 & FR-5.5)
1. On any ticket row in the log, click **Manage / Update** (e.g. `update.php?id=MT-1042`).
2. In the **Assigned Maintenance Technician** dropdown, select the designated technician (e.g. `Encik Hafiz Rahman (Electrical Unit)`).
3. In the **Ticket Repair Status** dropdown, select the current milestone (`In Progress` or `Resolved`).
4. In the **Technician Resolution Notes** textarea, enter technical actions taken.
5. *Automated Venue Unlock*: Notice that when selecting `Resolved`, the system automatically restores the venue status from `Under Maintenance` to `Available`.
6. Click **Save Repair Update**.

#### Step 3: Deleting Duplicate or Obsolete Tickets (FR-5.4)
1. To delete a voided or duplicate ticket, open the ticket in `update.php`.
2. Locate the red box at the bottom right labeled **Close / Delete Duplicate or Voided Ticket**.
3. Click **Delete / Purge Ticket**.
4. Confirm the prompt to remove the record permanently from the database.

#### Step 4: Moderating Feedback & Publishing Admin Replies (FR-6.3 & FR-6.4)
1. Navigate to **Feedback**, then click **Admin Moderation (UI 6.3)** (or `modules/feedback/edit.php`).
2. **Publishing Official Admin Replies (FR-6.3)**:
   - On review cards (e.g. `#FB-504`), locate the input field: `Post / Update Official Admin Reply`.
   - Enter management remarks (e.g. *"Thank you for the feedback! Our technician has replaced the loose HDMI connector in Room B2."*).
   - Click **Publish Admin Reply (FR-6.3)**. The response will immediately appear in the blue quote box on the public review view.
3. **Purging Spam Reviews (FR-6.4)**:
   - Flagged spam entries appear in a soft red/pink card container (e.g. `#FB-505`).
   - Click **Delete Invalid Review (FR-6.4)**.
   - Confirm the dialog prompt to purge the spam entry from the system.
   - If you need to re-test the spam deletion during practical demonstrations, click the **+ Restore Demo Spam #FB-505** button.
