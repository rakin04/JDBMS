# New JDBMS Features – How to Apply

## 1. Run the database additions

Import the new tables so the extra functions work:

- **phpMyAdmin:** Open `jdbms_db`, go to **Import**, choose `database_additions.sql`, then **Go**.
- **Command line:**  
  `mysql -u root jdbms_db < database_additions.sql`

This creates: `visitor`, `visit_log`, `incident_report`, `announcement`, `activity_log`.

---

## 2. New functions overview

### Admin

| Page | Function |
|------|----------|
| **Reports** (`reports.php`) | Statistics: total prisoners, by status, pending duties, parole requests, incidents, visits today. List of upcoming sentence end dates. |
| **Visitors** (`visitors.php`) | Add visitors (name, relation, phone). Log visits (prisoner + visitor + date + duration). Update visit status (Scheduled / Completed / Cancelled / No-show). |
| **Incidents** (`incidents.php`) | Report incidents (prisoner, type, description, date, severity, action taken). View recent incidents. |
| **Announcements** (`announcements.php`) | Post announcements (title + body). Show/hide from prisoners. |

### Prisoner

| Page | Function |
|------|----------|
| **My Visits** (`my_visits.php`) | View visit history (date, visitor, relation, duration, status). |
| **Dashboard** | “Announcements” card shows active announcements. |

### Prisoner profile (admin view)

- **Incident reports** card shows all incidents for that prisoner (if any).

---

## 3. Navigation

- **Admin header:** Dashboard, Add Prisoner, **Reports**, **Visitors**, **Incidents**, **Announcements**, Logout.
- **Prisoner header:** Dashboard, Parole, **My Visits**, Logout.

If you have not run `database_additions.sql`, the new pages may show empty data or “table missing” messages until the script is applied.
