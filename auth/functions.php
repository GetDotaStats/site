<?php
if (!class_exists('user')) {
    class user
    {
        public $apikey;
        public $domain;

        public function GetPlayerSummaries($steamid)
        {
            $response = file_get_contents('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->apikey . '&steamids=' . $steamid);
            $json = json_decode($response);
            return $json->response->players[0];
        }

        public function signIn($relocate = NULL)
        {
            require_once './openid.php';
            $openid = new LightOpenID($this->domain); // put your domain
            if (!$openid->mode) {
                $openid->identity = 'http://steamcommunity.com/openid';
                header('Location: ' . $openid->authUrl());
            } elseif ($openid->mode == 'cancel') {
                print ('User has canceled authentication!');
            } else {
                if ($openid->validate()) {
                    preg_match("/^http:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/", $openid->identity, $matches); // steamID: $matches[1]
                    //setcookie('steamID', $matches[1], time()+(60*60*24*7), '/'); // 1 week
                    $_SESSION['user_id'] = $matches[1];
                    $_SESSION['user_details'] = $this->GetPlayerSummaries($_SESSION['user_id']);

                    if ($relocate) {
                        header('Location: ' . $relocate);
                    } else {
                        header('Location: ./');
                    }
                    exit;
                } else {
                    print ('fail');
                }
            }
        }

        public function signOut($relocate = NULL)
        {
            unset($_SESSION['user_id']);
            unset($_SESSION['user_details']);

            if ($relocate) {
                header('Location: ' . $relocate);
            } else {
                header('Location: ./');
            }
        }
    }
}

if (!function_exists("convert_id")) {
    function convert_steamid($id, $required_output = '32')
    {
        if (empty($id)) return false;

        if (strlen($id) === 17 && $required_output == '32') {
            $converted = substr($id, 3) - 61197960265728;
        } else if (strlen($id) != 17 && $required_output == '64') {
            $converted = '765' . ($id + 61197960265728);
        }
        else{
            $converted = '';
        }

        return (string)$converted;
    }
}


if (!class_exists('SteamID')) {
    class SteamID
    {
        private $steamID32 = '';

        private $steamID64 = '';

        public function __construct($steam_id)
        {
            if (empty($steam_id)) {
                $this->steamID32 = $this->steamID64 = '';
            } elseif (ctype_digit($steam_id)) {
                $this->steamID64 = $steam_id;
                $this->steamID32 = $this->convert64to32($steam_id);
            } elseif (preg_match('/^STEAM_0:[01]:[0-9]+/', $steam_id)) {
                $this->steamID32 = $steam_id;
                $this->steamID64 = $this->convert32to64($steam_id);
            } else {
                throw new RuntimeException('Invalid data provided; data is not a valid steamid32 or steamid64');
            }
        }

        private function convert32to64($steam_id)
        {
            list(, $m1, $m2) = explode(':', $steam_id, 3);
            list($steam_cid,) = explode('.', bcadd((((int)$m2 * 2) + $m1), '76561197960265728'), 2);
            return $steam_cid;
        }

        private function convert64to32($steam_cid)
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

        public function getSteamID32()
        {
            return $this->steamID32;
        }

        public function getSteamID64()
        {
            return $this->steamID64;
        }
    }
}