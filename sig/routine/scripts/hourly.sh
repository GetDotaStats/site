#!/bin/bash
cd /home/www-getdotastats/sig/routine/php/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
./sig_access.php
echo "-------------------<br />"
echo "Run at: $(date -u)"
