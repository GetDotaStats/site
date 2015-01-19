<h2>lobby_mod_list GET {r}</h2>
* region (optional)<br/>
- Call this to get a list of mods that we can make lobbies for<br/>
# error, lobby_id, mod_id, workshop_id, lobby_name, lobby_region, lobby_max_players, lobby_leader, lobby_hosted, lobby_pass, lobby_map, lobby_current_players, mod_options_enabled, mod_options
<br/>
<br/>
<h2>lobby_list GET {}</h2>
* No input<br/>
- Call this to get a list of lobbies. lobby_hosted (0,1) indicates if the lobby is ready to join<br/>
# error, lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_hosted, lobby_pass, lobby_map, lobby_current_players, lobby_options
<br/>
<br/>
<h2>lobby_joined GET {uid, lid, t}</h2>
* user id32, lobby id, secure token<br/>
- Call this after joining a lobby (hosts included)<br/>
# error, lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_hosted, lobby_pass, lobby_map<br/>
<br/>
<h2>lobby_left GET {uid, lid, t}</h2>
* user id32, lobby id, secure token<br/>
- Call this after leaving a lobby to remove a player<br/>
# error, result<br/>
* string describing what happened, string describing success, secure token<br/>
<br/>
<h2>lobby_status GET {lid}</h2>
* lobby id<br/>
- Call this to get details about a specific lobby. lobby_active (0,1) indicates that the lobby is still being advertised
<br/>
# error, lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_active, lobby_hosted, lobby_pass, lobby_map, lobby_current_players, lobby_options
<br/>
<br/>
<h2>lobby_user_status GET {uid}</h2>
* userid32<br/>
- Call this to get details about the lobby a user is expected to join<br/>
# error, lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_hosted, lobby_pass, lobby_map, lobby_current_players
<br/>
<br/>
<h2>popular_mods GET {}</h2>
* No input<br/>
- Call this for a list of mods on the site<br/>
# error, modName, popularityRank, gamesLastWeek, gamesAllTime, workshopLink, steamGroup, modInfo, modDeveloperName, modDeveloperAvatar, modDateAdded, modDescription, mod_maps
<br/>
<br/>
<h2>lobby_created GET {uid, mid, wid, map, p, mp, r, ln, lo, lv}</h2>
* userid32, modID (GDS digit style), workshop id, map name, password, max players, region, lobby name, lobby options, lobby version<br/>
- No output<br/>
# error, result, lobby_id, token<br/>
* string describing what happened, string describing success, lobby id, secure token<br/>
<br/>
<h2>lobby_update GET {lid, map, mp, r, ln, t}</h2>
* lobbyID, map name, max players, region, lobby name, secure token<br/>
- Call this to update the details of a lobby<br/>
# error, result, token<br/>
* string describing what happened, string describing success, secure token<br/>
<br/>
<h2>lobby_close GET {lid, t}</h2>
* lobbyID, secure token<br/>
- No output<br/>
# error, result<br/>
* string describing what happened, string describing success<br/>
<br/>
<h2>lobby_keep_alive GET {lid, t}</h2>
* lobbyID, secure token<br/>
- No output<br/>
# error, result<br/>
* string describing what happened, string describing success<br/>
<br/>