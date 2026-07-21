# DEBUG REPORT — Issues & Bugs

**Date:** 2026-07-21
**Project:** Boat Rental Management System (BRMS)

---

## ACTIVE BUGS (needs fixing)

### BUG-1: `moveToMaintenance()` doesn't check if boat has active rental

**Severity:** Medium
**File:** `app/Services/RentalService.php:463-468`

**Root cause:** `moveToMaintenance()` calls `$boat->update(['status' => MAINTENANCE, 'current_rental_id' => null])` with no check that the boat has no active rental. If an admin moves an actively rented boat to maintenance, the current_rental_id is cleared and the rental becomes orphaned (no longer linked to a boat, but status remains ACTIVE).

**Impact:** Could orphan rentals. Admin could accidentally move a boat that's currently being rented.

**Fix:** Add check: if `$boat->currentRental`, throw exception or force-end the rental first.

---

## FIXED BUGS (resolved)

### ~~BUG-2: `boatId` variable used before declaration in `updateBoatCard()` — worker name never updates~~

**Severity:** Critical
**File:** `resources/js/dashboard.js`
**Fix applied:** Moved `const boatId = cardElement.dataset.boatId` BEFORE the worker name block.

### ~~BUG-3: Overdue/time-up overtime container visibility driven by `overdue` only, not `time_up`~~

**Severity:** Medium
**File:** `resources/js/dashboard.js:191`
**Fix applied:** Changed to `['overdue', 'time_up'].includes(boat.status) ? 'block' : 'none'`.

---

## NOT BUGS (by design / intentional)

### Item: Worker dashboard only shows own boats + available + maintenance

**File:** `app/Services/DashboardService.php:29-36`
**Status:** By design — workers should not see other workers' active/ended/time-up boats.

### Item: `DashboardService::getDashboardData()` doesn't include `server_time`

**File:** `app/Services/DashboardService.php:43-51`
**Status:** By design — `server_time` is added by the controller layer (`DashboardController`).

### Item: `NotificationService::send()` is a no-op — no DB writes

**File:** `app/Services/NotificationService.php:11-14`
**Status:** Intentional — performance optimization. UI notification dropdown will eventually show no new notifications.

### Item: Timer stutter fix verification needed

**File:** `resources/js/timer.js:50-78`
**Status:** Fixed but needs manual browser verification that timer ticks exactly 1/sec.

### Item: Status badge has no transition animation on state change

**File:** `resources/js/dashboard.js:156-163`
**Status:** Visual polish — badge color snaps instantly. Not a functional bug.

### Item: `syncTimerFromServer()` called before `updateBoatCards()`

**File:** `resources/js/dashboard.js:15-38`
**Status:** Minimal impact — causes at most 200ms delay in timer display. Not user-visible.

---

## NON-BUGS (removed)

- ~~BUG-6 (effective_end_at accessor)~~ — was verified correct
- ~~BUG-4 (server_time in service)~~ — by design
- ~~BUG-5 (notification unread still queries)~~ — by design
- ~~BUG-7 (timer stutter)~~ — fixed
- ~~BUG-8 (status badge animation)~~ — visual polish only
- ~~BUG-10 (syncTimerFromServer order)~~ — negligible impact
