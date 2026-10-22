#!/bin/bash
# Production fix script — run via SSH after key is restored
# DFC Talento Humano — 2026-10-22
# Usage: ssh -p 65002 -i ~/.ssh/hostinger_apirrhh u910322706@82.29.157.42 'bash -s' < docs/production-fix-2026-10-22.sh

set -e

PHP="/opt/alt/php84/usr/bin/php"
APP_DIR="/home/u910322706/domains/fidelmercadotech.com/dashboard-RRHH/backend"

cd "$APP_DIR"

echo "=== 1. Clear and rebuild route cache ==="
$PHP artisan route:clear
$PHP artisan route:cache
$PHP artisan route:list | grep attendance-stats  # verify route is cached

echo "=== 2. Assign employees.manage permission to admin role ==="
# Exact command to be confirmed by remedio-A-permisos agent:
# $PHP artisan tinker --execute="
#   \$role = \Spatie\Permission\Models\Role::where('name','admin')->firstOrFail();
#   if (!\$role->hasPermissionTo('employees.manage')) {
#     \$role->givePermissionTo('employees.manage');
#     echo 'Permission assigned';
#   } else {
#     echo 'Already had it';
#   }
# "
echo "[PLACEHOLDER] Fill in exact tinker command from remedio-A-permisos before running"

echo "=== 3. Clear config and optimize ==="
$PHP artisan config:clear
$PHP artisan config:cache

echo "=== 4. Verify attendance-stats endpoint ==="
# Replace YOUR_TOKEN_HERE with a valid admin Bearer token
curl -s -o /dev/null -w "%{http_code}\n" \
  "https://dfctalentohumano-api.fidelmercadotech.com/api/attendance-stats?from=2026-10-01&to=2026-10-22" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

echo "=== DONE ==="
