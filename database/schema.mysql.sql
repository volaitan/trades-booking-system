CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) NOT NULL UNIQUE,
    name          VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100) NOT NULL,
    description      VARCHAR(500) NOT NULL DEFAULT '',
    duration_minutes INT UNSIGNED NOT NULL,
    price_pence      INT UNSIGNED NOT NULL DEFAULT 0,
    active           TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bookings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference     VARCHAR(20) NOT NULL UNIQUE,
    service_id    INT UNSIGNED NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    phone         VARCHAR(30) NOT NULL,
    address       VARCHAR(255) NOT NULL,
    postcode      VARCHAR(10) NOT NULL,
    notes         TEXT NOT NULL,
    starts_at     DATETIME NOT NULL,
    ends_at       DATETIME NOT NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bookings_starts (starts_at),
    FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quotes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference     VARCHAR(20) NOT NULL UNIQUE,
    booking_id    INT UNSIGNED NULL,
    customer_name VARCHAR(100) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    address       VARCHAR(255) NOT NULL DEFAULT '',
    notes         TEXT NOT NULL,
    vat_rate      INT UNSIGNED NOT NULL DEFAULT 20,
    status        VARCHAR(20) NOT NULL DEFAULT 'draft',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quote_items (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote_id         INT UNSIGNED NOT NULL,
    description      VARCHAR(255) NOT NULL,
    quantity         INT UNSIGNED NOT NULL,
    unit_price_pence INT UNSIGNED NOT NULL,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    number         VARCHAR(20) NOT NULL UNIQUE,
    quote_id       INT UNSIGNED NULL,
    customer_name  VARCHAR(100) NOT NULL,
    email          VARCHAR(255) NOT NULL,
    address        VARCHAR(255) NOT NULL DEFAULT '',
    issue_date     DATE NOT NULL,
    due_date       DATE NOT NULL,
    vat_rate       INT UNSIGNED NOT NULL,
    subtotal_pence INT UNSIGNED NOT NULL,
    vat_pence      INT UNSIGNED NOT NULL,
    total_pence    INT UNSIGNED NOT NULL,
    status         VARCHAR(20) NOT NULL DEFAULT 'unpaid',
    paid_on        DATE NULL,
    FOREIGN KEY (quote_id) REFERENCES quotes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_items (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id       INT UNSIGNED NOT NULL,
    description      VARCHAR(255) NOT NULL,
    quantity         INT UNSIGNED NOT NULL,
    unit_price_pence INT UNSIGNED NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
