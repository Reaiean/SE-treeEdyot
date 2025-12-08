CREATE DATABASE drainage_system;

USE drainage_system;

CREATE TABLE roles (
    roleID INT AUTO_INCREMENT PRIMARY KEY,
    roleName VARCHAR(50) NOT NULL
);

CREATE TABLE users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    roleID INT NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    firstName VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    contactNumber VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    dateRegistered DATE NOT NULL,
    FOREIGN KEY (roleID) REFERENCES roles(roleID)
	ON DELETE RESTRICT ON UPDATE CASCADE
);


CREATE TABLE REPORTS (
  id INT AUTO_INCREMENT PRIMARY KEY,
  userId INT NOT NULL,
  reportType VARCHAR(100),
  description TEXT,
  status VARCHAR(50) DEFAULT 'Pending',
  severity VARCHAR(50),
  location VARCHAR(255),
  latitude DECIMAL(10,6),
  longitude DECIMAL(10,6),
  photoPath VARCHAR(255),
  dateFiled DATE,
  dateResolved DATE NULL,
  FOREIGN KEY (userId) REFERENCES users(userID)
  ON UPDATE CASCADE ON DELETE CASCADE
);





INSERT INTO roles (roleName) VALUES 
('Admin'),
('Resident'),
('Maintenance Staff');

SELECT * FROM users;
SELECT * FROM reports;
SELECT * FROM roles;











