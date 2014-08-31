#!/bin/bash
cd /home/www-getdotastats/d2mods/landing/
echo "-------------------<br />" >> /home/www-getdotastats/d2mods/live-log.html 2>&1
echo "Run at: $(date -u)<br />" >> /home/www-getdotastats/d2mods/live-log.html 2>&1
./live.php >> /home/www-getdotastats/d2mods/live-log.html 2>&1
