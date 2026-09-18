#LAMP Stack Project

##API Setup

db.php centralizes the database connection. It loads database credentials from a .env file so sensitive information is not hardcoded into the PHP source code or stored on GitHub. It then creates a PDO connection to the MySQL database.

The registration endpoint accepts the user's first name, last name, login, and password as JSON. It first checks whether the login already exists. If the login is available, the password is hashed using PHP's password_hash() function and the new user is inserted into the database using a parameterized SQL statement. A successful registration returns an HTTP 201 Created response along with the newly generated user ID.

The login endpoint accepts a login and password as JSON. It queries the Users table using a parameterized SELECT statement and verifies the submitted password against the stored password hash using password_verify(). A successful login returns HTTP 200 OK with the user record, while invalid credentials return HTTP 401 Unauthorized.