<h2>lobby_joined POST {uid, lid}</h2>
- Call this after joining a lobby (hosts included)<br/>
# lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_hosted, lobby_pass, lobby_map<br/>
<br/>
<h2>lobby_list GET {}</h2>
- Call this to get a list of lobbies. lobby_hosted (0,1) indicates if the lobby is ready to join<br/>
# lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_hosted, lobby_pass, lobby_map, lobby_current_players
<br/>
<br/>
<h2>lobby_status GET {lid}</h2>
- Call this to get details about a specific lobby. lobby_active (0,1) indicates that the lobby is still being advertised
<br/>
# lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_active, lobby_hosted, lobby_pass, lobby_map, lobby_current_players
<br/>
<br/>
<h2>lobby_user_status GET {uid}</h2>
- Call this to get details about the lobby a user is expected to join<br/>
# lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_hosted, lobby_pass, lobby_map, lobby_current_players
<br/>
<br/>
<h2>popular_mods GET {}</h2>
- Call this for a list of mods on the site<br/>
# modName, popularityRank, gamesLastWeek, gamesAllTime, workshopLink, steamGroup, modInfo, modDeveloperName, modDeveloperAvatar, modDateAdded, modDescription, mod_maps
<br/>
<br/>
<h2>lobby_created GET {uid, mid, wid, map, p, mp}</h2>
* userid32, modID (GDS digit style), workshop id, map name, password, max players
- No output, so input parameters explained below<br/>