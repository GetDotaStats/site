#!/bin/bash
cd /home/www-getdotastats/s2/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./cron_execution.php
echo "-------------------<br />"
echo "Ended at: $(date -u)"
