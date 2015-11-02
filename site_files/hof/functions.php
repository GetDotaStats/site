<?php
if (!function_exists("convert_id")) {
    function convert_steamid($id, $required_output = '32')
    {
        if (empty($id)) return false;

        if (strlen($id) === 17 && $required_output == '32') {
            $converted = substr($id, 3) - 61197960265728;
        } else if (strlen($id) != 17 && $required_output == '64') {
            $converted = '765' . ($id + 61197960265728);
        } else {
            $converted = '';
        }

        return (string)$converted;
    }
}

if (!class_exists('SteamID')) {
    class SteamID
    {
        private $steamID = '';
        private $steamID32 = '';
        private $steamID64 = '';

        public function __construct($steam_id)
        {
            if (empty($steam_id)) {
                //$this->steamID = $this->steamID64 = '';
            } elseif (ctype_digit($steam_id) && strlen($steam_id) === 17) {
                $this->steamID64 = $steam_id;
                $this->steamID32 = $this->convert64to32($steam_id);
                $this->steamID = $this->convert64toID($steam_id);
            } elseif (ctype_digit($steam_id) && strlen($steam_id) != 17) {
                $this->steamID64 = $this->convert32to64($steam_id);
                $this->steamID32 = $steam_id;
                $this->steamID = $this->convert32toID($steam_id);
            } elseif (preg_match('/^STEAM_0:[01]:[0-9]+/', $steam_id)) {
                $this->steamID64 = $this->convertIDto64($steam_id);
                $this->steamID32 = $this->convertIDto32($steam_id);
                $this->steamID = $steam_id;
            } else {
                throw new RuntimeException('Invalid data provided; data is not a valid steamID or steamID32 or steamID64');
            }
        }

        private function convert64to32($steam_id)
        {
            $steam_cid = substr($steam_id, 3) - 61197960265728;
            return $steam_cid;
        }

        private function convert32to64($steam_id)
        {
            $steam_cid = '765' . ($steam_id + 61197960265728);
            return $steam_cid;
        }

        private function convert32toID($steam_id)
        {
            $steam_cid = '765' . ($steam_id + 61197960265728);
            $steam_cid = $this->convert64toID($steam_cid);
            return $steam_cid;
        }

        private function convert64toID($steam_cid)
        {
            $id = array('STEAM_0');
            $id[1] = substr($steam_cid, -1, 1) % 2 == 0 ? 0 : 1;
            $id[2] = bcsub($steam_cid, '76561197960265728');
            if (bccomp($id[2], '0') != 1) {
                return false;
            }
            $id[2] = bcsub($id[2], $id[1]);
            list($id[2],) = explode('.', bcdiv($id[2], 2), 2);
            return implode(':', $id);
        }

        private function convertIDto64($steam_id)
        {
            list(, $m1, $m2) = explode(':', $steam_id, 3);
            list($steam_cid,) = explode('.', bcadd((((int)$m2 * 2) + $m1), '76561197960265728'), 2);
            return $steam_cid;
        }

        private function convertIDto32($steam_id)
        {
            list(, $m1, $m2) = explode(':', $steam_id, 3);
            list($steam_cid,) = explode('.', bcadd((((int)$m2 * 2) + $m1), '76561197960265728'), 2);
            $steam_cid = $this->convert64to32($steam_cid);
            return $steam_cid;
        }

        public function getSteamID()
        {
            return $this->steamID;
        }

        public function getsteamID32()
        {
            return $this->steamID32;
        }

        public function getSteamID64()
        {
            return $this->steamID64;
        }
    }
}

if (!class_exists('steam_webapi')) {
    class steam_webapi
    {
        private $steamAPIKey = NULL;

        public function __construct($steamAPIKey)
        {
            if (empty($steamAPIKey)) {
                throw new RuntimeException('No Steam Key Provided!');
            } else {
                $this->steamAPIKey = $steamAPIKey;
            }
        }

        function ResolveVanityURL($vanityURL)
        {
            $APIresult = curl('http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=' . $this->steamAPIKey . '&vanityurl=' . $vanityURL);

            $APIresult = !empty($APIresult)
                ? json_decode($APIresult, 1)
                : false;

            return $APIresult;
        }

        function GetFriendList($steamID, $relationshipFilter = 'friend')
        {
            //Relationship filter. Possibles values: all, friend
            $APIresult = curl('http://api.steampowered.com/ISteamUser/GetFriendList/v0001/?key=' . $this->steamAPIKey . '&steamid=' . $steamID . '&relationship=' . $relationshipFilter);

            $APIresult = !empty($APIresult)
                ? json_decode($APIresult, 1)
                : false;

            return $APIresult;
        }

        public function GetPlayerSummaries($steamID)
        {
            $APIresult = curl('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->steamAPIKey . '&steamids=' . $steamID);

            if (!empty($APIresult)) {
                $APIresult = json_decode($APIresult, 1);
                //return $json->response->players[0];
                return $APIresult['response']['players'][0];
            } else {
                return false;
            }

            /*
             $userName = $user_details->personaname;
             $userAvatar = $user_details->avatar;
             $userAvatarMedium = $user_details->avatarmedium;
             $userAvatarLarge = $user_details->avatarfull;
             */

            /*
                [steamid] => 76561197966991022
                [communityvisibilitystate] => 3
                [profilestate] => 1
                [personaname] => Holz
                [lastlogoff] => 1418777450
                [commentpermission] => 2
                [profileurl] => http://steamcommunity.com/id/Holz/
                [avatar] => http://cdn.akamai.steamstatic.com/steamcommunity/public/images/avatars/a0/a0168118206a97e7473570193fde8aa565a9cebd.jpg
                [avatarmedium] => http://cdn.akamai.steamstatic.com/steamcommunity/public/images/avatars/a0/a0168118206a97e7473570193fde8aa565a9cebd_medium.jpg
                [avatarfull] => http://cdn.akamai.steamstatic.com/steamcommunity/public/images/avatars/a0/a0168118206a97e7473570193fde8aa565a9cebd_full.jpg
                [personastate] => 1
                [primaryclanid] => 103582791429587955
                [timecreated] => 1087204865
                [personastateflags] => 516
             */
        }
    }
}

if (!class_exists('dota2_webapi')) {
    class dota2_webapi
    {
        private $steamAPIKey = NULL;

        public function __construct($steamAPIKey)
        {
            if (empty($steamAPIKey)) {
                throw new RuntimeException('No Steam Key Provided!');
            } else {
                $this->steamAPIKey = $steamAPIKey;
            }
        }

        function GetGameItems($language = 'en')
        {
            $APIresult = curl('http://api.steampowered.com/IEconDOTA2_570/GetGameItems/v1/?key=' . $this->steamAPIKey . '&format=json&language=' . $language);

            $APIresult = !empty($APIresult)
                ? json_decode($APIresult, 1)
                : false;

            return $APIresult;
        }
    }
}