# Joker Peintre - Storage Directory

This directory stores temporary and security-related JSON files:

- rate-limit.json
- ip-blocklist.json

These files are used for:

- Rate limiting (1 request per 60 seconds per IP)
- Automatic IP blocking (captcha failure, honeypot, flood)
- Basic spam protection layer

⚠ IMPORTANT

This directory must never be publicly accessible.

A `.htaccess` file is included to deny HTTP access.

Make sure:

- The web server allows `.htaccess`
- PHP has write permissions to this directory
- File permissions are secure (recommended 640 or 600)

Recommended production permissions:

- Directory: 750
- JSON files: 640

Do not delete this directory while the contact system is active.