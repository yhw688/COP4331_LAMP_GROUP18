#LAMP Stack Project

## API Setup

### Database Connection

`/api/config/db.php` centralizes the database connection used by the API.

* Database credentials are loaded from a `.env` file so sensitive information is not hardcoded into the PHP source code or stored on GitHub.
* The file creates and returns a PDO connection to the MySQL database.
* PDO is configured to throw exceptions when database errors occur.
* If the database connection fails, the API returns HTTP `500 Internal Server Error`.

### Registration API

`/api/register.php` handles registration of new regular user accounts.

* `POST /api/register.php` accepts the user's first name, last name, login, and password as JSON.
* The endpoint first checks whether the submitted login already exists in the `Users` table.
* If the login is available, the password is hashed using PHP's `password_hash()` function.
* The new account is created with the `User` role and is enabled by default.
* A successful registration returns HTTP `201 Created` along with the newly generated user ID.
* A duplicate login returns HTTP `409 Conflict`.

### Login API

`/api/login.php` authenticates existing users and establishes their session.

* `POST /api/login.php` accepts a login and password as JSON.
* The endpoint retrieves the matching user from the `Users` table using a parameterized `SELECT` statement.
* The submitted password is verified against the stored password hash using `password_verify()`.
* Disabled accounts are prevented from logging in.
* After successful authentication, the user's ID and role are stored in the PHP session for use by protected API endpoints.
* A successful login returns HTTP `200 OK` with the user record, while invalid credentials return HTTP `401 Unauthorized`.

### Admin User Management API

`/api/admin/users.php` provides administrator-only user management functionality. Access is restricted to authenticated users with the `Admin` role.

* `GET /api/admin/users.php?q=term` searches and lists user accounts by first name, last name, login, or role.
* `POST /api/admin/users.php` creates a new Admin account. Passwords are hashed before being stored in the database.
* `PUT /api/admin/users.php?id={userId}` modifies an existing user account. The request body specifies an action:

  * `disable` — disables the account without deleting it.
  * `enable` — re-enables a disabled account.
  * `changePassword` — replaces the user's password with a newly generated password hash.

All database operations use PDO prepared statements, and user passwords are never returned in API responses.

### Admin Contacts API

`/api/admin/contacts.php` provides administrator-only access to contact records across all users. Access is restricted to authenticated users with the `Admin` role.

* `GET /api/admin/contacts.php?q=term` searches contact records by first name, last name, phone number, email address, or the login of the user who owns the contact.
* The endpoint joins the `Contacts` and `Users` tables using `Contacts.UserID = Users.ID` so the response includes both the contact information and the owner’s account information.
* Search results are limited to 50 records to avoid loading the entire contacts table at once.
* The endpoint returns HTTP `200 OK` with matching contacts in JSON format.
* Normal users do not use this endpoint; their contact queries are handled by `/api/contacts.php`, which restricts results to the authenticated user’s own contacts.

All database operations use PDO prepared statements.

### Authentication Helper

`/api/config/auth.php` contains reusable authentication and authorization functions used by protected API endpoints.

* `requireLogin()` verifies that a valid user session exists, checks that the user still exists in the `Users` table, and confirms that the account is enabled.
* If the account is disabled or no longer exists, the session is cleared and access is denied.
* `requireAdmin()` first performs the normal login check and then verifies that the authenticated user has the `Admin` role.
* Unauthorized users receive HTTP `401 Unauthorized`, while authenticated users without the required permissions receive HTTP `403 Forbidden`.
* The helper allows protected endpoints to consistently verify the current user without duplicating authentication logic.

### Contacts API

`/api/contacts.php` provides CRUD functionality for contact records belonging to the currently authenticated user.

* `GET /api/contacts.php?q=term` searches the authenticated user's contacts by first name, last name, phone number, or email address.
* `GET /api/contacts.php?id={contactId}` retrieves a specific contact only if it belongs to the authenticated user.
* `POST /api/contacts.php` creates a new contact and automatically associates it with the authenticated user's `UserID`.
* `PUT /api/contacts.php?id={contactId}` updates a contact's first name, last name, phone number, and email address. The contact ID and owner `UserID` are not modified.
* `DELETE /api/contacts.php?id={contactId}` deletes a contact only if it belongs to the authenticated user.
* All contact queries use the authenticated user's `UserID` to prevent users from accessing, modifying, or deleting another user's contacts.
* Successful create operations return HTTP `201 Created`, while successful retrieval, update, and delete operations return HTTP `200 OK`.


## Database Seed

`/database/seed.sql` initializes the database with the required default administrator account.

* The seeded account uses the login `root` with the name `Application Administrator`.
* The account is created with the `Admin` role and is enabled by default.
* The administrator password is stored as a PHP-generated password hash rather than plaintext.
* The seed script checks whether the `root` account already exists before inserting it, preventing duplicate administrator accounts if the script is executed more than once.
* The default password should be changed after the initial login.

Before adding the password hash to `seed.sql`, generate it directly on the droplet using PHP:

```bash
php -r "echo password_hash('Admin123!', PASSWORD_DEFAULT), PHP_EOL;"
```

The command outputs a password hash similar to:

```text
$2y$12$...
```

The exact hash will be different each time because `password_hash()` generates a new random salt. Copy the complete generated hash into the `Password` field in `seed.sql`:

```sql
INSERT INTO Users
(
    FirstName,
    LastName,
    Login,
    Password,
    DateCreated,
    DateUpdated,
    Role,
    IsEnabled
)
SELECT
    'Application',
    'Administrator',
    'root',
    'PASTE_GENERATED_PASSWORD_HASH_HERE',
    NOW(),
    NOW(),
    'Admin',
    1
WHERE NOT EXISTS
(
    SELECT 1
    FROM Users
    WHERE Login = 'root'
);
```

The seed script can then be executed on the droplet with:

```bash
mysql -u ContactManagerUser -p ContactManagerDB < database/seed.sql
```