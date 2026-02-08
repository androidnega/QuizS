#!/bin/bash
# Double-click or run from terminal to start the Laravel dev server.
# Uses project directory as script location.

cd "$(dirname "$0")"

# Use XAMPP PHP if available; otherwise use system php
if [ -x "/Applications/XAMPP/xamppfiles/bin/php" ]; then
  PHP="/Applications/XAMPP/xamppfiles/bin/php"
else
  PHP="php"
fi

echo "Starting Laravel development server..."
echo "Stop with Ctrl+C. Leave this window open."
echo ""
exec "$PHP" artisan serve
