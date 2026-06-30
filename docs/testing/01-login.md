# Login — Test Cases

**Module:** Login / Authentication  
**Controller:** `Login.php`  
**URL:** `/portal`

---

## TC-LGN-001 — Login page loads correctly

🔴 Critical | Pre-condition: None

**Steps:**
1. Open browser
2. Go to `/portal`

**Expected Result:**
- Login page loads without error
- Email input field visible
- Password input field visible
- Sign In button visible
- No console errors

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-LGN-002 — Login with valid credentials

🔴 Critical | Pre-condition: Valid account exists

**Steps:**
1. Go to `/portal`
2. Enter valid email
3. Enter valid password
4. Click Sign In

**Expected Result:**
- Redirected to `/dashboard`
- Dashboard page loads with KPI cards visible
- User name shown in top nav/header
- Session is active (can navigate to other pages)

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-LGN-003 — Login with wrong password

🔴 Critical | Pre-condition: None

**Steps:**
1. Go to `/portal`
2. Enter valid email
3. Enter wrong password: `wrongpass123`
4. Click Sign In

**Expected Result:**
- Stays on `/portal` (no redirect)
- Error message shown (e.g. "Invalid credentials" or similar)
- No session created

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-LGN-004 — Login with empty fields

🔴 Critical | Pre-condition: None

**Steps:**
1. Go to `/portal`
2. Leave email and password empty
3. Click Sign In

**Expected Result:**
- Form validation prevents submission
- Error message or browser validation shown
- No redirect

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-LGN-005 — Logout

🔴 Critical | Pre-condition: Logged in (TC-LGN-002 passed)

**Steps:**
1. Click on user avatar / profile in top nav
2. Click Logout

**Expected Result:**
- Redirected to `/portal` (login page)
- Cannot access `/dashboard` directly (redirected back to login)
- Session is cleared

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________

---

## TC-LGN-006 — Access protected page without login

🔴 Critical | Pre-condition: Logged out

**Steps:**
1. Ensure you are logged out
2. Try to navigate directly to `/dashboard`

**Expected Result:**
- Redirected to `/portal` (login page)
- Dashboard content is not shown

**Status:** `[ ] Pass` `[ ] Fail` `[ ] Blocked`  
**Bug:** _______________
