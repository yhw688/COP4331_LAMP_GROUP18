-- Seed default administrator account if it does not already exist

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
    '$2y$12$wGmh73Y/4Ai60M/nHqzxZuSkb/QyFYYp0zNw2e3GNAsK9PnWLohAK',
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