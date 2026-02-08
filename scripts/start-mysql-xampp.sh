#!/usr/bin/env bash
# Start XAMPP MySQL on Mac (if installed in /Applications/XAMPP).
# Run: ./scripts/start-mysql-xampp.sh

XAMPP_BASE="/Applications/XAMPP/xamppfiles"

if [ ! -x "$XAMPP_BASE/bin/mysql" ]; then
  echo "XAMPP MySQL not found at $XAMPP_BASE"
  echo "Start MySQL from XAMPP Control Panel (Applications → XAMPP) instead."
  exit 1
fi

# Start MySQL via XAMPP's manager or direct binary (socket might be in /tmp or xampp/var/mysql)
if [ -x "$XAMPP_BASE/xampp" ]; then
  sudo "$XAMPP_BASE/xampp" startmysql 2>/dev/null || "$XAMPP_BASE/xampp" startmysql 2>/dev/null || true
fi

# Check if something is listening on 3306
if command -v nc &>/dev/null; then
  if nc -z 127.0.0.1 3306 2>/dev/null; then
    echo "MySQL is running on 127.0.0.1:3306"
  else
    echo "MySQL is not responding on port 3306."
    echo "Start it from XAMPP: open XAMPP app → Manage Servers → MySQL → Start"
  fi
fi
