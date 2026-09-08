-- 1. Identity & Profiles
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client', 'engineer', 'admin') DEFAULT 'client',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE engineer_profiles (
    engineer_id INT PRIMARY KEY,
    specialty VARCHAR(100) NOT NULL,
    current_status ENUM('available', 'deployed', 'on_leave') DEFAULT 'available',
    FOREIGN KEY (engineer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 2. Infrastructure & Locations
CREATE TABLE operational_hubs (
    hub_id INT AUTO_INCREMENT PRIMARY KEY,
    hub_name VARCHAR(100) NOT NULL,
    hub_type VARCHAR(50) DEFAULT 'Standard' -- e.g., 'Rapid Deployment' for APAC
);

CREATE TABLE service_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    hub_id INT NOT NULL,
    port_name VARCHAR(100) NOT NULL, -- e.g., 'Singapore', 'Rotterdam'
    FOREIGN KEY (hub_id) REFERENCES operational_hubs(hub_id) ON DELETE RESTRICT
);

-- 3. Core Transactions (UPDATED)
CREATE TABLE dispatch_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    vessel_name VARCHAR(100) NOT NULL,
    imo_number VARCHAR(50) NOT NULL,
    vessel_type VARCHAR(100) NOT NULL,
    company VARCHAR(100) NULL,
    contact_person VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(50) NOT NULL,
    eta_date DATE NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    is_urgent TINYINT(1) DEFAULT 0,
    location_id INT NOT NULL,
    description TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    status ENUM('pending', 'acknowledged', 'deployed', 'in_progress', 'resolved', 'cancelled') DEFAULT 'pending',
    is_active TINYINT(1) DEFAULT 1, -- NEW: Soft delete for historical records
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES service_locations(location_id) ON DELETE RESTRICT
);

-- NEW: Indexes for high-speed Admin Dashboard filtering
CREATE INDEX idx_dispatch_status ON dispatch_requests(status);
CREATE INDEX idx_dispatch_urgency ON dispatch_requests(is_urgent);

-- 4. Operations & Deployments
CREATE TABLE deployments (
    deployment_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    admin_id INT NOT NULL, 
    engineer_id INT NOT NULL,
    deployment_status ENUM('en_route', 'on_site', 'completed') DEFAULT 'en_route',
    deployed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES dispatch_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (engineer_id) REFERENCES engineer_profiles(engineer_id) ON DELETE RESTRICT
);

CREATE TABLE operation_updates (
    update_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    updater_id INT NOT NULL, 
    status_milestone VARCHAR(100) NOT NULL,
    detailed_message TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL, -- NEW: Engineer proof of completion
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES dispatch_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (updater_id) REFERENCES users(user_id) ON DELETE RESTRICT
);



INSERT INTO operational_hubs (hub_id, hub_name, hub_type) VALUES 
(1, 'APAC Hub', 'Rapid Deployment'),
(2, 'EMEA Hub', 'Standard'),
(3, 'Americas Hub', 'Standard');

INSERT INTO service_locations (location_id, hub_id, port_name) VALUES 
(1, 1, 'Singapore'),
(2, 1, 'Shanghai, China'),
(3, 2, 'Rotterdam, Netherlands'),
(4, 2, 'Dubai, UAE'),
(5, 2, 'Cape Town, South Africa'),
(6, 3, 'Houston, USA'),
(7, 3, 'Panama City, Panama');