#!/bin/bash
##every hour
cd /home/www-dota2/skill/routine/daily/
echo "Run at: $(date -u)"
~/gd_md_stop
echo "-------------------"
./daily_queries.php
echo "-------------------"
~/gd_md_start
echo "Run at: $(date -u)"
