-- =============================================
-- GreenGrocer Database Setup
-- Run this SQL in your InfinityFree phpMyAdmin
-- =============================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock DECIMAL(10,3) NOT NULL DEFAULT 0,
    unit_type ENUM('kg','unit') NOT NULL DEFAULT 'unit',
    category VARCHAR(100),
    image VARCHAR(255) DEFAULT 'default.jpg',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS delivery_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_label VARCHAR(50) NOT NULL,
    max_orders INT DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','delivered','cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid','paid') DEFAULT 'unpaid',
    delivery_date DATE NOT NULL,
    slot_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (slot_id) REFERENCES delivery_slots(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    price_at_purchase DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default delivery slots
INSERT INTO delivery_slots (slot_label, max_orders) VALUES
('9:00 AM - 12:00 PM', 10),
('12:00 PM - 3:00 PM', 10),
('3:00 PM - 6:00 PM', 10),
('6:00 PM - 9:00 PM', 8);

-- Default admin account (password: Admin@123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@greengrocer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample products
INSERT INTO products (name, description, price, stock, unit_type, category, image) VALUES
('Organic Tomatoes', 'Fresh locally grown organic tomatoes, rich in flavor and nutrients.', 2500, 50.000, 'kg', 'Vegetables', 'tomatoes.jpg'),
('Free-Range Eggs', 'Farm fresh free-range eggs from happy hens.', 3500, 200.000, 'unit', 'Dairy & Eggs', 'eggs.jpg'),
('Organic Bananas', 'Sweet Fairtrade organic bananas.', 1800, 30.000, 'kg', 'Fruits', 'bananas.jpg'),
('Eco Dish Soap', 'Biodegradable plant-based dish soap, 500ml bottle.', 4500, 75.000, 'unit', 'Eco Products', 'soap.jpg'),
('Organic Spinach', 'Baby spinach leaves, pesticide-free and nutrient-rich.', 3200, 20.000, 'kg', 'Vegetables', 'spinach.jpg'),
('Brown Rice', 'Whole grain organic brown rice, 1kg pack.', 2800, 100.000, 'unit', 'Grains', 'rice.jpg'),
('Organic Apples', 'Crisp Gala apples from certified organic orchards.', 2200, 40.000, 'kg', 'Fruits', 'apples.jpg'),
('Reusable Beeswax Wraps', 'Set of 3 eco-friendly food wraps, replaces plastic wrap.', 8500, 50.000, 'unit', 'Eco Products', 'beeswax.jpg');
