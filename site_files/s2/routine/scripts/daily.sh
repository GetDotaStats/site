#!/bin/bash
cd /home/www-getdotastats/s2/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./workshop.php
echo "-------------------<br />"
./custom_match_flags.php
echo "-------------------<br />"
echo "Ended at: $(date -u)"
