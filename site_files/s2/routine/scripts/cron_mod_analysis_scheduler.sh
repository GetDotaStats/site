#!/bin/bash
cd /home/www-getdotastats/s2/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./cron_match_flags.php
echo "-------------------<br />"
./custom_match_game_values.php
echo "-------------------<br />"
#./custom_match_player_values.php
#echo "-------------------<br />"
echo "Ended at: $(date -u)"
