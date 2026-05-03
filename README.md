# 🎓 Student Management System (SMS)
## Complete Setup & Installation Guide

---

## 📁 Folder Structure

```
sms/
├── assets/
│   ├── css/
│   │   └── style.css          ← Global dark theme stylesheet
│   └── js/
│       └── app.js             ← OTP input, password strength, etc.
├── config/
│   ├── config.php             ← App settings, mail config, sessions
│   └── database.php           ← PDO singleton (edit credentials here)
├── controllers/
│   ├── AuthController.php     ← Login, register, OTP, logout
│   ├── StudentController.php  ← CRUD operations
│   └── PermissionController.php ← RBAC management
├── includes/
│   ├── auth.php               ← Session guards, helpers
│   ├── footer.php             ← HTML footer partial
│   ├── header.php             ← HTML header partial
│   ├── mailer.php             ← PHPMailer wrapper + SMS sim
│   └── navbar.php             ← Navigation bar
├── models/
│   ├── Otp.php                ← OTP generation & verification
│   ├── Permission.php         ← RBAC permission model
│   ├── Student.php            ← Student CRUD model
│   └── User.php               ← User auth model
├── vendor/
│   └── phpmailer/             ← (optional) Place PHPMailer files here
├── dashboard.php              ← Main dashboard
├── database.sql               ← SQL schema + default admin
├── index.php                  ← Entry point (redirect)
├── login.php                  ← Login page
├── logout.php                 ← Logout handler
├── manage_permissions.php     ← RBAC management (Super Admin only)
├── otp_verify.php             ← 2FA OTP verification page
├── register.php               ← New user registration
└── students.php               ← Student CRUD interface
```

---

## ⚡ Step-by-Step Installation

### Step 1 — Install XAMPP

Download and install XAMPP from https://www.apachefriends.org/

Start these services in XAMPP Control Panel:
- ✅ Apache
- ✅ MySQL

---

### Step 2 — Copy Project Files

Copy the entire `sms/` folder into your XAMPP web root:

**Windows:** `C:\xampp\htdocs\sms\`
**macOS:**   `/Applications/XAMPP/htdocs/sms/`
**Linux:**   `/opt/lampp/htdocs/sms/`

---

### Step 3 — Create the Database

1. Open your browser and go to: http://localhost/phpmyadmin
2. Click **"New"** in the left sidebar to create a database (or use the SQL tab)
3. Click on the **SQL** tab
4. Copy & paste the entire contents of `database.sql`
5. Click **"Go"** to execute

This will:
- Create the `sms_db` database
- Create all 4 tables (users, permissions, students, otp_codes)
- Insert the default Super Admin account

---

### Step 4 — Configure Database Connection

Open `config/database.php` and update if needed:

```php
define('DB_HOST', 'localhost');   // usually localhost
define('DB_NAME', 'sms_db');     // database name
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password (blank for XAMPP default)
```

---

### Step 5 — (Optional) Configure Email for Real OTP Delivery

Open `config/config.php` and update the mail settings:

```php
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'your_email@gmail.com');
define('MAIL_PASSWORD', 'your_app_password');   // Gmail App Password
define('MAIL_FROM',     'your_email@gmail.com');
```

**Gmail Setup:**
1. Enable 2-Step Verification on your Google account
2. Go to Google Account → Security → App Passwords
3. Generate an App Password and paste it in MAIL_PASSWORD

---

### Step 6 — (Optional) Configure SMS for Real OTP Delivery

Open `config/config.php` and update the Twilio settings:

```php
define('TWILIO_SID',    'your_twilio_account_sid');
define('TWILIO_TOKEN',  'your_twilio_auth_token');
define('TWILIO_FROM',  'your_twilio_phone_number');
```

**Twilio Setup:**
1. Sign up at https://www.twilio.com/
2. Get your Account SID and Auth Token from the Dashboard
3. Buy a phone number for sending SMS
4. Paste the credentials in the config

**Without SMS Config:** SMS delivery will not be available until Twilio is configured.
Make sure SMTP is configured correctly so OTPs arrive by email.

---

### Step 7 — (Optional) Install PHPMailer and Twilio

If you want full email and SMS support:

**Option A — Composer (recommended):**
```bash
cd C:\xampp\htdocs\sms
composer require phpmailer/phpmailer
composer require twilio/sdk
```
Then update the paths in `includes/mailer.php` if needed.

**Option B — Manual:**
1. Download PHPMailer from https://github.com/PHPMailer/PHPMailer/releases
2. Extract and copy `PHPMailer.php`, `SMTP.php`, `Exception.php`
   into `sms/vendor/phpmailer/`
3. For Twilio, use Composer as above (manual installation is complex)

---

### Step 8 — Access the Application

Open your browser and go to:

```
http://localhost/sms
```

You will be redirected to the login page.

---

## 🔐 Default Credentials

| Field    | Value        |
|----------|--------------|
| Username | `superadmin` |
| Password | `Admin@1234` |
| Role     | Super Admin  |

**Important:** Change this password after first login!

---

## 🛡️ How the System Works

### Authentication Flow

```
Register → Login (username + password) → OTP Sent → OTP Verify → Dashboard
```

1. User registers with username, email, phone, password
2. User logs in — password verified via `password_verify()`
3. A 6-digit OTP is generated, saved to DB, and sent via **email** and **SMS**
4. OTP expires in 5 minutes and can only be used once
5. After correct OTP → session is created with role & permissions

### RBAC (Role-Based Access Control)

| Role        | Access                                    |
|-------------|-------------------------------------------|
| Super Admin | Full access + can manage user permissions |
| User        | Access based on assigned permissions only |

### Permissions

Super Admin can grant/revoke 4 permissions per user:

| Permission | What it allows              |
|------------|-----------------------------|
| View       | See the student list        |
| Add        | Create new student records  |
| Edit       | Modify existing records     |
| Delete     | Remove student records      |

- Frontend: buttons are hidden if permission not granted
- Backend: all actions verify permissions server-side too

---

## 🔒 Security Features

- ✅ Passwords hashed with `password_hash()` (bcrypt, cost 12)
- ✅ OTP hashed — single-use, 5-minute expiry
- ✅ PDO prepared statements — SQL injection prevention
- ✅ CSRF tokens on every form
- ✅ Session regeneration after login
- ✅ `httponly` + `samesite=Strict` session cookies
- ✅ All inputs sanitized with `htmlspecialchars()`
- ✅ Direct URL access blocked (auth check every page)
- ✅ Backend RBAC enforcement (not just frontend hiding)

---

## 🐞 Troubleshooting

**"Database connection failed"**
→ Ensure MySQL is running in XAMPP
→ Check credentials in `config/database.php`

**"Page not found" / 404**
→ Ensure the folder is named `sms` inside `htdocs`
→ Check Apache is running

**OTP not received by email**
→ Verify MAIL_* settings in `config/config.php`
→ Gmail: ensure you're using an App Password, not your account password
→ Check your spam/junk folder if the email is delayed

**Permissions not updating**
→ The user must log out and log back in for new permissions to load
→ Or refresh session manually via the dashboard

---

## 📋 Pages Reference

| URL                              | Description                  | Access          |
|----------------------------------|------------------------------|-----------------|
| `/sms/`                          | Redirects to login/dashboard | Public          |
| `/sms/register.php`              | Create new account           | Public          |
| `/sms/login.php`                 | Sign in                      | Public          |
| `/sms/otp_verify.php`            | 2FA OTP verification         | After login     |
| `/sms/dashboard.php`             | Main dashboard               | Authenticated   |
| `/sms/students.php`              | Student CRUD                 | Authenticated   |
| `/sms/manage_permissions.php`    | RBAC management              | Super Admin only|
| `/sms/logout.php`                | Sign out                     | Authenticated   |
