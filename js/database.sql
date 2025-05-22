-- Create the database
CREATE DATABASE smarmdb;
USE smarmdb;

-- Table: users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Table: team_list (master list of departments)
CREATE TABLE team_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(100) NOT NULL UNIQUE
);

-- Table: teams (associates users with departments)
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (department) REFERENCES team_list(department)
);

-- Table: schedulers
CREATE TABLE schedulers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    scheduler_name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table: scheduler_requests
CREATE TABLE scheduler_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_number VARCHAR(50) NOT NULL,
    scheduler_name VARCHAR(100) NOT NULL,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table: approved_requests
CREATE TABLE approved_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_number VARCHAR(50) NOT NULL,
    scheduler_name VARCHAR(100) NOT NULL,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);