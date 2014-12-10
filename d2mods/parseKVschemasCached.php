<?php
require_once('../global_functions.php');
require_once('./functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    echo '
        <head>
            <link href="//getdotastats.com/bootstrap/css/bootstrap.min.css" rel="stylesheet">
            <link href="//getdotastats.com/getdotastats.css?10" rel="stylesheet">
        </head>
    ';

    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        if ($db) {
            $memcache = new Memcache;
            $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

            ////////////////////////
            // Grab regular items
            ////////////////////////
            $regularAbilities = $memcache->get('dota2_regular_abilities_schema');
            if (!$regularAbilities) {
                $regularAbilitiesSQL = $db->q('SELECT `ability_id`, `ability_name` FROM `mod_abilities` WHERE `mod_id` = 0;');

                $regularAbilities = array();
                foreach ($regularAbilitiesSQL as $value) {
                    $regularAbilities[$value['ability_id']] = $value['ability_name'];
                }

                $memcache->set('dota2_regular_abilities_schema', $regularAbilities, 0, 10); //10seconds
            }
            ////////////////////////

            ////////////////////////////////
            // Ability Parsing
            ////////////////////////////////
            $schema = $db->q(
                'SELECT * FROM `mod_schemas` WHERE schema_type = "npc_abilities_custom";'
            );

            if (!empty($schema)) {
                foreach ($schema as $key_schema => $value_schema) {
                    $schemaJSON = json_decode($value_schema['schema_content'], 1);

                    foreach ($schemaJSON['DOTAAbilities'] as $key => $value) {
                        if (!empty($value['AbilityTextureName'])) {
                            echo $key . ' | ' . $value['AbilityTextureName'] . '<br />';

                            $abilityName = $key;

                            $abilityIcon = $value['AbilityTextureName'];
                            if (stristr($abilityIcon, 'recipe_')) {
                                $abilityIcon = 'recipe';
                            } else if (stristr($abilityIcon, 'item_')) {
                                $abilityIcon = str_replace('item_', '', $abilityIcon);
                            }

                            $customAbilityIcon = 1;
                            if (in_array($abilityIcon, $regularAbilities)) {
                                $customAbilityIcon = 0;
                            }

                            $db->q(
                                'INSERT INTO `mod_abilities`(`mod_id`, `ability_name`, `ability_icon`, `ability_custom_icon`)
                                    VALUES (?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE
                                    `mod_id` = VALUES(`mod_id`),
                                    `ability_name` = VALUES(`ability_name`),
                                    `ability_icon` = VALUES(`ability_icon`),
                                    `ability_custom_icon` = VALUES(`ability_custom_icon`);',
                                'sssi',
                                $value_schema['mod_id'], $abilityName, $abilityIcon, $customAbilityIcon
                            );
                        }
                    }
                }
            }
            ////////////////////////////////

            $memcache->close();
        } else {
            echo bootstrapMessage('Oh Snap', 'No DB!');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in!');
        echo '<a href="../">Go back to main site</a>';
    }
} catch (Exception $e) {
    $eMsg = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $eMsg);
}