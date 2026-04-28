DROP TABLE IF EXISTS PURCHASE_ITEM;
DROP TABLE IF EXISTS PURCHASE;
DROP TABLE IF EXISTS STOCK_RECEPTION;
DROP TABLE IF EXISTS CUSTOMER;
DROP TABLE IF EXISTS PRODUCT;
DROP TABLE IF EXISTS CATEGORY;

DROP TABLE IF EXISTS purchase_item;
DROP TABLE IF EXISTS purchase;
DROP TABLE IF EXISTS stock_reception;
DROP TABLE IF EXISTS customer;
DROP TABLE IF EXISTS product;
DROP TABLE IF EXISTS category;

CREATE TABLE category (
    id          INT           PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(50)   NOT NULL,
    description VARCHAR(255),
    low_stock_threshold INT   NOT NULL DEFAULT 5
);

CREATE TABLE product (
    id             INT            PRIMARY KEY AUTO_INCREMENT,
    name           VARCHAR(100)   NOT NULL,
    category_id    INT            NOT NULL,
    price          DECIMAL(10,2)  NOT NULL,
    upc            VARCHAR(13),
    epc            VARCHAR(24),
    manufacturer   VARCHAR(100),
    shelf_life_days INT,
    FOREIGN KEY (category_id) REFERENCES category(id)
);


CREATE TABLE stock_reception (
    id                 INT           PRIMARY KEY AUTO_INCREMENT,
    product_id         INT           NOT NULL,
    quantity_received  INT           NOT NULL,
    date_received      DATE          NOT NULL,
    current_stock      INT           NOT NULL,
    FOREIGN KEY (product_id) REFERENCES product(id)
);

CREATE TABLE customer (
    CustomerID       INT           NOT NULL PRIMARY KEY,
    FirstName        VARCHAR(100)  NOT NULL,
    LastName         VARCHAR(100)  NOT NULL,
    Email            VARCHAR(150)  NULL,
    PhoneNumber      VARCHAR(20)   NULL,
    MembershipNumber VARCHAR(20)  NOT NULL,
    TotalPoints      INT           NOT NULL DEFAULT 0,
    PasswordHash     VARCHAR(255)  NOT NULL,
    CreatedAt        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    EmailVerified    TINYINT(1)    NOT NULL DEFAULT 0,
    UNIQUE KEY uq_customer_membership (MembershipNumber)
);

CREATE TABLE purchase (
    id              INT             PRIMARY KEY AUTO_INCREMENT,
    customer_id     INT,
    total_amount    DECIMAL(10,2)   NOT NULL,
    points_earned   INT             DEFAULT 0,
    purchase_date   DATETIME        NOT NULL,
    payment_method  VARCHAR(30),
    receipt_sent    BOOLEAN         DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES customer(CustomerID)
);

CREATE TABLE purchase_item (
    id           INT            PRIMARY KEY AUTO_INCREMENT,
    purchase_id  INT            NOT NULL,
    product_id   INT            NOT NULL,
    quantity     INT            NOT NULL,
    unit_price   DECIMAL(10,2)  NOT NULL,
    subtotal     DECIMAL(10,2)  NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchase(id),
    FOREIGN KEY (product_id)  REFERENCES product(id)
);

INSERT INTO category (id, name, description, low_stock_threshold) VALUES
(1, 'Dairy',     'Milk, eggs, cheese and other dairy products', 5),
(2, 'Bakery',    'Breads, pastries and baked goods', 6),
(3, 'Beverages', 'Juices, waters and other drinks', 8),
(4, 'Meat',      'Fresh and packaged meat products', 4);

INSERT INTO product (id, name, category_id, price, upc, epc, manufacturer, shelf_life_days) VALUES
(1,  'Whole Milk 1L',          1, 2.49,  '0012345678901', 'EPC000000000000000001', 'DairyFarm Co.',       10),
(2,  'Sourdough Bread',        2, 3.99,  '0023456789012', 'EPC000000000000000002', 'Artisan Breads Ltd.',  5),
(3,  'Free-Range Eggs x12',    1, 5.29,  '0034567890123', 'EPC000000000000000003', 'Happy Hen Farms',     21),
(4,  'Orange Juice 1.5L',      3, 4.19,  '0045678901234', 'EPC000000000000000004', 'SunSqueeze Inc.',     14),
(5,  'Cheddar Cheese 400g',    1, 6.79,  '0056789012345', 'EPC000000000000000005', 'DairyFarm Co.',       60),
(6,  'Chicken Breast 500g',    4, 8.49,  '0067890123456', 'EPC000000000000000006', 'FreshMeat Packers',    3),
(7,  'Sparkling Water 6-pack', 3, 3.29,  '0078901234567', 'EPC000000000000000007', 'AquaFizz',           365);

INSERT INTO stock_reception (id, product_id, quantity_received, date_received, current_stock) VALUES
(1, 1, 120, '2026-03-25', 47),
(2, 2,  80, '2026-03-26', 22),
(3, 3, 200, '2026-03-26', 115),
(4, 4,  60, '2026-03-27', 31),
(5, 5,  90, '2026-03-28', 54),
(6, 6, 150, '2026-03-29', 88),
(7, 7, 100, '2026-03-30', 63);

INSERT INTO customer (CustomerID, FirstName, LastName, Email, PhoneNumber, MembershipNumber, TotalPoints, PasswordHash, CreatedAt, EmailVerified) VALUES
(1, 'Alice',    'Tremblay',   'alice.tremblay@email.com',   '5145550101', 'M100001', 320,  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-15 10:00:00', 0),
(2, 'Bob',      'Nguyen',     'bob.nguyen@email.com',       '5145550202', 'M100002', 150,  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-16 11:00:00', 0),
(3, 'Clara',    'Rossi',      'clara.rossi@email.com',      '4385550303', 'M100003', 540,  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-17 12:00:00', 0),
(4, 'David',    'Park',       'david.park@email.com',       '5145550404', 'M100004', 85,   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-18 09:00:00', 0),
(5, 'Emma',     'Lafleur',    'emma.lafleur@email.com',     '4385550505', 'M100005', 1020, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-19 14:00:00', 0),
(6, 'Fatima',   'Al-Rashid',  'fatima.alrashid@email.com',  '5145550606', 'M100006', 210,  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-20 15:00:00', 0),
(7, 'George',   'Osei',       'george.osei@email.com',      '4385550707', 'M100007', 470,  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-21 16:00:00', 0),
(8, 'Admin',    'Test',       'admin.test@smartstore.local','5145550808', 'M100008', 0,    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-01 10:00:00', 0),
(9, 'MK',       'Admin',      'mkprogrammerk80@gmail.com',  '5145550909', 'M100009', 0,    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-02 10:00:00', 0);

INSERT INTO purchase (id, customer_id, total_amount, points_earned, purchase_date, payment_method, receipt_sent) VALUES
(1,  1,    14.76, 14,  '2026-03-28 09:14:00', 'credit_card',   TRUE),
(2,  2,     8.48, 8,   '2026-03-28 10:02:00', 'debit_card',    TRUE),
(3,  3,    22.55, 22,  '2026-03-28 11:30:00', 'mobile_pay',    TRUE),
(4,  NULL, 11.27, 0,   '2026-03-29 08:45:00', 'cash',          FALSE),
(5,  4,    17.06, 17,  '2026-03-29 13:20:00', 'credit_card',   TRUE),
(6,  5,    30.14, 30,  '2026-03-30 16:05:00', 'mobile_pay',    TRUE),
(7,  NULL,  3.29, 0,   '2026-03-31 07:50:00', 'cash',          FALSE),
(8,  6,    15.77, 15,  '2026-04-01 09:22:00', 'debit_card',    TRUE),
(9,  7,    27.25, 27,  '2026-04-03 18:14:00', 'credit_card',   TRUE),
(10, 8,    12.57, 12,  '2026-04-05 11:03:00', 'mobile_pay',    TRUE),
(11, 9,    19.26, 19,  '2026-04-07 15:41:00', 'credit_card',   TRUE);

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
(19, 7, 7, 1, 3.29,  3.29),
(20, 8, 4, 1, 4.19,  4.19),
(21, 8, 5, 1, 6.79,  6.79),
(22, 8, 7, 1, 3.29,  3.29),
(23, 8, 1, 1, 2.49,  2.49),
(24, 9, 6, 2, 8.49, 16.98),
(25, 9, 2, 1, 3.99,  3.99),
(26, 9, 4, 1, 4.19,  4.19),
(27, 9, 1, 1, 2.49,  2.49),
(28, 10, 3, 1, 5.29,  5.29),
(29, 10, 1, 1, 2.49,  2.49),
(30, 10, 7, 1, 3.29,  3.29),
(31, 10, 2, 1, 3.99,  3.99),
(32, 11, 5, 1, 6.79,  6.79),
(33, 11, 4, 1, 4.19,  4.19),
(34, 11, 2, 1, 3.99,  3.99),
(35, 11, 7, 1, 3.29,  3.29),
(36, 11, 1, 1, 2.49,  2.49);

CREATE OR REPLACE VIEW receipt AS
SELECT
    p.id                AS purchase_id,
    p.purchase_date,
    p.payment_method,
    p.receipt_sent,
    TRIM(CONCAT(c.FirstName, ' ', c.LastName)) AS customer_name,
    c.Email             AS customer_email,
    pi.id               AS item_id,
    pr.name             AS product_name,
    cat.name            AS category,
    pi.quantity,
    pi.unit_price,
    pi.subtotal,
    p.total_amount,
    p.points_earned
FROM purchase p
LEFT JOIN customer      c   ON c.CustomerID  = p.customer_id
JOIN      purchase_item pi  ON pi.purchase_id = p.id
JOIN      product       pr  ON pr.id          = pi.product_id
JOIN      category      cat ON cat.id         = pr.category_id
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
(2, 'Refrigerator 2', 'Store Back',  'Frig2', 4.00, 80.00, 'OFF', 1);
