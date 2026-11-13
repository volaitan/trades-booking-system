CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    email         TEXT NOT NULL UNIQUE,
    name          TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    name             TEXT NOT NULL,
    description      TEXT NOT NULL DEFAULT '',
    duration_minutes INTEGER NOT NULL,
    price_pence      INTEGER NOT NULL DEFAULT 0,
    active           INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS bookings (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    reference     TEXT NOT NULL UNIQUE,
    service_id    INTEGER NOT NULL REFERENCES services(id),
    customer_name TEXT NOT NULL,
    email         TEXT NOT NULL,
    phone         TEXT NOT NULL,
    address       TEXT NOT NULL,
    postcode      TEXT NOT NULL,
    notes         TEXT NOT NULL DEFAULT '',
    starts_at     TEXT NOT NULL,
    ends_at       TEXT NOT NULL,
    status        TEXT NOT NULL DEFAULT 'pending',
    created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_bookings_starts ON bookings(starts_at);

CREATE TABLE IF NOT EXISTS quotes (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    reference     TEXT NOT NULL UNIQUE,
    booking_id    INTEGER REFERENCES bookings(id),
    customer_name TEXT NOT NULL,
    email         TEXT NOT NULL,
    address       TEXT NOT NULL DEFAULT '',
    notes         TEXT NOT NULL DEFAULT '',
    vat_rate      INTEGER NOT NULL DEFAULT 20,
    status        TEXT NOT NULL DEFAULT 'draft',
    created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS quote_items (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    quote_id         INTEGER NOT NULL REFERENCES quotes(id) ON DELETE CASCADE,
    description      TEXT NOT NULL,
    quantity         INTEGER NOT NULL,
    unit_price_pence INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS invoices (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    number         TEXT NOT NULL UNIQUE,
    quote_id       INTEGER REFERENCES quotes(id),
    customer_name  TEXT NOT NULL,
    email          TEXT NOT NULL,
    address        TEXT NOT NULL DEFAULT '',
    issue_date     TEXT NOT NULL,
    due_date       TEXT NOT NULL,
    vat_rate       INTEGER NOT NULL,
    subtotal_pence INTEGER NOT NULL,
    vat_pence      INTEGER NOT NULL,
    total_pence    INTEGER NOT NULL,
    status         TEXT NOT NULL DEFAULT 'unpaid',
    paid_on        TEXT
);

CREATE TABLE IF NOT EXISTS invoice_items (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id       INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    description      TEXT NOT NULL,
    quantity         INTEGER NOT NULL,
    unit_price_pence INTEGER NOT NULL
);
