#!/bin/bash
cd /home/www-getdotastats/d2mods/api/
echo "Run at: $(date -u)<br />"
echo "-------------------<br />"
wget --cache=off -O /home/www-getdotastats/d2mods/api/lobby_version.txt https://raw.githubusercontent.com/GetDotaStats/GetDotaLobby/lobbybrowser/version.txt
echo "-------------------<br />"
echo "Run at: $(date -u)"
