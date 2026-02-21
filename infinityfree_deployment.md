# How to Deploy  on InfinityFree

This guide provides step-by-step instructions on how to take your  project from a local XAMPP environment and make it live on InfinityFree hosting.

---

## Step 1: Export Your Local Database
Before uploading, you need a copy of your local database.
1. Open XAMPP and make sure **MySQL** and **Apache** are running.
2. Go to `http://localhost/phpmyadmin`.
3. Select your database (`stocksathi`) from the left sidebar.
4. Click on the **Export** tab at the top.
5. Leave the format as **SQL** and click **Export**. This will download a file named `stocksathi.sql` to your computer.

---

## Step 2: Set Up InfinityFree Database
Now, you need to create a database on the live server.
1. Log in to your [InfinityFree Dashboard](https://app.infinityfree.net/).
2. Select your hosting account and click **Control Panel**.
3. Scroll down to the **Databases** section and click on **MySQL Databases**.
4. In the "Create New Database" section, enter a name (e.g., `stocksathi`) and click **Create Database**.
5. Once created, carefully copy the following details from this page; you will need them shortly:
   - **MySQL Host Name** (e.g., `sql101.infinityfree.com`)
   - **MySQL User Name** (e.g., `if0_41207029`)
   - **MySQL Password** (This is your Control Panel/vPanel password, which you can find in your account details)
   - **MySQL Database Name** (e.g., `if0_41207029_stocksathi`)

---

## Step 3: Import Your Database to InfinityFree
1. Go back to the InfinityFree **Control Panel**.
2. In the **Databases** section, click on **phpMyAdmin**.
3. Click "Enter phpMyAdmin" next to the database you just created.
4. Click on the **Import** tab at the top.
5. Click **Choose File** and select the `stocksathi.sql` file you downloaded in Step 1.
6. Scroll down and click **Go** (or **Import**). Wait for the success message.

---

## Step 4: Update Your Application Configuration
Before uploading your code, you MUST update your local files with the new live database credentials you copied in Step 2.

**Update these 3 specific files (DO NOT UPDATE INSTALLER.php):**

### 1. `_includes/login/config.php`
Change lines 3-6:
```php
define('DB_HOST', 'sql101.infinityfree.com'); // Your live host
define('DB_USER', 'if0_41207029');            // Your live user
define('DB_PASS', 'Ashu2295');                // Your live password
define('DB_NAME', 'if0_41207029_stocksathi'); // Your live DB name
```

### 2. `_includes/Database.php`
Change lines 11-14:
```php
private $host = 'sql101.infinityfree.com';
private $dbname = 'if0_41207029_stocksathi';
private $username = 'if0_41207029';
private $password = 'Ashu2295';
```

### 3. `_includes/auth.php`
Change lines 34-37:
```php
$host = 'sql101.infinityfree.com';
$dbname = 'if0_41207029_stocksathi';
$username = 'if0_41207029';
$password = 'Ashu2295';
```

---

## Step 5: Upload Your Files
1. In the InfinityFree **Control Panel**, scroll to the **Files** section and click **Online File Manager** (or use an FTP client like FileZilla).
2. Open the `htdocs` folder. **(Crucial: delete the default `index2.html` file that is already there).**
3. Select ALL files and folders inside your local `c:\xampp_new\htdocs\stocksathi\` folder.
4. Drag and drop them directly into the remote `htdocs` folder on InfinityFree. Wait for the upload to complete.

*(Note: InfinityFree has an inode/file limit. Ensure you are uploading only necessary project files and not huge vendor/node_modules folders unless required).*

---

## Step 6: Verify Your Live Site
1. Open your browser.
2. Visit your live InfinityFree domain (e.g., `http://yourdomain.epizy.com`).
3. You should see your Stocksathi login page. Try logging in to ensure the database connection is working perfectly.

**Done! Your website is now live!**
