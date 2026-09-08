-- 1. Identity & Profiles
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client', 'engineer', 'admin') DEFAULT 'client',
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
    -- NEW: 'in_progress' added after 'deployed'
    status ENUM('pending', 'acknowledged', 'deployed', 'in_progress', 'resolved', 'cancelled') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES service_locations(location_id) ON DELETE RESTRICT
);

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
    updater_id INT NOT NULL, -- Tracks which admin or engineer posted the update
    status_milestone VARCHAR(100) NOT NULL, -- e.g., 'Team Deployed', 'Hull Inspected', 'Awaiting Parts'
    detailed_message TEXT NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES dispatch_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (updater_id) REFERENCES users(user_id) ON DELETE RESTRICT
);