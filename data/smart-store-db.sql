DROP TABLE IF EXISTS PURCHASE_ITEM;
DROP TABLE IF EXISTS PURCHASE;
DROP TABLE IF EXISTS STOCK_RECEPTION;
DROP TABLE IF EXISTS CUSTOMER;
DROP TABLE IF EXISTS PRODUCT;

CREATE TABLE PRODUCT (
    id             INT            PRIMARY KEY,
    name           VARCHAR(100)   NOT NULL,
    category       VARCHAR(50),
    price          DECIMAL(10,2)  NOT NULL,
    upc            VARCHAR(13),
    epc            VARCHAR(24),
    manufacturer   VARCHAR(100),
    image_url      VARCHAR(255),
    shelf_life_days INT
);

CREATE TABLE STOCK_RECEPTION (
    id                 INT           PRIMARY KEY,
    product_id         INT           NOT NULL,
    quantity_received  INT           NOT NULL,
    date_received      DATE          NOT NULL,
    current_stock      INT           NOT NULL,
    FOREIGN KEY (product_id) REFERENCES PRODUCT(id)
);

CREATE TABLE CUSTOMER (
    id                  INT           PRIMARY KEY,
    name                VARCHAR(100)  NOT NULL,
    email               VARCHAR(150),
    phone               VARCHAR(20),
    membership_number   INT           UNIQUE,
    total_points        INT           DEFAULT 0,
    preferred_language  VARCHAR(10),
    address             VARCHAR(255)
);

CREATE TABLE PURCHASE (
    id              INT             PRIMARY KEY,
    customer_id     INT,                          -- nullable (guest purchase)
    total_amount    DECIMAL(10,2)   NOT NULL,
    points_earned   INT             DEFAULT 0,
    purchase_date   DATETIME        NOT NULL,
    payment_method  VARCHAR(30),
    receipt_sent    BOOLEAN         DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES CUSTOMER(id)
);

CREATE TABLE PURCHASE_ITEM (
    id           INT            PRIMARY KEY,
    purchase_id  INT            NOT NULL,
    product_id   INT            NOT NULL,
    quantity     INT            NOT NULL,
    unit_price   DECIMAL(10,2)  NOT NULL,
    subtotal     DECIMAL(10,2)  NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES PURCHASE(id),
    FOREIGN KEY (product_id)  REFERENCES PRODUCT(id)
);

INSERT INTO PRODUCT (id, name, category, price, upc, epc, manufacturer, image_url, shelf_life_days) VALUES
(1,  'Whole Milk 1L',          'Dairy',      2.49,  '0012345678901', 'EPC000000000000000001', 'DairyFarm Co.',      'https://cdn.store/milk1l.jpg',       10),
(2,  'Sourdough Bread',        'Bakery',     3.99,  '0023456789012', 'EPC000000000000000002', 'Artisan Breads Ltd.','https://cdn.store/sourdough.jpg',    5),
(3,  'Free-Range Eggs x12',    'Dairy',      5.29,  '0034567890123', 'EPC000000000000000003', 'Happy Hen Farms',    'https://cdn.store/eggs12.jpg',       21),
(4,  'Orange Juice 1.5L',      'Beverages',  4.19,  '0045678901234', 'EPC000000000000000004', 'SunSqueeze Inc.',    'https://cdn.store/oj1.5l.jpg',       14),
(5,  'Cheddar Cheese 400g',    'Dairy',      6.79,  '0056789012345', 'EPC000000000000000005', 'DairyFarm Co.',      'https://cdn.store/cheddar.jpg',      60),
(6,  'Chicken Breast 500g',    'Meat',       8.49,  '0067890123456', 'EPC000000000000000006', 'FreshMeat Packers', 'https://cdn.store/chicken500.jpg',   3),
(7,  'Sparkling Water 6-pack', 'Beverages',  3.29,  '0078901234567', 'EPC000000000000000007', 'AquaFizz',           'https://cdn.store/sparkling6pk.jpg', 365);


INSERT INTO STOCK_RECEPTION (id, product_id, quantity_received, date_received, current_stock) VALUES
(1, 1, 120, '2026-03-25', 47),
(2, 2,  80, '2026-03-26', 22),
(3, 3, 200, '2026-03-26', 115),
(4, 4,  60, '2026-03-27', 31),
(5, 5,  90, '2026-03-28', 54),
(6, 6, 150, '2026-03-29', 88),
(7, 7, 100, '2026-03-30', 63);


INSERT INTO CUSTOMER (id, name, email, phone, membership_number, total_points, preferred_language, address) VALUES
(1, 'Alice Tremblay',   'alice.tremblay@email.com',  '514-555-0101', 100001, 320,  'fr', '12 Rue Saint-Denis, Montréal, QC'),
(2, 'Bob Nguyen',       'bob.nguyen@email.com',      '514-555-0202', 100002, 150,  'en', '45 Blvd René-Lévesque, Montréal, QC'),
(3, 'Clara Rossi',      'clara.rossi@email.com',     '438-555-0303', 100003, 540,  'fr', '8 Avenue du Parc, Montréal, QC'),
(4, 'David Park',       'david.park@email.com',      '514-555-0404', 100004, 85,   'en', '200 Rue Sherbrooke O, Montréal, QC'),
(5, 'Emma Lafleur',     'emma.lafleur@email.com',    '438-555-0505', 100005, 1020, 'fr', '77 Chemin de la Côte-des-Neiges, Montréal, QC'),
(6, 'Fatima Al-Rashid', 'fatima.alrashid@email.com', '514-555-0606', 100006, 210,  'ar', '3 Rue de la Montagne, Montréal, QC'),
(7, 'George Osei',      'george.osei@email.com',     '438-555-0707', 100007, 470,  'en', '55 Rue Peel, Montréal, QC');


INSERT INTO PURCHASE (id, customer_id, total_amount, points_earned, purchase_date, payment_method, receipt_sent) VALUES
(1,  1,    14.76, 14,  '2026-03-28 09:14:00', 'credit_card',   TRUE),
(2,  2,     8.48, 8,   '2026-03-28 10:02:00', 'debit_card',    TRUE),
(3,  3,    22.55, 22,  '2026-03-28 11:30:00', 'mobile_pay',    TRUE),
(4,  NULL, 11.27, 0,   '2026-03-29 08:45:00', 'cash',          FALSE),
(5,  4,    17.06, 17,  '2026-03-29 13:20:00', 'credit_card',   TRUE),
(6,  5,    30.14, 30,  '2026-03-30 16:05:00', 'mobile_pay',    TRUE),
(7,  NULL,  3.29, 0,   '2026-03-31 07:50:00', 'cash',          FALSE);


INSERT INTO PURCHASE_ITEM (id, purchase_id, product_id, quantity, unit_price, subtotal) VALUES
-- Purchase 1: milk + bread
(1,  1, 1, 2, 2.49,  4.98),
(2,  1, 2, 1, 3.99,  3.99),
(3,  1, 4, 1, 4.19,  4.19),   -- OJ rounds total to 13.16 + tax ≈ 14.76
-- Purchase 2: eggs only (2 packs at discounted unit price on receipt)
(4,  2, 3, 1, 5.29,  5.29),
(5,  2, 5, 1, 6.79,  6.79),   -- cheddar
-- Purchase 3: bigger shop
(6,  3, 6, 2, 8.49, 16.98),   -- chicken x2
(7,  3, 1, 1, 2.49,  2.49),
(8,  3, 2, 1, 3.99,  3.99),   -- bread
-- Purchase 4: guest – water + juice
(9,  4, 4, 1, 4.19,  4.19),
(10, 4, 7, 1, 3.29,  3.29),
(11, 4, 2, 1, 3.99,  3.99),   -- bread
-- Purchase 5: eggs + milk
(12, 5, 3, 2, 5.29, 10.58),
(13, 5, 1, 1, 2.49,  2.49),
(14, 5, 5, 1, 6.79,  6.79),   -- cheddar
-- Purchase 6: full weekly basket
(15, 6, 6, 2, 8.49, 16.98),
(16, 6, 3, 1, 5.29,  5.29),
(17, 6, 7, 1, 3.29,  3.29),
(18, 6, 2, 1, 3.99,  3.99),
-- Purchase 7: guest – just sparkling water
(19, 7, 7, 1, 3.29,  3.29);


CREATE OR REPLACE VIEW Receipt AS
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
FROM PURCHASE p
LEFT JOIN CUSTOMER      c  ON c.id  = p.customer_id
JOIN      PURCHASE_ITEM pi ON pi.purchase_id = p.id
JOIN      PRODUCT       pr ON pr.id = pi.product_id
ORDER BY p.id, pi.id;
