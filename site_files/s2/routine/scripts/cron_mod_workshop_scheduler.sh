#!/bin/bash
cd /home/www-getdotastats/s2/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./cron_workshop_scheduler.php
echo "-------------------<br />"
echo "Ended at: $(date -u)"
