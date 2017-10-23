#!/usr/bin/env bash

while true; do
    echo "Run cron"
    (php artisan schedule:run >> /dev/null 2>&1) &
    sleep 60
done