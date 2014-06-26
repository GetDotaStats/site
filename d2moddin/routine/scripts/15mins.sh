#!/bin/bash
##every 15mins
cd /home/www-getdotastats-staging/d2moddin/routine/php/
echo "Run at: $(date -u)"
echo "-------------------"
./daily_queries.php
echo "-------------------"
echo "Run at: $(date -u)"
