# JDBMS — Jail Database Management System

A web-based **Jail Database Management System** for managing prisoners, duties, parole evaluations, visits, incidents, and announcements. Built with PHP and MySQL, with a responsive UI for desktop and mobile.

---<img width="1920" height="1030" alt="13" src="https://github.com/user-attachments/assets/db249983-3b67-43fe-aabd-dd06c4f91a1d" />
<img width="1920" height="1030" alt="12" src="https://github.com/user-attachments/assets/8f9da7d4-31f2-432a-98c9-54f69639414e" />
<img width="1920" height="1030" alt="11" src="https://github.com/user-attachments/assets/0f99a0ac-e177-4f77-83f7-2935a7ffd191" />
<img width="1920" height="1030" alt="10" src="https://github.com/user-attachments/assets/0aeb6740-cf19-4a0e-a2ca-98dc3dd4cdaf" />
<img width="1920" height="1030" alt="9" src="https://github.com/user-attachments/assets/93bc4290-5b64-4081-bad4-2e8ba9490b03" />
<img width="1920" height="1030" alt="8" src="https://github.com/user-attachments/assets/cf7f550b-487e-4a97-93ab-c37a13651b19" />
<img width="1920" height="1030" alt="6" src="https://github.com/user-attachments/assets/aa449616-0717-4abd-89ba-9cb6d5c0992f" />
<img width="1920" height="1030" alt="5" src="https://github.com/user-attachments/assets/0b68264a-1420-4ff2-b230-2927d387a8de" />
<img width="1920" height="1030" alt="4" src="https://github.com/user-attachments/assets/7475b7df-e197-48d5-96e1-acc53074864e" />
<img width="1920" height="1030" alt="3" src="https://github.com/user-attachments/assets/8172c5b7-31f6-4b57-873d-04509ed6506d" />
<img width="1920" height="1030" alt="2" src="https://github.com/user-attachments/assets/514c5e39-051a-4254-8e42-5fde02836119" />
<img width="1920" height="1030" alt="1" src="https://github.com/user-attachments/assets/be4a16af-e1c3-4094-9146-9628f501b696" />
<img width="1920" height="1030" alt="01" src="https://github.com/user-attachments/assets/49d41fa3-4d8b-4724-abff-ca32485490e5" />




## Features

### Admin
- **Dashboard** — View all prisoners with search and filters (All, Pending approvals, Parole requests)
- **Add / Edit prisoner** — Full profile: personal info, family, physical details, crime, sentence
- **Prisoner profile** — View profile, approve duty work, edit, delete, run parole evaluation
- **Parole evaluation** — Points, time served, eligibility; confirm decision (Normal / Paroled / Isolated)
- **Reports** — Statistics (totals, by status, pending duties, parole requests, incidents, visits) and upcoming sentence end dates
- **Visitors** — Log visits with prisoner search and inline visitor details (name, relation, phone); update visit status
- **Incidents** — Report and list incidents (type, severity, action taken)
- **Announcements** — Create announcements and show/hide for prisoners

### Prisoner
- **Dashboard** — Summary, personal file, request duty, duty history, announcements
- **Parole** — Check eligibility and request parole review
- **My visits** — View visit history

### General
- Role-based access (admin / prisoner)
- Responsive layout and mobile-friendly navigation (hamburger menu)
- Shared header and footer across pages

---

## Tech Stack

- **Backend:** PHP (session-based auth)
- **Database:** MySQL (MySQLi)
- **Frontend:** HTML, CSS (custom), minimal JavaScript (menu toggle, prisoner search)

---

## Requirements

- PHP 7.4+ (or 8.x)
- MySQL 5.7+ / MariaDB
- Web server (Apache with mod_php, or PHP built-in server)
- XAMPP / WAMP / LAMP (or similar) is typical for local use

---

## Installation

### 1. Clone or download the project

```bash
git clone https://github.com/YOUR_USERNAME/JAIL-DBMS.git
cd JAIL-DBMS
```

(Replace `YOUR_USERNAME` with your GitHub username.)

### 2. Database setup

1. Start MySQL (e.g. via XAMPP).
2. Create the database and tables:
   - **Option A — phpMyAdmin:** Create a database (e.g. `jdbms_db`), then **Import** the SQL file:
     - Use `database.sql` for the core schema and initial data.
     - If you have `database_additions.sql` or `database_newupdate.sql`, import those as well for extra features (visitors, visit log, incidents, announcements).
   - **Option B — Command line:**
     ```bash
     mysql -u root -p < database.sql
     # If you have additional SQL files:
     mysql -u root -p jdbms_db < database_additions.sql
     ```

3. Default admin login: **username** `admin`, **password** `admin123`.

### 3. Configure database connection

Edit `db.php` and set your MySQL credentials:

```php
$host = 'localhost';
$dbname = 'jdbms_db';
$user = 'root';
$pass = '';   // Your MySQL password
```

### 4. Run the application

- **With XAMPP:** Put the project in `htdocs` (e.g. `htdocs/JAIL-DBMS`) and open:  
  `http://localhost/JAIL-DBMS/`
- **With PHP built-in server:**
  ```bash
  cd JAIL-DBMS
  php -S localhost:8000
  ```
  Then open: `http://localhost:8000/`

---

## Project Structure

```
JAIL-DBMS/
├── index.php              # Login page
├── login.php              # Login (alternate)
├── logout.php
├── db.php                 # Database connection
├── database.sql           # Core database schema + seed data
├── admin_dashboard.php
├── add_prisoner.php
├── edit_prisoner.php
├── prisoner_profile.php
├── evaluate_prisoner.php
├── reports.php
├── visitors.php
├── incidents.php
├── announcements.php
├── prisoner_dashboard.php
├── prisoner_parole.php
├── my_visits.php
├── includes/
│   ├── header.php
│   └── footer.php
├── assets/
│   └── css/
│       └── style.css
└── README.md
```

---

## Updating on GitHub

1. **Initialize Git (if not already):**
   ```bash
   git init
   git add .
   git commit -m "Initial commit: JDBMS project"
   ```

2. **Add your repository and push:**
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/JAIL-DBMS.git
   git branch -M main
   git push -u origin main
   ```

3. **Later updates:**
   ```bash
   git add .
   git commit -m "Describe your changes"
   git push
   ```

**Note:** Do not commit `db.php` with real passwords if the repo is public. Use a `db.example.php` with placeholders and add `db.php` to `.gitignore`, or use environment variables.

---

## License

You can add a license (e.g. MIT, GPL) here if you wish.
