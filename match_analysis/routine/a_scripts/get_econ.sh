#!/bin/bash
cd /home/www-dota2/match_analysis/routine/daily/
echo "Run at: $(date -u)"
echo "-------------------"
echo "Grabbing data for 1,000,000 matches:"
echo "-------------------"
./get_econ.php
echo "-------------------"
echo "Run at: $(date -u)"
