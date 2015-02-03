#!/bin/bash
cd /home/www-getdotastats/d2mods/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./10minute.php
echo "-------------------<br />"
echo "Run at: $(date -u)"
