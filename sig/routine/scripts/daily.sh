#!/bin/bash
##every day
cd /home/www-dota2/sig/routine/php
echo "Run at: $(date -u)"
echo "<hr />"
./gds_sig_st.php
echo "<hr />"
echo "Run at: $(date -u)"
