<?php
if (!class_exists('user')) {
    class user
    {
        public $apikey;
        public $domain;

        public function GetPlayerSummaries($steamid)
        {
            try {
                $response = curl('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->apikey . '&steamids=' . $steamid);
                $json = json_decode($response);
                if (!empty($json)) {
                    return $json->response->players[0];
                }
            } catch (Exception $e) {
                $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
                echo bootstrapMessage('Oh Snap', $message, 'danger');
            }

            return false;
        }

        public function signIn($relocate = NULL, $db)
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

                    $steamID64 = $matches[1];
                    $steamID32 = convert_steamid($steamID64);

                    $user_details = $this->GetPlayerSummaries($steamID64);

                    $userName = $user_details->personaname;
                    $userAvatar = $user_details->avatar;
                    $userAvatarMedium = $user_details->avatarmedium;
                    $userAvatarLarge = $user_details->avatarfull;


                    $_SESSION['user_id32'] = $steamID32;
                    $_SESSION['user_id64'] = $steamID64;
                    $_SESSION['user_name'] = htmlentities($userName);
                    $_SESSION['user_avatar'] = $userAvatar;

                    $db->q("INSERT INTO `gds_users`(`user_id32`, `user_id64`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`) VALUES (?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE `user_name` = VALUES(`user_name`), `user_avatar` = VALUES(`user_avatar`), `user_avatar_medium` = VALUES(`user_avatar_medium`), `user_avatar_large` = VALUES(`user_avatar_large`);",
                        'iissss',
                        $steamID32, $steamID64, $userName, $userAvatar, $userAvatarMedium, $userAvatarLarge);

                    $cookie = guid();

                    $db->q("INSERT INTO `gds_users_sessions` (`user_id64`, `remote_ip`, `user_cookie`) VALUES (?, ?, ?)",
                        'iss',
                        $steamID64, $_SERVER['REMOTE_ADDR'], $cookie);

                    //LOOKUP ACCOUNT
                    $accountDetails = $db->q('SELECT * FROM `gds_users` WHERE `user_id64` = ? LIMIT 0,1;',
                        'i',
                        $steamID64);

                    if (!empty($accountDetails)) {
                        //SET ACCESS PERMISSION
                        $_SESSION['access_feeds'] = $accountDetails[0]['access_feeds'];
                        $_SESSION['isAdmin'] = $accountDetails[0]['isAdmin'];
                    }

                    $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? '.' . $_SERVER['HTTP_HOST'] : false;
                    setcookie('session', $cookie, time() + 60 * 60 * 24 * 30, '/', $domain);

                    /*setcookie('session', $cookie, time() + 60 * 60 * 24 * 30, '/', '.getdotastats.com');
                    setcookie('session', $cookie, time() + 60 * 60 * 24 * 30, '/', '.dota.solutions');
                    setcookie('session', $cookie, time() + 60 * 60 * 24 * 30, '/', '.dota2.solutions');
                    setcookie('session', $cookie, time() + 60 * 60 * 24 * 30, '/', '.dota.technology');
                    setcookie('session', $cookie, time() + 60 * 60 * 24 * 30, '/', '.dota.photography');
                    setcookie('session', $cookie, time() + 60 * 60 * 24 * 30, '/', '.dota.company');*/

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

        public function signOut($relocate = NULL, $db)
        {
            $sessionCookie = !empty($_COOKIE['session'])
                ? $_COOKIE['session']
                : NULL;
            $sessionUserID64 = !empty($_SESSION['user_id64'])
                ? $_SESSION['user_id64']
                : NULL;

            if (!empty($_COOKIE['session']) || !empty($_SESSION['user_id64'])) {
                $db->q("DELETE FROM `gds_users_sessions` WHERE `user_id64` = ? OR `user_cookie` = ?;",
                    'is',
                    $sessionUserID64, $sessionCookie);
            }

            unset($_SESSION['user_id32']);
            unset($_SESSION['user_id64']);
            unset($_SESSION['user_name']);
            unset($_SESSION['user_avatar']);
            unset($_SESSION['access_feeds']);
            unset($_SESSION['isAdmin']);

            $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? '.' . $_SERVER['HTTP_HOST'] : false;
            setcookie('session', '', time() - 3600, '/', $domain);

            /*setcookie('session', '', time() - 3600, '/', '.getdotastats.com');
            setcookie('session', '', time() - 3600, '/', '.dota.solutions');
            setcookie('session', '', time() - 3600, '/', '.dota2.solutions');
            setcookie('session', '', time() - 3600, '/', '.dota.technology');
            setcookie('session', '', time() - 3600, '/', '.dota.photography');
            setcookie('session', '', time() - 3600, '/', '.dota.company');*/

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
        } else {
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