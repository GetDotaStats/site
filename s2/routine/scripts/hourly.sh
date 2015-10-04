#!/bin/bash
cd /home/www-getdotastats/s2/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./custom_flags.php
echo "-------------------<br />"
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./custom_game_values.php
echo "-------------------<br />"
echo "Ended at: $(date -u)"
