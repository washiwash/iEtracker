
# iETracker


iETracker is a PHP web app for employee attendance tracking and task management.
It combines daily time logs, task workflow monitoring, and profile management in one dashboard-first interface. 


## Core Module
1. Authentication Module
2. Dashboard Module
3. Attendance Module
4. Task Tracker Module
5. Profile Module
## Tech Stack

Backend: PHP 8+ (strict types, sessions, PDO prepared statements)
Database: MySQL/MariaDB
Local environment: XAMPP (Apache + MySQL)
Frontend: HTML5, CSS3, JavaScript (vanilla ES6)
UI framework: Bootstrap 5.3.8
Icons: Bootstrap Icons

## Installation

### Prerequisites
- **Operating System**: Windows (as per XAMPP download).
- **PHP Version**: 8.0 or higher (XAMPP 8.2.12 includes PHP 8.2).
- **MySQL/MariaDB**: Included in XAMPP.
- **Web Browser**: Any modern browser (e.g., Chrome, Firefox).
- **Git**: Installed for cloning the repository.
- **Disk Space**: At least 500 MB free for XAMPP and the project.

### Step 1: Download and Install XAMPP
1. Download XAMPP for Windows (x64) version 8.2.12 from the official SourceForge link:  
   [https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download](https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download).  
   - This version includes Apache, MySQL, PHP 8.2, and phpMyAdmin.
2. Run the installer as Administrator.
3. During installation:
   - Choose the default installation directory (usually `C:\xampp`).
   - Select components: Apache, MySQL, PHP, and phpMyAdmin.
4. After installation, do not start XAMPP yet.

### Step 2: Clone the Repository
1. Open Command Prompt or Git Bash.
2. Navigate to the XAMPP `htdocs` directory:  
   ```
   cd C:\xampp\htdocs
   ```
3. Clone the repository:  
   ```
   git clone https://github.com/washiwash/iEtracker
   ```
   - This creates a folder named `iEtracker` inside `htdocs`.
   - Verify the folder exists: `dir iEtracker` (should list files like `index.php`, `database/`, etc.).

### Step 3: Set Up the Database
1. Launch XAMPP Control Panel (from the Start menu or `C:\xampp\xampp-control.exe`).
2. Start the **Apache** and **MySQL** modules by clicking "Start" next to each. Wait for them to turn green.
3. Open phpMyAdmin:
   - Click the "Admin" button next to MySQL in the XAMPP Control Panel, or navigate to `http://localhost/phpmyadmin` in your browser.
4. In phpMyAdmin:
   - Click "Databases" in the top menu.
   - Under "Create database", enter `ietracker` and click "Create".
5. Select the `ietracker` database from the left sidebar.
6. Click the "SQL" tab.
7. Copy and paste the following SQL schema to create the required tables, then click "Go":

   ```sql
   -- Create users table
   CREATE TABLE users (
       id INT AUTO_INCREMENT PRIMARY KEY,
       full_name VARCHAR(255) NOT NULL,
       email VARCHAR(255) UNIQUE NOT NULL,
       job_title VARCHAR(255),
       role VARCHAR(50) DEFAULT 'user',
       password_hash VARCHAR(255) NOT NULL,
       is_active TINYINT(1) DEFAULT 1
   );

   -- Create attendance table
   CREATE TABLE attendance (
       id INT AUTO_INCREMENT PRIMARY KEY,
       user_id INT NOT NULL,
       time_in DATETIME NOT NULL,
       time_out DATETIME NULL,
       FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   );

   -- Create tasks table
   CREATE TABLE tasks (
       id INT AUTO_INCREMENT PRIMARY KEY,
       user_id INT NOT NULL,
       task_name VARCHAR(255) NOT NULL,
       task_description TEXT,
       due_at DATETIME NOT NULL,
       status ENUM('pending', 'in_progress', 'completed', 'due', 'archive', 'archived') DEFAULT 'pending',
       FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   );
   ```

   - If successful, you'll see the tables listed under the `ietracker` database.
   - Note: The app sets the MySQL timezone to '+08:00' (Asia/Manila) in the database connection file. If you need a different timezone, adjust `database/ietracker_database.php` accordingly.

### Step 4: Run the Website
1. Ensure Apache and MySQL are still running in XAMPP.
2. Open your web browser and navigate to:  
   `http://localhost/iEtracker/views/authenticator/register.php`
   - This loads the registration page first (as per your original link).
3. Register a new user account to test:
   - Fill in full name, email, job title, password (min. 8 characters), and confirm password.
   - Submit the form.
4. After registration, log in at `http://localhost/iEtracker/views/authenticator/login.php`.
5. You should be redirected to the dashboard at `http://localhost/iEtracker/index.php`.

### Troubleshooting
- **Port Conflicts**: If Apache/MySQL won't start, check for conflicts. Change ports in XAMPP config if needed.
- **Database Errors**: Ensure the database name is exactly `ietracker` and tables are created. Check phpMyAdmin for errors.
- **PHP Errors**: Verify PHP 8+ is active in XAMPP. Check `C:\xampp\php\php.ini` for any disabled extensions (e.g., ensure `pdo_mysql` is enabled).
- **File Permissions**: If issues occur, run XAMPP as Administrator.
- **Timezone Issues**: The app uses Asia/Manila time. If your local time differs, update the `SET time_zone` line in `database/ietracker_database.php`.
- **No Data Showing**: After setup, add sample data via the UI or manually in phpMyAdmin.

