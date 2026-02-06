#!/bin/bash
# Fix chrome-sandbox SUID permissions for Electron on Linux
chmod 4755 /opt/Vaxtly/chrome-sandbox || true

# Allow the app to cache config/routes/views for performance
chmod 777 /opt/Vaxtly/resources/build/app/bootstrap/cache || true
