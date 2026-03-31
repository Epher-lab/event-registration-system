#!/bin/bash
# EventSys — Quick Setup Script
# Run: bash setup.sh

echo "======================================"
echo "  EventSys Setup Script"
echo "======================================"

# Check if MySQL is running
if ! command -v mysql &> /dev/null; then
    echo "[ERROR] MySQL not found. Please install MySQL/MariaDB first."
    exit 1
fi

echo ""
echo "Enter your MySQL root password (leave blank if none):"
read -s MYSQL_PASS

if [ -z "$MYSQL_PASS" ]; then
    MYSQL_CMD="mysql -u root"
else
    MYSQL_CMD="mysql -u root -p$MYSQL_PASS"
fi

echo ""
echo "[1/3] Creating database and tables..."
$MYSQL_CMD < sql/event_system.sql

if [ $? -eq 0 ]; then
    echo "  ✓ Database 'event_system' created with demo data."
else
    echo "  ✗ Database setup failed. Check your MySQL credentials."
    exit 1
fi

echo ""
echo "[2/3] Checking PHP version..."
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION;")
if [ "$PHP_VER" -ge 7 ]; then
    echo "  ✓ PHP $PHP_VER detected."
else
    echo "  ✗ PHP 7+ required."
    exit 1
fi

echo ""
echo "[3/3] Setting permissions..."
chmod 755 . -R
echo "  ✓ Done."

echo ""
echo "======================================"
echo "  Setup Complete!"
echo "======================================"
echo ""
echo "Next steps:"
echo "  1. Copy this folder to your web root:"
echo "     sudo cp -r . /var/www/html/event_system"
echo ""
echo "  2. Update DB credentials in includes/config.php"
echo "     (DB_USER, DB_PASS)"
echo ""
echo "  3. Visit: http://localhost/event_system"
echo ""
echo "Demo login credentials:"
echo "  Admin:     admin@eventsys.com / password"
echo "  Organizer: organizer@eventsys.com / password"
echo "  Attendee:  john@example.com / password"
echo ""
