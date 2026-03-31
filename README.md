# EventSys — Event Registration & Ticketing System
### CLASS GROUP 8 Project

---

## Project Structure

```
event_system/
├── index.php              # Public event calendar (event listing)
├── event.php              # Event details + ticket selection
├── cart.php               # Shopping cart + checkout + order summary
├── register.php           # Attendee registration (with CAPTCHA)
├── login.php              # Login page
├── logout.php             # Logout
├── dashboard.php          # Attendee dashboard (My Tickets)
├── admin/
│   ├── index.php          # Organizer admin panel
│   ├── create_event.php   # Admin: create event + ticket types
│   ├── edit_event.php     # Admin: edit event
│   └── attendees.php      # Admin: view all attendees
├── includes/
│   ├── config.php         # DB config, session, helpers, CSRF, CAPTCHA
│   ├── header.php         # Navigation + global CSS
│   └── footer.php         # Footer
├── sql/
│   └── event_system.sql   # Full database schema + demo data
├── apache_config/
│   └── event_system.conf  # Apache VHost + load balancing explanation
├── .htaccess              # Security headers, cache, PHP config
├── setup.sh               # Quick setup script
├── TECHNICAL_REPORT.md    # Full technical report (10 + 5 marks sections)
└── README.md              # This file
```

---

## Requirements Coverage

| # | Requirement | File(s) | Status |
|---|---|---|---|
| 1 | Networking Basics: Load balancing | `apache_config/event_system.conf`, `TECHNICAL_REPORT.md` | ✅ |
| 2 | Server Setup: Apache high concurrency | `apache_config/event_system.conf`, `TECHNICAL_REPORT.md` | ✅ |
| 3a | Server-side: Event listing page | `index.php` | ✅ |
| 3b | Server-side: Event details + ticket types | `event.php` | ✅ |
| 3c | Server-side: Registration form | `register.php` | ✅ |
| 3d | Server-side: Ticket selection + checkout | `cart.php` | ✅ |
| 3e | Server-side: Attendee dashboard | `dashboard.php` | ✅ |
| 3f | Server-side: Organizer admin panel | `admin/` | ✅ |
| 4 | Data Handling: Validate quantities, prevent overselling | `cart.php` (FOR UPDATE lock) | ✅ |
| 5 | State Management: Shopping cart with sessions | `includes/config.php`, `cart.php` | ✅ |
| 6 | Security: CAPTCHA on registration | `register.php`, `includes/config.php` | ✅ |
| 7 | Database: `event_system` with all 5 tables | `sql/event_system.sql` | ✅ |
| 8 | DB Queries: Transaction handling for ticket booking | `cart.php` | ✅ |
| 9 | CRUD: Manage events, tickets, attendees | `admin/` | ✅ |
| 10 | Advanced CGI: Concurrent request handling | `TECHNICAL_REPORT.md` | ✅ |
| 11 | Deployment: Scalability considerations | `TECHNICAL_REPORT.md` | ✅ |

---

## Database Tables

- **events** — Event details (title, venue, date, status)
- **ticket_types** — Ticket categories per event (name, price, qty)
- **attendees** — User accounts (name, email, role)
- **registrations** — Booking records (attendee ↔ event ↔ ticket)
- **payments** — Payment simulation records

---

## Quick Setup

```bash
# 1. Clone / copy project to web root
sudo cp -r event_system/ /var/www/html/

# 2. Import database
mysql -u root -p < /var/www/html/event_system/sql/event_system.sql

# 3. Update config (if needed)
nano /var/www/html/event_system/includes/config.php
# Set DB_USER, DB_PASS, SITE_URL

# 4. Enable Apache mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# 5. Visit
http://localhost/event_system
```

### Demo Accounts
| Role | Email | Password |
|---|---|---|
| Admin | admin@eventsys.com | password |
| Organizer | organizer@eventsys.com | password |
| Attendee | john@example.com | password |

---

## Key Features

### Public
- Browse all active events with availability progress bars
- Search & filter (upcoming / all / past)
- Event detail page with ticket types and pricing
- Add tickets to session-based shopping cart
- Multi-step checkout with simulated payment (card / M-Pesa / PayPal)

### Attendees (login required)
- Register with email + math CAPTCHA (anti-bot)
- Dashboard showing upcoming events and booking history
- Booking references displayed on each ticket

### Organizers / Admins
- Admin panel with revenue stats and recent bookings
- Create events with multiple ticket types
- Edit events and update ticket quantities
- View all registered attendees with search

### Security
- CSRF tokens on all forms
- Password hashing (bcrypt via `password_hash`)
- PDO prepared statements (SQL injection prevention)
- Math CAPTCHA on registration
- `.htaccess` blocks directory listing and access to `includes/`
- Security headers (X-Frame-Options, XSS-Protection, Content-Type-Options)

### Concurrent Booking Prevention
- MySQL `SELECT ... FOR UPDATE` row-level locking
- Atomic `quantity_sold = quantity_sold + N` updates
- Transaction rollback on any failure
- Server-side re-validation at checkout time (not just cart-time)
