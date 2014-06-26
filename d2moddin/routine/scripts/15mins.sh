#!/bin/bash
##every 15mins
cd /home/www-getdotastats-staging/d2moddin/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./15mins.php
echo "-------------------<br />"
echo "Run at: $(date -u)"
