# Database Setup Instructions for Erasky Trade Solutions

To get the backend database up and running with the legacy data, please follow these steps:

## 1. Create the Database
Create a MySQL/MariaDB database locally or on your server.
```sql
CREATE DATABASE `u597814446_ex_worlds_in` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 2. Configure Environment Variables
Update the database connection settings in your `.env` file under `new_website/backend/`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u597814446_ex_worlds_in
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 3. Import the SQL Dump (Legacy Data)
Use the terminal to import the SQL file. Make sure you run this command from the root directory of your project:
```bash
mysql -u your_username -p u597814446_ex_worlds_in < u597814446_ex_worlds_in.sql
```
*(If you are running local XAMPP, the command is usually: `C:\xampp\mysql\bin\mysql.exe -u root u597814446_ex_worlds_in < u597814446_ex_worlds_in.sql`)*

## 4. Run Laravel Migrations
After the legacy data is imported, run the Laravel migrations to create system tables like `personal_access_tokens`, `sessions`, `cache`, and `jobs`:
```bash
cd new_website/backend
php artisan migrate
```
*Note: We have protected the default `users` migration file to check if the table already exists, ensuring it does not overwrite or fail on your legacy `users` table.*
