#!/bin/bash
cd /home/www-getdotastats/hof/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./daily.php
echo "-------------------<br />"
echo "Run at: $(date -u)"
