# How Well Do You Know Ali? 🤔
### A Full-Stack Personal Friendship Quiz Web Application
**Built for Ali Sultan • Production-Ready for InfinityFree Free Shared Hosting (PHP 8+ & MySQL/MariaDB)**

---

## 1. Project Overview
This is a modern, mobile-first, entertainment-focused web application where friends can answer questions about **Ali Sultan** and receive an immediate, server-calculated score and personalized feedback.

- **Frontend:** Pure semantic HTML5, modern bespoke CSS3 (mobile-first, responsive, subtle AI/technology aesthetic), and vanilla JavaScript.
- **Backend:** PHP 8+ with PDO prepared statements and secure session management.
- **Database:** MySQL / MariaDB relational database with InnoDB foreign keys and cascaded deletion.
- **Zero Paid Dependencies:** No Firebase, No Supabase, No Node.js server required in production. 100% compatible with free shared hosting like InfinityFree.

---

## 2. Public User Experience & Features
1. **Landing Page (`index.php`):**
   - Welcoming, tech-inspired interface with animated glow accents.
   - Title: *How Well Do You Know Ali? 🤔*
   - Subtitle: *“Think you know me? Let's find out!”*
   - Asks for Friend's Name (required) and optional Nickname/Insider Joke.
   - Smooth entrance animations and mobile-optimized button targets.

2. **Interactive Quiz (`quiz.php`):**
   - Dynamically loaded questions from the MySQL database.
   - **Bulletproof Privacy & Security:** Correct answers are strictly evaluated on the server and are **NEVER** sent to the client browser's HTML or JavaScript.
   - Question counter (e.g. *Question 1 of 10*) and animated percentage progress bar.
   - 4 selectable option cards with active visual indicator.
   - Previous & Next navigation with state preservation (prevents accidental loss of answers).
   - Validation prevents skipping unanswered questions.
   - Final confirmation modal before submission.

3. **Server-Side Scoring (`submit.php`):**
   - Answers submitted via POST.
   - PHP retrieves the authentic correct answers from MySQL.
   - Compares answers, computes the score, percentage, and saves the response into `quiz_responses` and `quiz_answers`.

4. **Results & Sharing (`result.php`):**
   - Dynamic score presentation (e.g. *8 / 10 • 80%*).
   - Animated visual score circle and personalized score feedback tier:
     - **90–100%:** *“Wow! You really know Ali! 🏆”*
     - **70–89%:** *“Pretty impressive! You know me well! 🔥”*
     - **50–69%:** *“Not bad… but you still have some homework to do 😄”*
     - **30–49%:** *“You know a little about me… time to investigate! 😂”*
     - **0–29%:** *“Okay… do we even know each other? 😂”*
   - **One-Click Actions:**
     - *Share on WhatsApp:* Opens WhatsApp with a pre-filled invitation message.
     - *Copy Quiz Link:* Copies URL with instant feedback toast (*“Link copied! 📋”*).
     - *Challenge your friends:* Restarts the quiz for another friend.

---

## 3. Admin Dashboard (`/admin/`)
- **Initial Setup Wizard (`admin/setup.php`):**
  - When first installed, visiting `/admin/setup.php` allows Ali to create his own secure username, email, and password.
  - As soon as the first admin account is registered, `setup.php` permanently locks itself against any further registrations.
- **Protected Authentication (`admin/login.php`):**
  - Session-based authentication with `password_hash()` and `password_verify()`.
  - Session fixation protection with `session_regenerate_id(true)`.
  - Automatic CSRF protection on all form submissions.
- **Dashboard Overview (`admin/index.php`):**
  - Statistics cards: Total Quizzes Completed, Average Score %, Highest Score %, Lowest Score %, and Active Questions Count.
  - Recent 10 submissions with instant links to inspect responses.
- **Responses Audit (`admin/responses.php`):**
  - Table of all submissions: Friend Name, Score, Percentage, Submission Date.
  - Search filter by friend name.
  - Sorting by newest, oldest, highest score, and lowest score.
  - Delete submission capability with CSRF protection.
- **Detailed Response View (`admin/response-view.php`):**
  - Displays every question asked.
  - Displays what the friend selected.
  - Displays what the true correct answer was.
  - Highlights correct (green) vs. incorrect (red) with exact timestamps.
- **Question Management (`admin/questions.php`, `add-question.php`, `edit-question.php`):**
  - Add, edit, reorder, or toggle active/disabled status for any question.
  - Set the correct answer option (A, B, C, or D).
- **Settings & Export (`admin/settings.php`):**
  - Change admin password securely.
  - Download the complete InfinityFree production deployment ZIP.

---

## 4. Database Schema (`database.sql`)
The MySQL schema uses relational tables with foreign keys:

1. **`questions`:**
   - `id` (INT AUTO_INCREMENT PRIMARY KEY)
   - `question_text` (TEXT NOT NULL)
   - `option_a` (VARCHAR(255) NOT NULL)
   - `option_b` (VARCHAR(255) NOT NULL)
   - `option_c` (VARCHAR(255) NOT NULL)
   - `option_d` (VARCHAR(255) NOT NULL)
   - `correct_answer` (ENUM('A','B','C','D') NOT NULL)
   - `is_active` (TINYINT(1) DEFAULT 1)
   - `order_num` (INT DEFAULT 0)
   - `created_at`, `updated_at` (TIMESTAMP)

2. **`quiz_responses`:**
   - `id` (INT AUTO_INCREMENT PRIMARY KEY)
   - `friend_name` (VARCHAR(100) NOT NULL)
   - `score` (INT NOT NULL)
   - `total_questions` (INT NOT NULL)
   - `percentage` (DECIMAL(5,2) NOT NULL)
   - `submitted_at` (TIMESTAMP)

3. **`quiz_answers`:**
   - `id` (INT AUTO_INCREMENT PRIMARY KEY)
   - `response_id` (INT NOT NULL, FOREIGN KEY -> `quiz_responses(id)` ON DELETE CASCADE)
   - `question_id` (INT NOT NULL, FOREIGN KEY -> `questions(id)` ON DELETE CASCADE)
   - `selected_answer` (ENUM('A','B','C','D') NOT NULL)
   - `is_correct` (TINYINT(1) NOT NULL)

4. **`admin_users`:**
   - `id` (INT AUTO_INCREMENT PRIMARY KEY)
   - `username` (VARCHAR(50) UNIQUE NOT NULL)
   - `email` (VARCHAR(100) UNIQUE NOT NULL)
   - `password_hash` (VARCHAR(255) NOT NULL)
   - `created_at` (TIMESTAMP)

---

## 5. InfinityFree Deployment Step-by-Step Guide

### Step 1: Create an Account & Domain on InfinityFree
1. Go to [https://www.infinityfree.com](https://www.infinityfree.com) and log in.
2. In the Client Area, click **Create Account**.
3. Choose your free subdomain (e.g. `aliquiz.epizy.com` or `knowali.infinityfreeapp.com`) or attach your custom domain.

### Step 2: Create a MySQL Database
1. In your InfinityFree Client Area, click on your account and open the **Control Panel (VistaPanel)**.
2. Scroll to the **Databases** section and click **MySQL Databases**.
3. In "Create New Database", enter a name such as `aliquiz` and click **Create Database**.
4. Take note of the connection credentials displayed on the page:
   - **MySQL Hostname:** e.g. `sql304.infinityfree.com`
   - **MySQL Database Name:** e.g. `if0_12345678_aliquiz`
   - **MySQL Username:** e.g. `if0_12345678`
   - **MySQL Password:** Your vPanel / Account password

### Step 3: Import the SQL Schema
1. Back in VistaPanel under Databases, click **phpMyAdmin**.
2. Click **Connect** next to your newly created database.
3. In the top navigation bar of phpMyAdmin, click the **Import** tab.
4. Click **Choose File** and select `database.sql` from your project folder.
5. Click **Import** (or **Go**). All 4 tables, 10 sample questions, and default admin user will be created.

### Step 4: Configure Database Credentials
1. Open the file `config/database.example.php`.
2. Save or rename a copy as `config/database.php`.
3. Fill in your InfinityFree MySQL values:
   ```php
   define('DB_HOST', 'sql304.infinityfree.com'); // Your MySQL hostname
   define('DB_NAME', 'if0_12345678_aliquiz');    // Your Database name
   define('DB_USER', 'if0_12345678');           // Your MySQL username
   define('DB_PASSWORD', 'YourAccountPassword'); // Your password
   define('DB_PORT', '3306');
   define('DB_DRIVER', 'mysql');
   ```

### Step 5: Upload Files to InfinityFree
You can upload the files using either **Monsta File Manager** in the browser or **FileZilla (FTP)**:
1. Open the **`htdocs/`** directory of your domain (do not upload outside of `htdocs`).
2. Upload the project files and folders so the structure looks like:
   ```text
   htdocs/
   ├── index.php
   ├── quiz.php
   ├── submit.php
   ├── result.php
   ├── config/
   │   ├── database.php
   │   └── database.example.php
   ├── admin/
   │   ├── index.php
   │   ├── auth.php
   │   ├── login.php
   │   ├── logout.php
   │   ├── questions.php
   │   ├── add-question.php
   │   ├── edit-question.php
   │   ├── delete-question.php
   │   ├── responses.php
   │   ├── response-view.php
   │   └── settings.php
   ├── assets/
   │   ├── css/style.css
   │   └── js/app.js
   └── database.sql
   ```

### Step 6: Test Public Quiz & Admin
1. Visit your website: `http://your-domain.infinityfreeapp.com/`.
2. Enter a test friend name, take the quiz, and verify score calculation.
3. Visit `http://your-domain.infinityfreeapp.com/admin/setup.php`.
4. Create your administrator account with your chosen password.
5. Log into the dashboard, review quiz responses, and customize questions.

---

## 6. Communication Flow (Frontend ↔ PHP ↔ MySQL)
1. **Quiz Initialization:**
   `Browser (index.php)` → sends friend name → `quiz.php` queries MySQL `SELECT id, question_text, option_a, ... FROM questions WHERE is_active = 1`. Notice `correct_answer` is strictly omitted from the query.
2. **Interactive Answer Recording:**
   Client JavaScript in `app.js` tracks selected answers in local state without submitting anything to external APIs.
3. **Submission & Verification:**
   User clicks Submit → POST sent to `submit.php` with CSRF token and `answers` map.
4. **Server-Side Evaluation:**
   `submit.php` queries MySQL for the real `correct_answer` values. In a database transaction, it records the total score in `quiz_responses` and every individual answer choice in `quiz_answers`.
5. **Private Results:**
   Server issues a 302 redirect to `result.php?id=<response_id>`, which queries only that specific friend's response and displays their score.
6. **Admin Review:**
   Admin logs in via `admin/login.php` → `admin/responses.php` queries `quiz_responses` → clicking a friend runs a JOIN on `quiz_answers` and `questions` to display exactly which answers were right or wrong.

---

## 7. How to Add or Edit Questions
- **Via Admin Dashboard:**
  Log into `/admin/` and click **Questions**. Click **➕ Add New Question** to write the question, 4 options, and pick which option (A, B, C, or D) is the correct one.
- **Via phpMyAdmin (SQL):**
  You can run direct SQL insert statements on the `questions` table:
  ```sql
  INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer, order_num, is_active)
  VALUES ('What is Ali\'s favorite video game?', 'Cyberpunk 2077', 'Minecraft', 'FIFA', 'Valorant', 'A', 11, 1);
  ```

---

## 8. How to Customize Branding & Name
To personalize the application for a different name:
1. In `config/database.php`, change:
   ```php
   define('APP_NAME', 'How Well Do You Know Ali?');
   define('OWNER_NAME', 'Ali Sultan');
   ```
2. In `index.php` and `result.php`, update any personalized headlines or titles.

---

## 9. Security Checklist
- [x] **No Plaintext Passwords:** Admin password hashed with `password_hash()` and verified via `password_verify()`.
- [x] **SQL Injection Prevention:** 100% of queries use PDO prepared statements with parameter binding.
- [x] **XSS Protection:** All user-supplied inputs and outputs run through `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- [x] **CSRF Protection:** Anti-CSRF session tokens on all forms (quiz submission, login, question management, delete).
- [x] **Anti-Cheat Design:** Correct answers are NEVER exposed in browser JavaScript or HTML source code.
- [x] **Database Isolation:** Database credentials reside in `config/database.php`, completely separate from client assets.
- [x] **Cascading Integrity:** Foreign keys ensure that deleting a question or response cleans up all associated answers automatically.
