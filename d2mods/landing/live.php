#!/usr/bin/php -q
<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

if (!function_exists('timePretty')) {
    function timePretty()
    {
        return date("c", time());
    }
}

try {
    $port = 4445;

    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); // create a streaming socket, of type TCP/IP

    socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1); // set the option to reuse the port

    socket_bind($sock, 0, $port); // "bind" the socket to the address to "localhost", on port $port

    socket_listen($sock); // start listen for connections

    echo "[".timePretty()."] Waiting for connections...\n";

    $clients = array($sock); // create a list of all the clients that will be connected to us... & add the listening socket to this list

    $write = NULL; //hacks cause we can't pass null
    $except = NULL; //hacks cause we can't pass null

    try {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);

        if (!$db) {
            throw new Exception('[1] No DB!!!');
        }

        while (true) {
            try {
                if (!$db) {
                    echo '[2] No DB!!';
                    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
                }

                while ($db) {
                    $read = $clients; // create a copy, so $clients doesn't get modified by socket_select()

                    if (socket_select($read, $write, $except, 0) < 1) // get a list of all the clients that have data to be read from & if there are no clients with data, go to next iteration
                        continue;

                    if (in_array($sock, $read)) { // check if there is a client trying to connect
                        $clients[] = $newsock = socket_accept($sock); // accept the client, and add him to the $clients array

                        socket_write($newsock, "I'm listening. There are " . (count($clients) - 1) . " client(s) connected\n"); // send the client a welcome message

                        socket_getpeername($newsock, $ip);
                        echo "[".timePretty()."] New client connected: {$ip}\n";

                        $key = array_search($sock, $read); // remove the listening socket from the clients-with-data array
                        unset($read[$key]);
                    }

                    foreach ($read as $read_sock) { // loop through all the clients that have data to read from
                        $data = @socket_read($read_sock, 15360, PHP_NORMAL_READ); // read until newline or 15360 bytes || socket_read while show errors when the client is disconnected, so silence the error messages

                        if ($data === false) { // check if the client is disconnected
                            $key = array_search($read_sock, $clients); // remove client for $clients array
                            unset($clients[$key]);
                            echo "[".timePretty()."] Client disconnected.\n";

                            continue;
                        }

                        $data = trim($data); // trim off the trailing/beginning white spaces

                        if (!empty($data)) { // check if there is any data after trimming off the spaces
                            socket_getpeername($read_sock, $ip, $port);

                            echo "[".timePretty()."] Received: [" . $ip . ':' . $port . '] ' . $data . "\n"; // send ack back to client -- add a newline character to the end of the message

                            try {
                                $db->ping();
                                $test = $db->q('INSERT INTO `test_landing`(`message`, `remote_ip`) VALUES (?, ?)',
                                    'ss',
                                    $db->escape($data), $ip);

                                $json_array = json_decode($data,true);
                                if(!empty($json_array)){
                                    echo "||JSON is parsable||\n";
                                    print_r($json_array);

                                    if ($test) {
                                        socket_write($read_sock, 'Acknowledged' . "\n");

                                    } else {
                                        socket_write($read_sock, '[4] Failure: Not recorded' . "\n");
                                    }
                                }
                                else{
                                    socket_write($read_sock, '[5] Failure: Not JSON' . "\n");
                                }
                            } catch (Exception $e) {
                                echo $e->getMessage();
                                socket_write($read_sock, '[3] Failure' . "\n");
                                $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }

            sleep(10);
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    socket_close($sock);
} catch (Exception $e) {
    echo $e->getMessage();
}
