lobby_joined POST {uid, lid}
 - Call this after joining a lobby (hosts included)
# lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_hosted, lobby_pass, lobby_map

lobby_list GET {}
 - Call this to get a list of lobbies. lobby_hosted (0,1) indicates if the lobby is ready to join
# lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_hosted, lobby_pass, lobby_map, lobby_current_players

lobby_status GET {lid}
 - Call this to get details about a specific lobby. lobby_active (0,1) indicates that the lobby is still being advertised
# lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_active, lobby_hosted, lobby_pass, lobby_map, lobby_current_players

lobby_user_status GET {uid}
 - Call this to get details about the lobby a user is expected to join
# lobby_id, mod_id, workshop_id, lobby_max_players, lobby_leader, lobby_hosted, lobby_pass, lobby_map, lobby_current_players

popular_mods GET {}
 - Call this for a list of mods on the site
# modName, popularityRank, gamesLastWeek, gamesAllTime, workshopLink, steamGroup, modInfo, modDeveloperName, modDeveloperAvatar, modDateAdded, modDescription, mod_maps