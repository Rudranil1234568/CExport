🐠 CExport - Exotic Fish Exporter Website & CMS
CExport is a dynamic, single-page application (SPA) built for an ornamental fish export business. It features a modern, aquatic-themed frontend and a robust Custom CMS (Content Management System) that allows administrators to manage website text, images, video, and fish inventory without touching the code.

🚀 Features
🌊 Frontend (User Side)
Dynamic Content: All text, headings, and images are fetched from the database.

Gallery Inventory: Filterable gallery displaying freshwater and marine fish species.

Video Hero: Autoplay background video support.

Responsive Design: Built with Tailwind CSS for mobile-first layouts.

🛠️ Admin Panel (CMS)
Secure Authentication: Session-based login with password hashing.

Site Identity: Update the website logo and favicon directly from the dashboard.

Content Management:

Rich Text Editor: Integrated TinyMCE for editing "About", "Services", and "Contact" sections.

Video Manager: Upload and replace the Hero section video (.mp4/.webm).

Contact Info: Update phone numbers, emails, and addresses dynamically.

Inventory Management:

Add, Edit, and Delete fish species.

BLOB Image Storage: Images are stored securely within the database (no external storage buckets required).

Status Toggles: Instantly hide/show fish or network countries.

Analytics: Built-in visitor chart using Chart.js.

🛠️ Tech Stack
Backend: PHP (Native, No Framework)

Database: MySQL / MariaDB

Frontend: HTML5, Tailwind CSS, JavaScript

Libraries:

TinyMCE (Rich Text Editing)

Chart.js (Analytics)

Lucide Icons (UI Icons)

📂 Project Structure
Bash

/root
├── .env                # Database credentials (GIT IGNORED IN PROD)
├── index.php           # Main Frontend Website
├── view_image.php      # Helper: Renders BLOB images from DB
├── /assets             # Static assets (videos, default icons)
├── /includes
│   ├── db.php          # Database connection & Env parser
│   └── functions.php   # Helper functions
├── /admin              # CMS Folder
│   ├── index.php       # Main Admin Dashboard
│   ├── login.php       # Admin Login
│   └── logout.php      # Session destroyer
⚙️ Installation & Setup
1. Requirements
PHP >= 7.4

MySQL Server

Apache/Nginx (e.g., XAMPP, WAMP, Laragon)

2. Database Setup
Create a MySQL database named cexport_db.

Import the provided database.sql file (not included in repo, ensure you export your local DB).

Create an Admin User: Insert a user into the users table. Note: Passwords must be hashed using password_hash().

SQL

INSERT INTO users (username, password) VALUES ('admin', '$2y$10$...'); 
-- Replace '$2y$10$...' with a valid BCrypt hash
3. Configuration
Create a .env file in the project root:

Ini, TOML

DB_HOST=localhost
DB_NAME=cexport_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
4. Running the Project
Place the project folder in your htdocs or www directory.

Access the frontend: http://localhost/your-project-folder/

Access the admin panel: http://localhost/your-project-folder/admin/

🖼️ Image Handling
This project uses a unique approach where images are stored as Binary Large Objects (BLOBs) directly in the MySQL database.

Retrieval: The view_image.php file acts as a router.

Usage: <img src="view_image.php?type=gallery&id=1">

Fallback: If an image is missing, a 1x1 transparent pixel is returned to prevent broken icons.

🛡️ Security Note
The db.php file prevents direct access via URL for security.

Database errors are logged server-side and hidden from the end-user.

📝 License
MIT
