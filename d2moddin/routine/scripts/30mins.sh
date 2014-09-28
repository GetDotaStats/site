#!/bin/bash
cd /home/www-getdotastats/d2moddin/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./mongodb.php
echo "-------------------<br />"
echo "Run at: $(date -u)"
