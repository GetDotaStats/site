<?php
try {
    require_once('../connections/parameters.php');
    require_once('../global_functions.php');

    //ToDo: Make two columns
    //ToDo: Add jump functionality into framework

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    echo '<h2>Arcana Votes</h2>';

    if (!class_exists('curl_improved')) {
        class curl_improved
        {
            private $ch = null;
            private $page = null;

            private $isBehindProxy = null;
            private $hasEnabledProxy = false;

            public function __construct(bool $behindProxy, string $link = null)
            {
                $this->isBehindProxy = $behindProxy;

                $this->ch = curl_init();
                $this->setOptions();
                $this->setUserAgent();
                $this->setTimeOuts();

                if (!empty($link)) {
                    $this->setLink($link);
                }
            }

            public function __destruct()
            {
                $this->closeLink();
            }

            public function setLink(string $link)
            {
                curl_setopt($this->ch, CURLOPT_URL, $link);
            }

            public function getPage()
            {
                if ($this->isBehindProxy) {
                    if (!$this->hasEnabledProxy) {
                        throw new Exception('Config says we are behind proxy! We must setProxyDetails() before attempting to grab page!');
                    }
                }

                $this->page = curl_exec($this->ch);

                if (empty($this->page) || !$this->page) {
                    $this->page = false;
                }

                $this->closeLink();

                return $this->page;
            }

            public function closeLink()
            {
                if (gettype($this->ch) == 'resource') {
                    curl_close($this->ch);
                }
            }

            public function setOptions(array $options = array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_HEADER => 0, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_AUTOREFERER => 1, CURLOPT_HTTPHEADER => array('Expect:')))
            {

                if (empty($options) || !isset($options[CURLOPT_RETURNTRANSFER])) {
                    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
                }

                if (empty($options) || !isset($options[CURLOPT_HEADER])) {
                    curl_setopt($this->ch, CURLOPT_HEADER, 0);
                }

                if (empty($options) || !isset($options[CURLOPT_FOLLOWLOCATION])) {
                    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
                }

                if (empty($options) || !isset($options[CURLOPT_AUTOREFERER])) {
                    curl_setopt($this->ch, CURLOPT_AUTOREFERER, 1);
                }

                if (empty($options) || !isset($options[CURLOPT_HTTPHEADER])) {
                    curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
                }

                if (!empty($options) && is_array($options)) {
                    curl_setopt_array($this->ch, $options);
                }
            }

            public function setUserAgent(string $userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1')
            {
                empty($userAgent)
                    ? $userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1'
                    : null;

                $this->setOptions(array(CURLOPT_USERAGENT => $userAgent));
            }

            public function setTimeOuts(int $connectTimeout = 5, int $executeTimeout = 10)
            {
                empty($connectTimeout) || !is_numeric($connectTimeout)
                    ? $connectTimeout = 5
                    : null;

                empty($executeTimeout) || !is_numeric($executeTimeout)
                    ? $executeTimeout = 10
                    : null;

                $this->setOptions(array(CURLOPT_CONNECTTIMEOUT => $connectTimeout, CURLOPT_TIMEOUT => $executeTimeout));
            }

            public function setReferrer(string $referrer = 'https://google.com')
            {
                if (isset($referrer)) {
                    $this->setOptions(array(CURLOPT_REFERER => $referrer));
                } else {
                    throw new Exception('Attempted to set empty referrer!');
                }
            }

            public function setPostFields($postFields = array())
            {
                if (is_array($postFields)) {
                    $fields_string = '';
                    foreach ($postFields as $key => $value) {
                        $fields_string .= $key . '=' . $value . '&';
                    }
                    rtrim($fields_string, '&');
                    $postFields = $fields_string;
                    unset($fields_string);
                }

                if (!empty($postFields)) {
                    $this->setOptions(array(CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $postFields));
                } else {
                    throw new Exception('Attempted to POST with empty postfields!');
                }
            }

            public function setCookie($cookie)
            {
                if (!empty($cookie)) {
                    $this->setOptions(array(CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie));
                }
            }

            public function setProxyDetails(string $proxyAddress, string $proxyPort = '8080', string $proxyType = 'HTTP', string $proxyUser = NULL, string $proxyPass = NULL, int $SSLverify = 1)
            {
                if (!empty($proxyAddress)) {

                    //PORT
                    echo 'PORT: ' . $proxyPort . '<br />';
                    if (!empty($proxyPort)) {
                        $this->setOptions(array(CURLOPT_PROXYPORT => $proxyPort));
                    } else {
                        $this->setOptions(array(CURLOPT_PROXYPORT => '8080'));
                    }

                    //TYPE
                    echo 'TYPE: ' . $proxyType . '<br />';
                    if (!empty($proxyType)) {
                        $this->setOptions(array(CURLOPT_PROXYTYPE => $proxyType));
                    } else {
                        $this->setOptions(array(CURLOPT_PROXYTYPE => 'HTTP'));
                    }

                    //ADDRESS
                    echo 'ADDRESS: ' . $proxyAddress . '<br />';
                    $this->setOptions(array(CURLOPT_PROXY => $proxyAddress));

                    //AUTH
                    if (!empty($proxyUser) || !empty($proxyPass)) {
                        $proxyAuthCredentials = $proxyUser . ':' . $proxyPass;
                        echo 'AUTH: ' . $proxyAuthCredentials . '<br />';
                        $this->setOptions(array(CURLOPT_PROXYUSERPWD => $proxyAuthCredentials));
                    } else {
                        $proxyAuthCredentials = NULL;
                    }

                    /*if (isset($SSLverify)) {
                        echo 'SSL VERIFY: ' . $SSLverify . '<br />';
                        $this->setOptions(array(CURLOPT_SSL_VERIFYHOST => $SSLverify));
                    } else {
                        $this->setOptions(array(CURLOPT_SSL_VERIFYHOST => 2));
                    }*/

                    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);


                    $this->hasEnabledProxy = true;
                } else {
                    throw new Exception('Attempted to user Proxy with empty proxy address!');
                }
            }
        }
    }

    function change_booleans_to_numbers(Array $data)
    {
        function converter(&$value, $key)
        {
            if (is_bool($value)) {
                $value = ($value ? 1 : 0);
            }
        }

        array_walk_recursive($data, 'converter');
        return $data;
    }

    $heroes = array(
        0 => array(
            'name_raw' => 'blank',
            'name_formatted' => 'No Hero',
            'pic' => 'aaa_blank'
        ),
        1 => array(
            'name_raw' => 'npc_dota_hero_antimage',
            'name_formatted' => 'Anti Mage',
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
            'name_formatted' => 'Earth Shaker',
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
            'name_formatted' => 'Wraith King',
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
            'name_formatted' => 'Centaur',
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

    $apiEndpoint = 'http://www.dota2.com/webapi/IDOTA2Events/GetArcanaVotes/v0001?event_id=14';


    $arcanaVotes = $memcached->get('d2_arcana_votes');
    if (!$arcanaVotes) {
        $curlObject = new curl_improved($behindProxy, $apiEndpoint);
        //$curlObject->setProxyDetails($proxyDetails['address'], $proxyDetails['port'], $proxyDetails['type'], $proxyDetails['user'], $proxyDetails['pass']);
        $arcanaVotes = $curlObject->getPage();

        $arcanaVotes = json_decode($arcanaVotes, true);
        //$arcanaVotes = change_booleans_to_numbers($page);

        if (empty($arcanaVotes)) throw new Exception("Couldn't get arcana votes!");

        $memcached->set('d2_arcana_votes', $arcanaVotes, 5 * 60);
    }

    $orderedRounds = array();
    $orderedRounds['meta']['round_time_remaining'] = $arcanaVotes['round_time_remaining'];
    $orderedRounds['meta']['round_number'] = $arcanaVotes['round_number'];
    $orderedRounds['meta']['voting_state'] = $arcanaVotes['voting_state'];
    foreach ($arcanaVotes['matches'] as $key => $value) {
        $orderedRounds[$value['round_number']]['voting_state'] = $value['voting_state'];
        $orderedRounds[$value['round_number']]['is_votes_hidden'] = !empty($value['is_votes_hidden']) ? 1 : 0;
        $orderedRounds[$value['round_number']]['calibration_time_remaining'] = $value['calibration_time_remaining'];

        if (!empty($value['hero_id_1'])) {
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['match_id'] = $value['match_id'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['hero_id_0'] = $value['hero_id_0'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['hero_id_1'] = $value['hero_id_1'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['hero_seeding_0'] = $value['hero_seeding_0'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['hero_seeding_1'] = $value['hero_seeding_1'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['vote_count_0'] = $value['vote_count_0'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['vote_count_1'] = $value['vote_count_1'];
        } else {
            $orderedRounds[$value['round_number']]['byes'][$value['hero_seeding_0']] = array(
                'match_id' => $value['match_id'],
                'hero_id_0' => $value['hero_id_0'],
                'hero_seeding_0' => $value['hero_seeding_0'],
            );

            ksort($orderedRounds[$value['round_number']]['byes']);
        }
    }

    foreach ($orderedRounds as $key => $roundData) {
        $skipKeys = array('meta');
        if (in_array($key, $skipKeys)) {
            $roundTimeRemaining = secs_to_h($roundData['round_time_remaining']);

            echo "<table style='border: 1px; padding: 5px; border-spacing: 2px;'>";

            echo "<tr>
                        <th>Round Time Remaining</th>
                        <td>{$roundTimeRemaining}</td>
                    </tr>";

            echo "<tr>
                        <th>Current Round</th>
                        <td>{$roundData['round_number']}</td>
                    </tr>";

            echo "<tr>
                        <th>Voting Enabled</th>
                        <td>{$roundData['voting_state']}</td>
                    </tr>";

            echo "</table>";

            continue;
        }

        if (!empty($roundData['matches'])) {
            if ($key == $orderedRounds['meta']['round_number']) {
                echo "<h2 class='boldRedText'>Round #{$key}</h2>";
            } else {
                echo "<h2>Round #{$key}</h2>";
            }

            echo "<table style='border: 1px; padding: 5px; border-spacing: 2px;'>";
            foreach ($roundData['matches'] as $key2 => $matchData) {
                if (!empty($matchData['hero_id_0']) && is_numeric($matchData['hero_id_0'])) {
                    if (!empty($heroes[$matchData['hero_id_0']])) {
                        $hero0_name = $heroes[$matchData['hero_id_0']]['name_formatted'];
                        $hero0_img = $heroes[$matchData['hero_id_0']]['pic'] . '.png';
                    } else {
                        $hero0_name = 'Unknown';
                        $hero0_img = $heroes[0]['pic'] . '.png';
                    }
                } else {
                    $hero0_name = 'Bye';
                    $hero0_img = $heroes[0]['pic'] . '.png';
                }
                $hero0_img = $imageCDN . '/images/heroes/' . $hero0_img;
                $hero0_img = "<img width='54' height='30' alt='Image for hero #{$matchData['hero_id_0']}' src='{$hero0_img}' />";
                $hero0_votes = number_format($matchData['vote_count_0']);
                $hero0_seed = $matchData['hero_seeding_0'];

                if (!empty($matchData['hero_id_1']) && is_numeric($matchData['hero_id_1'])) {
                    if (!empty($heroes[$matchData['hero_id_1']])) {
                        $hero1_name = $heroes[$matchData['hero_id_1']]['name_formatted'];
                        $hero1_img = $heroes[$matchData['hero_id_1']]['pic'] . '.png';
                    } else {
                        $hero1_name = 'Unknown';
                        $hero1_img = $heroes[0]['pic'] . '.png';
                    }
                } else {
                    $hero1_name = 'Bye';
                    $hero1_img = $heroes[0]['pic'] . '.png';
                }
                $hero1_img = $imageCDN . '/images/heroes/' . $hero1_img;
                $hero1_img = "<img width='54' height='30' alt='Image for hero #{$matchData['hero_id_1']}' src='{$hero1_img}' />";
                $hero1_votes = number_format($matchData['vote_count_1']);
                $hero1_seed = $matchData['hero_seeding_1'];

                $matchData['vote_count_0'] > $matchData['vote_count_1']
                    ? $hero0_name = "<span class='boldGreenText'><em>{$hero0_name}</em></span>"
                    : null;

                $matchData['vote_count_1'] > $matchData['vote_count_0']
                    ? $hero1_name = "<span class='boldGreenText'><em>{$hero1_name}</em></span>"
                    : null;

                //echo "<tr><th colspan='3' align='center'>Match #{$key2}</th></tr>";

                echo "<tr>
                        <td align='left' width='140px'>{$hero0_name}</td>
                        <td align='center'><strong>vs.</strong></td>
                        <td align='right' width='140px'>{$hero1_name}</td>
                    </tr>";

                echo "<tr>
                        <td align='center'>{$hero0_img}</td>
                        <td>&nbsp;</td>
                        <td align='center'>{$hero1_img}</td>
                    </tr>";

                echo "<tr>
                        <td align='left'>{$hero0_votes}</td>
                        <td align='center'><strong>votes</strong></td>
                        <td align='right'>{$hero1_votes}</td>
                    </tr>";


                if (($matchData['vote_count_0'] + $matchData['vote_count_1']) > 0) {
                    $votesMax = $matchData['vote_count_0'] + $matchData['vote_count_1'];
                    $votePercentageHero0 = number_format($matchData['vote_count_0'] / ($matchData['vote_count_0'] + $matchData['vote_count_1']) * 100, 0);
                    $votePercentageHero1 = number_format($matchData['vote_count_1'] / ($matchData['vote_count_0'] + $matchData['vote_count_1']) * 100, 0);

                    $hero0classMeet = $matchData['vote_count_0'] > $matchData['vote_count_1']
                        ? ($matchData['vote_count_1'] == 0
                            ? 'green arcana-progress-bar-meet-right-special'
                            : 'green arcana-progress-bar-meet-right')
                        : ($matchData['vote_count_1'] == 0
                            ? 'red arcana-progress-bar-meet-right-special'
                            : 'red arcana-progress-bar-meet-right');

                    $hero1classMeet = $matchData['vote_count_1'] > $matchData['vote_count_0']
                        ? ($matchData['vote_count_0'] == 0
                            ? 'green arcana-progress-bar-meet-left-special'
                            : 'green arcana-progress-bar-meet-left')
                        : ($matchData['vote_count_0'] == 0
                            ? 'red arcana-progress-bar-meet-left-special'
                            : 'red arcana-progress-bar-meet-left');

                    if ($matchData['vote_count_0'] == 0) $hero0classMeet = 'arcana-progress-bar-hide';
                    if ($matchData['vote_count_1'] == 0) $hero1classMeet = 'arcana-progress-bar-hide';

                    echo "<tr>
                            <td colspan='3'>
                                <div class='arcana-progress-bar'>
                                    <span class='{$hero0classMeet}' style='width: {$votePercentageHero0}%;'>{$votePercentageHero0}%</span>
                                    <span class='{$hero1classMeet}' style='width: {$votePercentageHero1}%;'>{$votePercentageHero1}%</span>
                                </div>
                            </td>
                        </tr>";
                }

                /*echo "<tr>
                        <td align='left'>{$hero0_seed}</td>
                        <td align='center'><strong>seed</strong></td>
                        <td align='right'>{$hero1_seed}</td>
                    </tr>";*/

                echo "<tr>
                        <td>&nbsp;</td>
                    </tr>";
            }
            echo "</table>";

            if (!empty($roundData['byes'])) {
                $numByes = count($roundData['byes']);
                //$rowNames = "<tr><th>&nbsp;</th>";
                $rowImages = "<tr><th>&nbsp;</th>";
                //$rowSeed = "<tr><th align='left'>Seed</th>";

                echo "<h3>Byes <small>({$numByes})</small></h3>";

                echo "<table style='border: 1px; padding: 5px; border-spacing: 2px;'>";

                foreach ($roundData['byes'] as $key2 => $byeData) {
                    if (!empty($byeData['hero_id_0']) && is_numeric($byeData['hero_id_0'])) {
                        if (!empty($heroes[$byeData['hero_id_0']])) {
                            $hero0_name = $heroes[$byeData['hero_id_0']]['name_formatted'];
                            $hero0_img = $heroes[$byeData['hero_id_0']]['pic'] . '.png';
                        } else {
                            $hero0_name = 'Unknown';
                            $hero0_img = $heroes[0]['pic'] . '.png';
                        }
                    } else {
                        $hero0_name = 'Bye (#' . $byeData['match_id'] . ')';
                        $hero0_img = $heroes[0]['pic'] . '.png';
                    }
                    $hero0_img = $imageCDN . '/images/heroes/' . $hero0_img;
                    $hero0_img = "<img width='54' height='30' title='{$hero0_name}' alt='Image for hero #{$byeData['hero_id_0']}' src='{$hero0_img}' />";
                    $hero0_seed = $byeData['hero_seeding_0'];

                    //$rowNames .= "<td align='center' width='100px'>{$hero0_name}</td>";
                    $rowImages .= "<td align='center'>{$hero0_img} </td>";
                    //$rowSeed .= "<td align='center'>{$hero0_seed}</td>";
                }

                //echo $rowNames . '</tr>' . $rowImages . '</tr>' . $rowSeed . '</tr></table>';
                echo $rowImages . '</tr></table>';
                echo '<hr />';
            }
        }
    }


    /*echo '<pre>';
    print_r($orderedRounds);
    echo '</pre>';*/


} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}