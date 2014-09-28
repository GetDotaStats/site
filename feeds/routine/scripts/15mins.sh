#!/bin/bash
##every 15mins
cd /home/www-getdotastats/feeds/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./15mins.php
echo "-------------------<br />"
echo "Run at: $(date -u)"
