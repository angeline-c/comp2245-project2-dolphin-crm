CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  firstname VARCHAR(255) NOT NULL,
  lastname VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(50),
  firstname VARCHAR(255) NOT NULL,
  lastname VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  telephone VARCHAR(50),
  company VARCHAR(255),
  type VARCHAR(50),
  assigned_to INT,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (assigned_to) REFERENCES users(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contact_id INT NOT NULL,
  comment TEXT NOT NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (contact_id) REFERENCES contacts(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

INSERT INTO users (firstname, lastname, email, password, role)
VALUES ('Admin', 'User', 'admin@project2.com', '$2y$10$PkXnLg3PEFTFJm/6tZsXmeCnyw5LCuFKlZmPD3TvpZiysdAKJp6S2', 'admin');
