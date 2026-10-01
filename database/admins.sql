-- For new MySQL/MariaDB installations. Existing tables are not modified.
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NOT NULL,
    birthdate DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Prefer not to say') NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    contact_number VARCHAR(11) NOT NULL,
    address VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
