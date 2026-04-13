DROP TABLE IF EXISTS PURCHASE_ITEM;
DROP TABLE IF EXISTS PURCHASE;
DROP TABLE IF EXISTS STOCK_RECEPTION;
DROP TABLE IF EXISTS CUSTOMER;
DROP TABLE IF EXISTS PRODUCT;

DROP TABLE IF EXISTS purchase_item;
DROP TABLE IF EXISTS purchase;
DROP TABLE IF EXISTS stock_reception;
DROP TABLE IF EXISTS customer;
DROP TABLE IF EXISTS product;

CREATE TABLE product (
    id             INT            PRIMARY KEY,
    name           VARCHAR(100)   NOT NULL,
    category       VARCHAR(50),
    price          DECIMAL(10,2)  NOT NULL,
    upc            VARCHAR(13),
    epc            VARCHAR(24),
    manufacturer   VARCHAR(100),
    shelf_life_days INT
);

CREATE TABLE stock_reception (
    id                 INT           PRIMARY KEY,
    product_id         INT           NOT NULL,
    quantity_received  INT           NOT NULL,
    date_received      DATE          NOT NULL,
    current_stock      INT           NOT NULL,
    FOREIGN KEY (product_id) REFERENCES product(id)
);

CREATE TABLE customer (
    id                  INT           PRIMARY KEY,
    name                VARCHAR(100)  NOT NULL,
    email               VARCHAR(150),
    phone               VARCHAR(20),
    membership_number   INT           UNIQUE,
    total_points        INT           DEFAULT 0,
    preferred_language  VARCHAR(10),
    address             VARCHAR(255),
    password_hash VARCHAR(255) NOT NULL COMMENT 'Hashed password (bcrypt)'
);

CREATE TABLE purchase (
    id              INT             PRIMARY KEY,
    customer_id     INT,
    total_amount    DECIMAL(10,2)   NOT NULL,
    points_earned   INT             DEFAULT 0,
    purchase_date   DATETIME        NOT NULL,
    payment_method  VARCHAR(30),
    receipt_sent    BOOLEAN         DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES customer(id)
);

CREATE TABLE purchase_item (
    id           INT            PRIMARY KEY,
    purchase_id  INT            NOT NULL,
    product_id   INT            NOT NULL,
    quantity     INT            NOT NULL,
    unit_price   DECIMAL(10,2)  NOT NULL,
    subtotal     DECIMAL(10,2)  NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchase(id),
    FOREIGN KEY (product_id)  REFERENCES product(id)
);

INSERT INTO product (id, name, category, price, upc, epc, manufacturer, shelf_life_days) VALUES
(1,  'Whole Milk 1L',          'Dairy',      2.49,  '0012345678901', 'EPC000000000000000001', 'DairyFarm Co.',      10),
(2,  'Sourdough Bread',        'Bakery',     3.99,  '0023456789012', 'EPC000000000000000002', 'Artisan Breads Ltd.', 5),
(3,  'Free-Range Eggs x12',    'Dairy',      5.29,  '0034567890123', 'EPC000000000000000003', 'Happy Hen Farms',    21),
(4,  'Orange Juice 1.5L',      'Beverages',  4.19,  '0045678901234', 'EPC000000000000000004', 'SunSqueeze Inc.',    14),
(5,  'Cheddar Cheese 400g',    'Dairy',      6.79,  '0056789012345', 'EPC000000000000000005', 'DairyFarm Co.',      60),
(6,  'Chicken Breast 500g',    'Meat',       8.49,  '0067890123456', 'EPC000000000000000006', 'FreshMeat Packers',  3),
(7,  'Sparkling Water 6-pack', 'Beverages',  3.29,  '0078901234567', 'EPC000000000000000007', 'AquaFizz',          365);

INSERT INTO stock_reception (id, product_id, quantity_received, date_received, current_stock) VALUES
(1, 1, 120, '2026-03-25', 47),
(2, 2,  80, '2026-03-26', 22),
(3, 3, 200, '2026-03-26', 115),
(4, 4,  60, '2026-03-27', 31),
(5, 5,  90, '2026-03-28', 54),
(6, 6, 150, '2026-03-29', 88),
(7, 7, 100, '2026-03-30', 63);

INSERT INTO customer (id, name, email, phone, membership_number, total_points, preferred_language, address, password_hash) VALUES
(1, 'Alice Tremblay',   'alice.tremblay@email.com',  '514-555-0101', 100001, 320,  'fr', '12 Rue Saint-Denis, Montréal, QC', '$2y$10$example_hash'),
(2, 'Bob Nguyen',       'bob.nguyen@email.com',      '514-555-0202', 100002, 150,  'en', '45 Blvd René-Lévesque, Montréal, QC', '$2y$10$example_hash'),
(3, 'Clara Rossi',      'clara.rossi@email.com',     '438-555-0303', 100003, 540,  'fr', '8 Avenue du Parc, Montréal, QC', '$2y$10$example_hash'),
(4, 'David Park',       'david.park@email.com',      '514-555-0404', 100004, 85,   'en', '200 Rue Sherbrooke O, Montréal, QC', '$2y$10$example_hash'),
(5, 'Emma Lafleur',     'emma.lafleur@email.com',    '438-555-0505', 100005, 1020, 'fr', '77 Chemin de la Côte-des-Neiges, Montréal, QC', '$2y$10$example_hash'),
(6, 'Fatima Al-Rashid', 'fatima.alrashid@email.com', '514-555-0606', 100006, 210,  'en', '3 Rue de la Montagne, Montréal, QC', '$2y$10$example_hash'),
(7, 'George Osei',      'george.osei@email.com',     '438-555-0707', 100007, 470,  'en', '55 Rue Peel, Montréal, QC', '$2y$10$example_hash'),
(8, 'Admin Test',       'admin.test@smartstore.local','514-555-0808',100008, 0,    'en', '100 Test Ave, Montreal, QC', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO purchase (id, customer_id, total_amount, points_earned, purchase_date, payment_method, receipt_sent) VALUES
(1,  1,    14.76, 14,  '2026-03-28 09:14:00', 'credit_card',   TRUE),
(2,  2,     8.48, 8,   '2026-03-28 10:02:00', 'debit_card',    TRUE),
(3,  3,    22.55, 22,  '2026-03-28 11:30:00', 'mobile_pay',    TRUE),
(4,  NULL, 11.27, 0,   '2026-03-29 08:45:00', 'cash',          FALSE),
(5,  4,    17.06, 17,  '2026-03-29 13:20:00', 'credit_card',   TRUE),
(6,  5,    30.14, 30,  '2026-03-30 16:05:00', 'mobile_pay',    TRUE),
(7,  NULL,  3.29, 0,   '2026-03-31 07:50:00', 'cash',          FALSE);

INSERT INTO purchase_item (id, purchase_id, product_id, quantity, unit_price, subtotal) VALUES
(1,  1, 1, 2, 2.49,  4.98),
(2,  1, 2, 1, 3.99,  3.99),
(3,  1, 4, 1, 4.19,  4.19),
(4,  2, 3, 1, 5.29,  5.29),
(5,  2, 5, 1, 6.79,  6.79),
(6,  3, 6, 2, 8.49, 16.98),
(7,  3, 1, 1, 2.49,  2.49),
(8,  3, 2, 1, 3.99,  3.99),
(9,  4, 4, 1, 4.19,  4.19),
(10, 4, 7, 1, 3.29,  3.29),
(11, 4, 2, 1, 3.99,  3.99),
(12, 5, 3, 2, 5.29, 10.58),
(13, 5, 1, 1, 2.49,  2.49),
(14, 5, 5, 1, 6.79,  6.79),
(15, 6, 6, 2, 8.49, 16.98),
(16, 6, 3, 1, 5.29,  5.29),
(17, 6, 7, 1, 3.29,  3.29),
(18, 6, 2, 1, 3.99,  3.99),
(19, 7, 7, 1, 3.29,  3.29);

CREATE OR REPLACE VIEW receipt AS
SELECT
    p.id                AS purchase_id,
    p.purchase_date,
    p.payment_method,
    p.receipt_sent,
    c.name              AS customer_name,
    c.email             AS customer_email,
    pi.id               AS item_id,
    pr.name             AS product_name,
    pr.category,
    pi.quantity,
    pi.unit_price,
    pi.subtotal,
    p.total_amount,
    p.points_earned
FROM purchase p
LEFT JOIN customer      c  ON c.id  = p.customer_id
JOIN      purchase_item pi ON pi.purchase_id = p.id
JOIN      product       pr ON pr.id = pi.product_id
ORDER BY p.id, pi.id;

-- Phase 2 — IoT refrigerators, readings, alerts, notifications
DROP TABLE IF EXISTS TemperatureAlerts;
DROP TABLE IF EXISTS SensorReadings;
DROP TABLE IF EXISTS SystemNotifications;
DROP TABLE IF EXISTS Refrigerators;

CREATE TABLE Refrigerators (
    RefrigeratorID INT NOT NULL AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Location VARCHAR(100) NOT NULL,
    MQTT_Topic VARCHAR(50) NOT NULL,
    Temperature_Threshold DECIMAL(5,2) DEFAULT 4.00,
    Humidity_Threshold DECIMAL(5,2) DEFAULT 80.00,
    Fan_Status ENUM('ON','OFF') DEFAULT 'OFF',
    Is_Active TINYINT(1) DEFAULT 1,
    CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (RefrigeratorID),
    UNIQUE KEY MQTT_Topic (MQTT_Topic)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE SensorReadings (
    ReadingID INT NOT NULL AUTO_INCREMENT,
    RefrigeratorID INT NOT NULL,
    Temperature DECIMAL(5,2) NOT NULL,
    Humidity DECIMAL(5,2) NOT NULL,
    ReadingTime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ReadingID),
    KEY idx_refrigerator_time (RefrigeratorID, ReadingTime),
    CONSTRAINT SensorReadings_ibfk_1 FOREIGN KEY (RefrigeratorID) REFERENCES Refrigerators (RefrigeratorID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE TemperatureAlerts (
    AlertID INT NOT NULL AUTO_INCREMENT,
    RefrigeratorID INT NOT NULL,
    Temperature DECIMAL(5,2) NOT NULL,
    Threshold DECIMAL(5,2) NOT NULL,
    AlertType ENUM('TEMPERATURE_HIGH','TEMPERATURE_LOW','HUMIDITY_HIGH') NOT NULL,
    Message TEXT,
    EmailSent TINYINT(1) DEFAULT 0,
    UserResponse ENUM('YES','NO','PENDING') DEFAULT 'PENDING',
    FanActivated TINYINT(1) DEFAULT 0,
    AlertTime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ResolvedAt TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (AlertID),
    KEY RefrigeratorID (RefrigeratorID),
    CONSTRAINT TemperatureAlerts_ibfk_1 FOREIGN KEY (RefrigeratorID) REFERENCES Refrigerators (RefrigeratorID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE SystemNotifications (
    NotificationID INT NOT NULL AUTO_INCREMENT,
    Title VARCHAR(200) NOT NULL,
    Message TEXT NOT NULL,
    Type ENUM('INFO','WARNING','ERROR','SUCCESS') DEFAULT 'INFO',
    IsRead TINYINT(1) DEFAULT 0,
    CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (NotificationID),
    KEY idx_read_status (IsRead, CreatedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO Refrigerators (RefrigeratorID, Name, Location, MQTT_Topic, Temperature_Threshold, Humidity_Threshold, Fan_Status, Is_Active) VALUES
(1, 'Refrigerator 1', 'Store Front', 'Frig1', 4.00, 80.00, 'OFF', 1),
(2, 'Refrigerator 2', 'Store Back', 'Frig2', 4.00, 80.00, 'OFF', 1);
