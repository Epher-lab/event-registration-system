-- Event System Database Schema
CREATE DATABASE IF NOT EXISTS event_system;
USE event_system;

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    venue VARCHAR(200),
    event_date DATETIME NOT NULL,
    end_date DATETIME,
    image_url VARCHAR(500),
    status ENUM('active','cancelled','completed') DEFAULT 'active',
    organizer_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ticket_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity_total INT NOT NULL,
    quantity_sold INT NOT NULL DEFAULT 0,
    sale_start DATETIME,
    sale_end DATETIME,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attendees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255),
    role ENUM('attendee','organizer','admin') DEFAULT 'attendee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendee_id INT NOT NULL,
    event_id INT NOT NULL,
    ticket_type_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    booking_ref VARCHAR(20) UNIQUE,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attendee_id) REFERENCES attendees(id),
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id)
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('card','mpesa','paypal') DEFAULT 'card',
    payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    transaction_ref VARCHAR(100),
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (registration_id) REFERENCES registrations(id)
);

-- Sessions table for custom session handling
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    attendee_id INT,
    cart_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert demo data
INSERT INTO attendees (first_name, last_name, email, phone, password_hash, role) VALUES
('Admin', 'User', 'admin@eventsys.com', '+254700000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Jane', 'Organizer', 'organizer@eventsys.com', '+254700000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer'),
('John', 'Doe', 'john@example.com', '+254700000003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'attendee');

INSERT INTO events (title, description, venue, event_date, end_date, status, organizer_id) VALUES
('TechConf Nairobi 2025', 'The biggest tech conference in East Africa. Join industry leaders, innovators and developers for 2 days of keynotes, workshops and networking.', 'KICC, Nairobi', '2025-09-15 08:00:00', '2025-09-16 18:00:00', 'active', 2),
('AfroBeats Night Live', 'An electric night of live Afrobeats performances featuring top artists from across the continent.', 'Carnivore Grounds, Nairobi', '2025-08-20 19:00:00', '2025-08-21 02:00:00', 'active', 2),
('Startup Pitch Finale', 'Watch 20 African startups compete for $500,000 in funding. Network with VCs and angel investors.', 'Radisson Blu, Nairobi', '2025-07-10 09:00:00', '2025-07-10 17:00:00', 'active', 2),
('Wellness & Yoga Retreat', 'A full-day outdoor wellness retreat featuring yoga, meditation, healthy food and mindfulness workshops.', 'Karura Forest, Nairobi', '2025-07-25 07:00:00', '2025-07-25 16:00:00', 'active', 2);

INSERT INTO ticket_types (event_id, name, description, price, quantity_total, quantity_sold) VALUES
(1, 'Early Bird', 'Limited early bird access — full 2-day pass', 2500.00, 100, 45),
(1, 'General Admission', 'Standard 2-day conference pass', 4500.00, 300, 120),
(1, 'VIP', 'VIP pass with front seating, networking dinner & gift bag', 12000.00, 50, 18),
(2, 'Regular', 'General entry ticket', 1500.00, 500, 220),
(2, 'VIP Table (6 pax)', 'Reserved table for 6 with bottle service', 25000.00, 30, 8),
(3, 'Investor Pass', 'Access to all pitches + investor lounge', 5000.00, 100, 60),
(3, 'General', 'Standard entry', 1000.00, 400, 150),
(4, 'Standard', 'Full day wellness pass', 3500.00, 80, 35),
(4, 'Couple', 'Wellness pass for 2', 6000.00, 40, 12);
