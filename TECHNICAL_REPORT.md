# EventSys — Technical Report
## CLASS GROUP 8: Event Registration and Ticketing System

---

## 1. Ticket Inventory Management Logic

Ticket inventory is managed through the `ticket_types` table which tracks both `quantity_total` (maximum capacity) and `quantity_sold` (running count). 

When a user selects tickets:
- The event detail page fetches available counts live: `quantity_total - quantity_sold`
- Visual progress bars show fill percentage (e.g., "220/500 sold, 44% full")
- A "Sold Out" badge appears when `quantity_sold >= quantity_total`
- Tickets cannot be added to cart if availability is exceeded (server-side validation)

---

## 2. Database Transaction Implementation (Requirement #8)

The checkout process in `cart.php` uses **PDO transactions** to ensure atomicity:

```php
$db->beginTransaction();
try {
    // 1. Lock the row to prevent concurrent reads
    $lock = $db->prepare("SELECT ... FROM ticket_types WHERE id = ? FOR UPDATE");
    
    // 2. Check available inventory
    $avail = $tt['quantity_total'] - $tt['quantity_sold'];
    if ($avail < $requested_qty) {
        $db->rollBack();  // Abort if not enough tickets
        throw new Exception("Not enough tickets");
    }

    // 3. Create registration record
    $db->prepare("INSERT INTO registrations ...)->execute(...);

    // 4. Simulate payment
    $db->prepare("INSERT INTO payments ...)->execute(...);

    // 5. Atomically increment sold count
    $db->prepare("UPDATE ticket_types SET quantity_sold = quantity_sold + ? WHERE id = ?")->execute(...);

    // 6. Confirm registration
    $db->prepare("UPDATE registrations SET status='confirmed' WHERE id=?")->execute(...);

    $db->commit();  // Only commits if ALL steps succeed
} catch (Exception $e) {
    $db->rollBack();  // Roll back everything on any failure
}
```

The `FOR UPDATE` row lock is critical — it prevents two users from simultaneously reading the same "5 tickets left" and both booking them, which would result in overselling.

---

## 3. Concurrent Booking Prevention

**Problem**: Two users buy the last ticket simultaneously. Both read `quantity_sold = 99`, both see 1 available, both insert registrations — now `quantity_sold = 101` with only 100 total.

**Solution implemented**:
1. **`SELECT ... FOR UPDATE`** — Acquires a row-level exclusive lock in the transaction. Other transactions attempting to modify this row must wait.
2. **`quantity_sold = quantity_sold + N`** — Atomic increment; avoids race condition from read-then-write patterns.
3. **Database-level constraint** — `quantity_sold` can only increase through the transaction, not direct user input.
4. **Re-validation at checkout** — Even if cart showed "available", inventory is re-checked server-side at payment time.

---

## 4. Email Notification System Design

> (Implementation design — sending real emails requires an SMTP server)

**Trigger points**:
| Event | Email sent to |
|---|---|
| Registration confirmed | Attendee (booking reference + QR code) |
| Event cancelled | All registered attendees |
| Event details changed | All registered attendees |
| New attendee registers | Admin/Organizer |

**Recommended implementation using PHPMailer + Gmail SMTP**:

```php
use PHPMailer\PHPMailer\PHPMailer;

function sendBookingConfirmation($attendee, $registration, $event) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@eventsys.com';
    $mail->Password = 'app_password';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->addAddress($attendee['email'], $attendee['first_name']);
    $mail->Subject = "Booking Confirmed: {$event['title']} [{$registration['booking_ref']}]";
    $mail->isHTML(true);
    $mail->Body = renderEmailTemplate('booking_confirmed', compact('attendee','registration','event'));
    $mail->send();
}
```

For high-volume events (flash sales), use a **job queue** (e.g., Redis + worker process) to dispatch emails asynchronously so HTTP responses aren't delayed.

---

## 5. Handling Flash Sales and High Traffic (Term Report)

**Challenges**:
- Thousands of concurrent users hitting "Buy" at the same time
- Database becoming a bottleneck under heavy write load

**Solutions**:

### A. Load Balancing (Apache mod_proxy_balancer)
Multiple app servers behind a load balancer. Requests distributed using `byrequests` (round-robin) or `bybusyness` (least-busy). Sticky sessions ensure cart data persists for each user.

### B. Database Optimizations
- **Connection pooling** via PgBouncer or ProxySQL
- **Read replicas** for SELECT queries (event list, availability checks)
- **Write to master only** for registrations/payments
- **Index** `ticket_types(event_id)`, `registrations(attendee_id)`, `registrations(event_id)`

### C. Caching
- Cache event list and ticket availability in **Redis** (invalidate on each booking)
- Cache user session data in Redis instead of PHP file sessions
- CDN (Cloudflare) for static assets

### D. Queue-based Booking
Under extreme load, accept booking requests into a **Redis queue** and process them serially, returning a "you're in queue, position #47" response to users. This prevents database overload.

---

## 6. Refund and Cancellation Workflow (Term Report)

**Cancellation by Attendee**:
1. Attendee requests cancellation from dashboard
2. System checks cancellation policy (e.g., >48h before event = full refund)
3. Registration status → `cancelled`
4. `ticket_types.quantity_sold` decremented (ticket returned to pool)
5. Payment record updated to `refunded`
6. Email notification sent

**Cancellation by Organizer**:
1. Admin sets event status → `cancelled`
2. Cron job / trigger loops all `confirmed` registrations for that event
3. All registrations → `cancelled`, payments → `refunded`
4. Mass email notification to all attendees

**Database update**:
```sql
-- Within a transaction
UPDATE registrations SET status = 'cancelled' WHERE id = ?;
UPDATE ticket_types SET quantity_sold = quantity_sold - ? WHERE id = ?;
UPDATE payments SET payment_status = 'refunded' WHERE registration_id = ?;
```

---

## 7. QR Code Ticket Possibilities (Term Report)

Each registration has a unique `booking_ref` (e.g., `EVT-A3F7C2B1`). This can be encoded into a QR code for event check-in.

**Implementation**:
```php
// Using endroid/qr-code (composer package)
use Endroid\QrCode\QrCode;

$qr = QrCode::create($registration['booking_ref'])
    ->setSize(200)
    ->setMargin(10);

$writer = new PngWriter();
$result = $writer->write($qr);
// Embed in email as base64 or save as PNG
$dataUri = $result->getDataUri(); // display inline in HTML
```

**Check-in flow**:
1. Attendee presents QR code on phone / printed
2. Organizer scans with camera app → reads booking ref
3. System checks `registrations` table: status=confirmed, event matches
4. Mark as checked-in (add `checked_in_at` column)
5. Prevent duplicate scan (check `checked_in_at IS NOT NULL`)

**Security**: Sign the QR payload with HMAC to prevent forged codes:
```php
$payload = $booking_ref . ':' . hash_hmac('sha256', $booking_ref, SECRET_KEY);
```

---

## 8. Advanced CGI & Concurrent Requests (Requirement #10)

**How CGI handles concurrent requests**:
- Classic CGI spawns a **new process per request** — this doesn't scale well
- PHP-FPM (FastCGI Process Manager) maintains a **pool of PHP worker processes** that handle requests concurrently without forking per request
- Apache + mod_php embeds PHP in the Apache process, sharing the process pool

**PHP-FPM configuration for high concurrency**:
```ini
; /etc/php/8.x/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50        ; Max simultaneous PHP workers
pm.start_servers = 10       ; Workers at startup
pm.min_spare_servers = 5    ; Minimum idle workers
pm.max_spare_servers = 20   ; Maximum idle workers
pm.max_requests = 500       ; Restart worker after N requests (prevents memory leaks)
```

**With load balancer**: Each app server runs its own FPM pool. Apache distributes incoming connections via mod_proxy_balancer, and each server's FPM handles its share concurrently.

---

## 9. Deployment & Scalability Considerations (Requirement #11)

| Layer | Technology | Scalability approach |
|---|---|---|
| DNS | Cloudflare | Global anycast, DDoS protection |
| Load Balancer | Apache mod_proxy_balancer / HAProxy | Horizontal scaling |
| App Servers | PHP-FPM + Apache (2+ servers) | Add more servers under load |
| Database | MySQL with read replicas | Write to master, read from replicas |
| Cache | Redis | Session storage, ticket availability cache |
| Storage | Object storage (S3/Cloudflare R2) | Event images, generated QR codes |
| Email | Amazon SES / SendGrid | Transactional email at scale |

**CI/CD**: Deploy via GitHub Actions → rsync to production servers → zero-downtime with rolling restarts of PHP-FPM.
