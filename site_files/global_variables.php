<?php
$CSParray = array(
    'default-src' => array(
        "'self'",
        "getdotastats.com"
    ),
    'connect-src' => array(
        "'self'",
        "static.getdotastats.com",
        "getdotastats.com"
    ),
    'style-src' => array(
        "'self'",
        "'unsafe-inline'",
        "static.getdotastats.com",
        "getdotastats.com",
        "ajax.googleapis.com",
        "*.google.com"
    ),
    'script-src' => array(
        "'self'",
        "'unsafe-eval'",
        "'unsafe-inline'",
        "data:",
        "static.getdotastats.com",
        "getdotastats.com",
        "oss.maxcdn.com",
        "ajax.googleapis.com",
        "*.google.com",
        "*.google-analytics.com",
        "*.changetip.com"
    ),
    'img-src' => array(
        "'self'",
        "data:",
        "static.getdotastats.com",
        "getdotastats.com",
        "dota2.photography",
        "media.steampowered.com",
        "ajax.googleapis.com",
        "cdn.akamai.steamstatic.com",
        "cdn.dota2.com",
        "*.gstatic.com",
        "*.akamaihd.net",
        "*.google-analytics.com",
        "*.steamusercontent.com",
        "steamcdn-a.akamaihd.net",
        "montools.com"
    ),
    'font-src' => array(
        "'self'",
        "static.getdotastats.com",
        "getdotastats.com",
    ),
    'frame-src' => array(
        "'self'",
        "static.getdotastats.com",
        "getdotastats.com",
        "*.changetip.com"
    ),
    'object-src' => array(
        "'none'"
    ),
    'media-src' => array(
        "'none'"
    ),
    'report-uri' => array(
        "./csp_reports.php"
    ),
);

$heroes = array(
    0 => array(
        'name_raw' => 'blank',
        'name_formatted' => 'No Hero',
        'pic' => 'aaa_blank'
    ),
    1 => array(
        'name_raw' => 'npc_dota_hero_antimage',
        'name_formatted' => 'Anti-Mage',
        'pic' => 'anti-mage'
    ),
    2 => array(
        'name_raw' => 'npc_dota_hero_axe',
        'name_formatted' => 'Axe',
        'pic' => 'axe'
    ),
    3 => array(
        'name_raw' => 'npc_dota_hero_bane',
        'name_formatted' => 'Bane',
        'pic' => 'bane'
    ),
    4 => array(
        'name_raw' => 'npc_dota_hero_bloodseeker',
        'name_formatted' => 'Bloodseeker',
        'pic' => 'bloodseeker'
    ),
    5 => array(
        'name_raw' => 'npc_dota_hero_crystal_maiden',
        'name_formatted' => 'Crystal Maiden',
        'pic' => 'crystal-maiden'
    ),
    6 => array(
        'name_raw' => 'npc_dota_hero_drow_ranger',
        'name_formatted' => 'Drow Ranger',
        'pic' => 'drow-ranger'
    ),
    7 => array(
        'name_raw' => 'npc_dota_hero_earthshaker',
        'name_formatted' => 'Earthshaker',
        'pic' => 'earthshaker'
    ),
    8 => array(
        'name_raw' => 'npc_dota_hero_juggernaut',
        'name_formatted' => 'Juggernaut',
        'pic' => 'juggernaut'
    ),
    9 => array(
        'name_raw' => 'npc_dota_hero_mirana',
        'name_formatted' => 'Mirana',
        'pic' => 'mirana'
    ),
    10 => array(
        'name_raw' => 'npc_dota_hero_morphling',
        'name_formatted' => 'Morphling',
        'pic' => 'morphling'
    ),
    11 => array(
        'name_raw' => 'npc_dota_hero_nevermore',
        'name_formatted' => 'Shadow Fiend',
        'pic' => 'shadow-fiend'
    ),
    12 => array(
        'name_raw' => 'npc_dota_hero_phantom_lancer',
        'name_formatted' => 'Phantom Lancer',
        'pic' => 'phantom-lancer'
    ),
    13 => array(
        'name_raw' => 'npc_dota_hero_puck',
        'name_formatted' => 'Puck',
        'pic' => 'puck'
    ),
    14 => array(
        'name_raw' => 'npc_dota_hero_pudge',
        'name_formatted' => 'Pudge',
        'pic' => 'pudge'
    ),
    15 => array(
        'name_raw' => 'npc_dota_hero_razor',
        'name_formatted' => 'Razor',
        'pic' => 'razor'
    ),
    16 => array(
        'name_raw' => 'npc_dota_hero_sand_king',
        'name_formatted' => 'Sand King',
        'pic' => 'sand-king'
    ),
    17 => array(
        'name_raw' => 'npc_dota_hero_storm_spirit',
        'name_formatted' => 'Storm Spirit',
        'pic' => 'storm-spirit'
    ),
    18 => array(
        'name_raw' => 'npc_dota_hero_sven',
        'name_formatted' => 'Sven',
        'pic' => 'sven'
    ),
    19 => array(
        'name_raw' => 'npc_dota_hero_tiny',
        'name_formatted' => 'Tiny',
        'pic' => 'tiny'
    ),
    20 => array(
        'name_raw' => 'npc_dota_hero_vengefulspirit',
        'name_formatted' => 'Vengeful Spirit',
        'pic' => 'vengeful-spirit'
    ),
    21 => array(
        'name_raw' => 'npc_dota_hero_windrunner',
        'name_formatted' => 'Wind Runner',
        'pic' => 'windranger'
    ),
    22 => array(
        'name_raw' => 'npc_dota_hero_zuus',
        'name_formatted' => 'Zeus',
        'pic' => 'zeus'
    ),
    23 => array(
        'name_raw' => 'npc_dota_hero_kunkka',
        'name_formatted' => 'Kunkka',
        'pic' => 'kunkka'
    ),
    25 => array(
        'name_raw' => 'npc_dota_hero_lina',
        'name_formatted' => 'Lina',
        'pic' => 'lina'
    ),
    26 => array(
        'name_raw' => 'npc_dota_hero_lion',
        'name_formatted' => 'Lion',
        'pic' => 'lion'
    ),
    27 => array(
        'name_raw' => 'npc_dota_hero_shadow_shaman',
        'name_formatted' => 'Shadow Shaman',
        'pic' => 'shadow-shaman'
    ),
    28 => array(
        'name_raw' => 'npc_dota_hero_slardar',
        'name_formatted' => 'Slardar',
        'pic' => 'slardar'
    ),
    29 => array(
        'name_raw' => 'npc_dota_hero_tidehunter',
        'name_formatted' => 'Tidehunter',
        'pic' => 'tidehunter'
    ),
    30 => array(
        'name_raw' => 'npc_dota_hero_witch_doctor',
        'name_formatted' => 'Witch Doctor',
        'pic' => 'witch-doctor'
    ),
    31 => array(
        'name_raw' => 'npc_dota_hero_lich',
        'name_formatted' => 'Lich',
        'pic' => 'lich'
    ),
    32 => array(
        'name_raw' => 'npc_dota_hero_riki',
        'name_formatted' => 'Riki',
        'pic' => 'riki'
    ),
    33 => array(
        'name_raw' => 'npc_dota_hero_enigma',
        'name_formatted' => 'Enigma',
        'pic' => 'enigma'
    ),
    34 => array(
        'name_raw' => 'npc_dota_hero_tinker',
        'name_formatted' => 'Tinker',
        'pic' => 'tinker'
    ),
    35 => array(
        'name_raw' => 'npc_dota_hero_sniper',
        'name_formatted' => 'Sniper',
        'pic' => 'sniper'
    ),
    36 => array(
        'name_raw' => 'npc_dota_hero_necrolyte',
        'name_formatted' => 'Necrophos',
        'pic' => 'necrophos'
    ),
    37 => array(
        'name_raw' => 'npc_dota_hero_warlock',
        'name_formatted' => 'Warlock',
        'pic' => 'warlock'
    ),
    38 => array(
        'name_raw' => 'npc_dota_hero_beastmaster',
        'name_formatted' => 'Beastmaster',
        'pic' => 'beastmaster'
    ),
    39 => array(
        'name_raw' => 'npc_dota_hero_queenofpain',
        'name_formatted' => 'Queen of Pain',
        'pic' => 'queen-of-pain'
    ),
    40 => array(
        'name_raw' => 'npc_dota_hero_venomancer',
        'name_formatted' => 'Venomancer',
        'pic' => 'venomancer'
    ),
    41 => array(
        'name_raw' => 'npc_dota_hero_faceless_void',
        'name_formatted' => 'Faceless Void',
        'pic' => 'faceless-void'
    ),
    42 => array(
        'name_raw' => 'npc_dota_hero_skeleton_king',
        'name_formatted' => 'Skeleton King',
        'pic' => 'skeleton-king'
    ),
    43 => array(
        'name_raw' => 'npc_dota_hero_death_prophet',
        'name_formatted' => 'Death Prophet',
        'pic' => 'death-prophet'
    ),
    44 => array(
        'name_raw' => 'npc_dota_hero_phantom_assassin',
        'name_formatted' => 'Phantom Assassin',
        'pic' => 'phantom-assassin'
    ),
    45 => array(
        'name_raw' => 'npc_dota_hero_pugna',
        'name_formatted' => 'Pugna',
        'pic' => 'pugna'
    ),
    46 => array(
        'name_raw' => 'npc_dota_hero_templar_assassin',
        'name_formatted' => 'Templar Assassin',
        'pic' => 'templar-assassin'
    ),
    47 => array(
        'name_raw' => 'npc_dota_hero_viper',
        'name_formatted' => 'Viper',
        'pic' => 'viper'
    ),
    48 => array(
        'name_raw' => 'npc_dota_hero_luna',
        'name_formatted' => 'Luna',
        'pic' => 'luna'
    ),
    49 => array(
        'name_raw' => 'npc_dota_hero_dragon_knight',
        'name_formatted' => 'Dragon Knight',
        'pic' => 'dragon-knight'
    ),
    50 => array(
        'name_raw' => 'npc_dota_hero_dazzle',
        'name_formatted' => 'Dazzle',
        'pic' => 'dazzle'
    ),
    51 => array(
        'name_raw' => 'npc_dota_hero_rattletrap',
        'name_formatted' => 'Clockwerk',
        'pic' => 'clockwerk'
    ),
    52 => array(
        'name_raw' => 'npc_dota_hero_leshrac',
        'name_formatted' => 'Leshrac',
        'pic' => 'leshrac'
    ),
    53 => array(
        'name_raw' => 'npc_dota_hero_furion',
        'name_formatted' => 'Nature\'s Prophet',
        'pic' => 'natures-prophet'
    ),
    54 => array(
        'name_raw' => 'npc_dota_hero_life_stealer',
        'name_formatted' => 'Lifestealer',
        'pic' => 'lifestealer'
    ),
    55 => array(
        'name_raw' => 'npc_dota_hero_dark_seer',
        'name_formatted' => 'Dark Seer',
        'pic' => 'dark-seer'
    ),
    56 => array(
        'name_raw' => 'npc_dota_hero_clinkz',
        'name_formatted' => 'Clinkz',
        'pic' => 'clinkz'
    ),
    57 => array(
        'name_raw' => 'npc_dota_hero_omniknight',
        'name_formatted' => 'Omniknight',
        'pic' => 'omniknight'
    ),
    58 => array(
        'name_raw' => 'npc_dota_hero_enchantress',
        'name_formatted' => 'Enchantress',
        'pic' => 'enchantress'
    ),
    59 => array(
        'name_raw' => 'npc_dota_hero_huskar',
        'name_formatted' => 'Huskar',
        'pic' => 'huskar'
    ),
    60 => array(
        'name_raw' => 'npc_dota_hero_night_stalker',
        'name_formatted' => 'Night Stalker',
        'pic' => 'night-stalker'
    ),
    61 => array(
        'name_raw' => 'npc_dota_hero_broodmother',
        'name_formatted' => 'Broodmother',
        'pic' => 'broodmother'
    ),
    62 => array(
        'name_raw' => 'npc_dota_hero_bounty_hunter',
        'name_formatted' => 'Bounty Hunter',
        'pic' => 'bounty-hunter'
    ),
    63 => array(
        'name_raw' => 'npc_dota_hero_weaver',
        'name_formatted' => 'Weaver',
        'pic' => 'weaver'
    ),
    64 => array(
        'name_raw' => 'npc_dota_hero_jakiro',
        'name_formatted' => 'Jakiro',
        'pic' => 'jakiro'
    ),
    65 => array(
        'name_raw' => 'npc_dota_hero_batrider',
        'name_formatted' => 'Batrider',
        'pic' => 'batrider'
    ),
    66 => array(
        'name_raw' => 'npc_dota_hero_chen',
        'name_formatted' => 'Chen',
        'pic' => 'chen'
    ),
    67 => array(
        'name_raw' => 'npc_dota_hero_spectre',
        'name_formatted' => 'Spectre',
        'pic' => 'spectre'
    ),
    68 => array(
        'name_raw' => 'npc_dota_hero_ancient_apparition',
        'name_formatted' => 'Ancient Apparition',
        'pic' => 'ancient-apparition'
    ),
    69 => array(
        'name_raw' => 'npc_dota_hero_doom_bringer',
        'name_formatted' => 'Doom',
        'pic' => 'doom'
    ),
    70 => array(
        'name_raw' => 'npc_dota_hero_ursa',
        'name_formatted' => 'Ursa',
        'pic' => 'ursa'
    ),
    71 => array(
        'name_raw' => 'npc_dota_hero_spirit_breaker',
        'name_formatted' => 'Spirit Breaker',
        'pic' => 'spirit-breaker'
    ),
    72 => array(
        'name_raw' => 'npc_dota_hero_gyrocopter',
        'name_formatted' => 'Gyrocopter',
        'pic' => 'gyrocopter'
    ),
    73 => array(
        'name_raw' => 'npc_dota_hero_alchemist',
        'name_formatted' => 'Alchemist',
        'pic' => 'alchemist'
    ),
    74 => array(
        'name_raw' => 'npc_dota_hero_invoker',
        'name_formatted' => 'Invoker',
        'pic' => 'invoker'
    ),
    75 => array(
        'name_raw' => 'npc_dota_hero_silencer',
        'name_formatted' => 'Silencer',
        'pic' => 'silencer'
    ),
    76 => array(
        'name_raw' => 'npc_dota_hero_obsidian_destroyer',
        'name_formatted' => 'Outworld Devourer',
        'pic' => 'outworld-devourer'
    ),
    77 => array(
        'name_raw' => 'npc_dota_hero_lycan',
        'name_formatted' => 'Lycan',
        'pic' => 'lycan'
    ),
    78 => array(
        'name_raw' => 'npc_dota_hero_brewmaster',
        'name_formatted' => 'Brewmaster',
        'pic' => 'brewmaster'
    ),
    79 => array(
        'name_raw' => 'npc_dota_hero_shadow_demon',
        'name_formatted' => 'Shadow Demon',
        'pic' => 'shadow-demon'
    ),
    80 => array(
        'name_raw' => 'npc_dota_hero_lone_druid',
        'name_formatted' => 'Lone Druid',
        'pic' => 'lone-druid'
    ),
    81 => array(
        'name_raw' => 'npc_dota_hero_chaos_knight',
        'name_formatted' => 'Chaos Knight',
        'pic' => 'chaos-knight'
    ),
    82 => array(
        'name_raw' => 'npc_dota_hero_meepo',
        'name_formatted' => 'Meepo',
        'pic' => 'meepo'
    ),
    83 => array(
        'name_raw' => 'npc_dota_hero_treant',
        'name_formatted' => 'Treant Protector',
        'pic' => 'treant-protector'
    ),
    84 => array(
        'name_raw' => 'npc_dota_hero_ogre_magi',
        'name_formatted' => 'Ogre Magi',
        'pic' => 'ogre-magi'
    ),
    85 => array(
        'name_raw' => 'npc_dota_hero_undying',
        'name_formatted' => 'Undying',
        'pic' => 'undying'
    ),
    86 => array(
        'name_raw' => 'npc_dota_hero_rubick',
        'name_formatted' => 'Rubick',
        'pic' => 'rubick'
    ),
    87 => array(
        'name_raw' => 'npc_dota_hero_disruptor',
        'name_formatted' => 'Disruptor',
        'pic' => 'disruptor'
    ),
    88 => array(
        'name_raw' => 'npc_dota_hero_nyx_assassin',
        'name_formatted' => 'Nyx Assassin',
        'pic' => 'nyx-assassin'
    ),
    89 => array(
        'name_raw' => 'npc_dota_hero_naga_siren',
        'name_formatted' => 'Naga Siren',
        'pic' => 'naga-siren'
    ),
    90 => array(
        'name_raw' => 'npc_dota_hero_keeper_of_the_light',
        'name_formatted' => 'Keeper of the Light',
        'pic' => 'keeper-of-the-light'
    ),
    91 => array(
        'name_raw' => 'npc_dota_hero_wisp',
        'name_formatted' => 'Wisp',
        'pic' => 'wisp'
    ),
    92 => array(
        'name_raw' => 'npc_dota_hero_visage',
        'name_formatted' => 'Visage',
        'pic' => 'visage'
    ),
    93 => array(
        'name_raw' => 'npc_dota_hero_slark',
        'name_formatted' => 'Slark',
        'pic' => 'slark'
    ),
    94 => array(
        'name_raw' => 'npc_dota_hero_medusa',
        'name_formatted' => 'Medusa',
        'pic' => 'medusa'
    ),
    95 => array(
        'name_raw' => 'npc_dota_hero_troll_warlord',
        'name_formatted' => 'Troll Warlord',
        'pic' => 'troll-warlord'
    ),
    96 => array(
        'name_raw' => 'npc_dota_hero_centaur',
        'name_formatted' => 'Centaur Warrunner',
        'pic' => 'centaur-warrunner'
    ),
    97 => array(
        'name_raw' => 'npc_dota_hero_magnataur',
        'name_formatted' => 'Magnus',
        'pic' => 'magnus'
    ),
    98 => array(
        'name_raw' => 'npc_dota_hero_shredder',
        'name_formatted' => 'Timbersaw',
        'pic' => 'timbersaw'
    ),
    99 => array(
        'name_raw' => 'npc_dota_hero_bristleback',
        'name_formatted' => 'Bristleback',
        'pic' => 'bristleback'
    ),
    100 => array(
        'name_raw' => 'npc_dota_hero_tusk',
        'name_formatted' => 'Tusk',
        'pic' => 'tusk'
    ),
    101 => array(
        'name_raw' => 'npc_dota_hero_skywrath_mage',
        'name_formatted' => 'Skywrath Mage',
        'pic' => 'skywrath-mage'
    ),
    102 => array(
        'name_raw' => 'npc_dota_hero_abaddon',
        'name_formatted' => 'Abaddon',
        'pic' => 'abaddon'
    ),
    103 => array(
        'name_raw' => 'npc_dota_hero_elder_titan',
        'name_formatted' => 'Elder Titan',
        'pic' => 'elder-titan'
    ),
    104 => array(
        'name_raw' => 'npc_dota_hero_legion_commander',
        'name_formatted' => 'Legion Commander',
        'pic' => 'legion-commander'
    ),
    105 => array(
        'name_raw' => 'npc_dota_hero_techies',
        'name_formatted' => 'Techies',
        'pic' => 'techies'
    ),
    106 => array(
        'name_raw' => 'npc_dota_hero_ember_spirit',
        'name_formatted' => 'Ember Spirit',
        'pic' => 'ember-spirit'
    ),
    107 => array(
        'name_raw' => 'npc_dota_hero_earth_spirit',
        'name_formatted' => 'Earth Spirit',
        'pic' => 'earth-spirit'
    ),
    109 => array(
        'name_raw' => 'npc_dota_hero_terrorblade',
        'name_formatted' => 'Terrorblade',
        'pic' => 'terrorblade'
    ),
    110 => array(
        'name_raw' => 'npc_dota_hero_phoenix',
        'name_formatted' => 'Phoenix',
        'pic' => 'phoenix'
    ),
    111 => array(
        'name_raw' => 'npc_dota_hero_oracle',
        'name_formatted' => 'Oracle',
        'pic' => 'oracle'
    ),
    112 => array(
        'name_raw' => 'npc_dota_hero_winter_wyvern',
        'name_formatted' => 'Winter Wyvern',
        'pic' => 'winter-wyvern'
    ),
    113 => array(
        'name_raw' => 'npc_dota_hero_arc_warden',
        'name_formatted' => 'Arc Warden',
        'pic' => 'arc-warden'
    )
);
