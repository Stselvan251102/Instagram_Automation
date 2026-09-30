# Carouselfy — AI Instagram Carousel Automation & Studio

A production-ready Instagram Carousel Automation and Canva-style Design Studio converted from Lovable AI to run natively on **Hostinger shared hosting (Apache + PHP + MySQL + HTML/CSS/JavaScript)** without requiring Node.js at runtime.

---

## 🚀 Overview

**Carouselfy** allows content creators, engineers, and marketers to:
- **Design Scroll-Stopping Carousels:** High-resolution multi-slide editor supporting **4:5 Portrait (1080×1350)**, **1:1 Square (1080×1080)**, and **9:16 Stories (1080×1920)**.
- **AI Content Engine:** Automatically generate 3–10 slide carousels for technical topics (Python, Salesforce, System Design, etc.) using OpenAI GPT-4o or a built-in offline technical content bank.
- **Canva-Style Visual Editing:** Drag-and-drop bounding boxes, resize handles, typography controls, and inline double-click text editing.
- **Pre-Flight Linter:** Automated quality checks for safe-zone compliance (Instagram header, action buttons, swipe indicators), WCAG 4.5:1 contrast, and text overflow with 1-click auto-fix.
- **Brand Kits:** Manage multiple brand presets, Instagram handles (`@handle`), profile URLs, logos, and custom color palettes.
- **Direct Instagram Publishing:** One-click publishing of multi-slide carousels and single images directly to Instagram Business & Creator accounts via the official Meta Graph API.
- **Post Scheduling & Automation:** Schedule carousels for automatic publishing at peak engagement times, powered by Hostinger cPanel Cron Jobs.
- **Multi-Format Retina Export:** Export carousels as retina 2× ZIP (PNGs), multi-page PDF, or single PNG/JPG slides.

---

## 🛠 Technology Stack

- **Backend Runtime:** PHP 8.1 / 8.2 / 8.3 (Native Hostinger shared hosting support)
- **Database:** MySQL 5.7+ / 8.0+ or MariaDB 10.3+ via PHP Data Objects (PDO) with prepared statements
- **Web Server:** Apache 2.4+ with `mod_rewrite`, `.htaccess`, security headers, and upload hardening
- **Frontend Engine:** Production React & Tailwind CSS vector canvas bundled into static assets in `public_html/assets/`
- **APIs & Integrations:**
  - **Meta / Instagram Graph API:** Official Instagram Business Publishing API
  - **OpenAI API:** GPT-4o / GPT-4o-mini Chat Completion with structured JSON output and built-in offline fallback

---

## 📁 Project Structure

```
├── public_html/                      # Hostinger public webroot
│   ├── .htaccess                     # Apache clean routing, security headers & script blocking
│   ├── index.php                     # Main Carousel Studio entry point
│   ├── dashboard.php                 # Project & post statistics dashboard
│   ├── schedule.php                  # Post scheduling manager & queue
│   ├── accounts.php                  # Connected Instagram accounts manager
│   ├── settings.php                  # System diagnostics & API credential status
│   ├── login.php                     # User authentication login
│   ├── register.php                  # User registration
│   ├── logout.php                    # Session termination
│   ├── assets/
│   │   ├── css/studio.css            # Production CSS & Tailwind styles
│   │   └── js/studio.js              # Production React Canvas bundle
│   ├── api/
│   │   ├── ai/generate.php           # AI carousel generation endpoint
│   │   ├── projects/                 # Save, list, get, delete project endpoints
│   │   ├── brandkits/                # Save and list brand kit endpoints
│   │   ├── instagram/                # OAuth connect, callback, publish & schedule endpoints
│   │   ├── auth/                     # Login, register, status, logout endpoints
│   │   └── upload.php                # Secure slide & image upload handler
│   ├── cron/
│   │   └── publish_scheduled.php     # Cron runner for automated publishing
│   └── uploads/                      # Uploaded assets & slide buffers
│       ├── .htaccess                 # Disables PHP script execution in uploads
│       └── .gitkeep
├── config/
│   ├── config.php                    # Application configuration & .env parser
│   ├── config.example.php            # Example PHP configuration
│   └── database.php                  # PDO database connection singleton
├── includes/
│   ├── header.php                    # Reusable dark studio navigation header
│   ├── footer.php                    # Reusable footer
│   ├── functions.php                 # Helper functions, CSRF, sanitization & session
│   ├── auth.php                      # Authentication service
│   ├── ai.php                        # OpenAI API client & fallback content bank
│   └── instagram.php                 # Meta / Instagram Graph API client
├── database.sql                      # MySQL database schema & seed data
├── .env.example                      # Environment variables template
├── .gitignore                        # Git exclusions (.env, node_modules, uploads)
└── README.md                         # Documentation
```

---

## 💻 Local Setup & Testing

### 1. Requirements
- PHP 8.1+ with `curl`, `pdo_mysql`, `openssl`, `mbstring`, and `gd` extensions enabled.
- (Optional) Node.js 18+ and npm only if you want to rebuild the frontend assets.

### 2. Configure Environment
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
Edit `.env` to provide your MySQL database credentials (if available locally) and OpenAI/Meta API keys.

### 3. Run with Built-in PHP Server
Start the application locally without needing Apache:
```bash
php -S 127.0.0.1:8088 -t public_html
```
Open your browser and navigate to:
```
http://127.0.0.1:8088/
```

### 4. Rebuilding Frontend (Developers Only)
If you modify React components in `src/`, rebuild the production bundle:
```bash
npm install
npm run build:client
```
The output will compile directly into `public_html/assets/js/studio.js` and `public_html/assets/css/studio.css`.

---

## 🌐 Hostinger Shared Hosting Deployment

### Step 1: Connect Repository or Upload Files
1. Log in to your **Hostinger Control Panel (hPanel)**.
2. Under **Advanced**, click **GIT**.
3. Create a new repository:
   - **Repository URL:** `https://github.com/Stselvan251102/Instagram_Automation.git`
   - **Branch:** `main`
   - **Install Path:** `public_html` (or your domain root)
4. Click **Create** to deploy.

### Step 2: Configure PHP Version
1. In hPanel, navigate to **Advanced → PHP Configuration**.
2. Select **PHP 8.2** or **PHP 8.3**.
3. Under the **PHP Extensions** tab, ensure the following are enabled:
   - `pdo_mysql`
   - `curl`
   - `gd`
   - `openssl`
   - `mbstring`

### Step 3: Create MySQL Database & Import Schema
1. In hPanel, navigate to **Databases → Management**.
2. Create a new MySQL database (note the database name, username, and password).
3. Click **Enter phpMyAdmin** next to the newly created database.
4. Click the **Import** tab and choose `database.sql` from the repository root.
5. Click **Go** to create the tables (`users`, `projects`, `brand_kits`, `instagram_accounts`, `scheduled_posts`, `activity_logs`, `settings`).

### Step 4: Configure Environment Variables (.env)
1. Using the Hostinger **File Manager**, create a file named `.env` in the root directory (one level above or directly in `public_html`).
2. Populate `.env` using `.env.example`:
```ini
APP_ENV=production
APP_URL=https://yourdomain.com
APP_SECRET=your_32_character_random_secret

DB_HOST=localhost
DB_PORT=3306
DB_NAME=u123456789_carouselfy
DB_USER=u123456789_user
DB_PASSWORD=your_database_password

OPENAI_API_KEY=sk-proj-your_openai_key
OPENAI_MODEL=gpt-4o-mini

INSTAGRAM_APP_ID=your_meta_app_id
INSTAGRAM_APP_SECRET=your_meta_app_secret
INSTAGRAM_REDIRECT_URI=https://yourdomain.com/api/instagram/callback.php
INSTAGRAM_GRAPH_VERSION=v20.0

CRON_SECRET=your_custom_cron_secret_token
```

### Step 5: Enable SSL & Verify Domain
1. In hPanel, go to **Security → SSL** and install a free Let's Encrypt SSL certificate.
2. In **Website → Force HTTPS**, turn on HTTPS redirection.
3. Test your domain: `https://yourdomain.com/settings.php` to verify all checks pass.

### Step 6: Configure Automated Background Publishing (Cron Job)
1. In hPanel, go to **Advanced → Cron Jobs**.
2. Select **Custom** type.
3. Schedule to run every minute:
   - **Minute:** `*`
   - **Hour:** `*`
   - **Day:** `*`
   - **Month:** `*`
   - **Weekday:** `*`
4. Enter the command (replace with your Hostinger username):
```bash
php /home/u123456789/domains/yourdomain.com/public_html/cron/publish_scheduled.php > /dev/null 2>&1
```

---

## 🔒 Security Best Practices

1. **No Sensitive Keys in Version Control:** Never commit `.env` or real API credentials to Git. `.gitignore` is pre-configured to strictly ignore `.env`, `.env.*`, and temporary uploads.
2. **Prepared Statements Everywhere:** All database interactions use PDO prepared statements to completely eliminate SQL injection vulnerabilities.
3. **Upload Directory Hardening:** The `public_html/uploads/` directory includes an `.htaccess` file preventing execution of any `.php`, `.phtml`, or executable scripts.
4. **MIME & File Validation:** Uploads strictly validate file magic bytes (MIME types) and extensions (`png`, `jpeg`, `webp`), limiting files to 15MB.
5. **CSRF Protection:** State-changing requests validate a session-bound CSRF token.
6. **Cron Webhook Token:** Direct HTTP access to the cron runner is protected by `?secret=CRON_SECRET`.

---

## 📦 GitHub Deployment

- **Repository:** `https://github.com/Stselvan251102/Instagram_Automation.git`
- **Branch:** `main`

To deploy updates, commit and push to `main`. Hostinger's Git deployment feature will automatically sync the changes.
