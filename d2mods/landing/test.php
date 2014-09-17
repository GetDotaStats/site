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
    $port = 4444;

    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); // create a streaming socket, of type TCP/IP

    socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1); // set the option to reuse the port

    socket_bind($sock, 0, $port); // "bind" the socket to the address to "localhost", on port $port

    socket_listen($sock); // start listen for connections

    echo "[" . timePretty() . "] Waiting for connections...<br />";

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
                    echo '[2] No DB!!' . "<br />";
                    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
                }

                while ($db) {
                    $read = $clients; // create a copy, so $clients doesn't get modified by socket_select()

                    if (socket_select($read, $write, $except, 0) < 1) // get a list of all the clients that have data to be read from & if there are no clients with data, go to next iteration
                        continue;

                    if (in_array($sock, $read)) { // check if there is a client trying to connect
                        $newsock = socket_accept($sock); // accept the client

                        socket_getpeername($newsock, $ip, $port);
                        $sockName = $ip . ':' . $port;

                        $clients[$sockName] = $newsock; //add client socket to the $clients array

                        //socket_write($newsock, "I'm listening. There are " . (count($clients) - 1) . " client(s) connected\n"); // send the client a welcome message
                        socket_write($newsock, "connected"); // send the client a welcome message

                        echo "[" . timePretty() . "] New client connected: {$sockName}<br />";

                        $key = array_search($sock, $read); // remove the listening socket from the clients-with-data array
                        unset($read[$key]);
                    }

                    foreach ($read as $read_sock) { // loop through all the clients that have data to read from
                        $data = @socket_read($read_sock, 15360, PHP_NORMAL_READ); // read until newline or 15360 bytes || socket_read while show errors when the client is disconnected, so silence the error messages
                        $key = array_search($read_sock, $clients);

                        if ($data === false) { // check if the client is disconnected
                            echo "[" . timePretty() . "] Client disconnected: {$key}<br />";
                            unset($clients[$key]); // remove client for $clients array

                            continue;
                        }

                        $data = trim($data); // trim off the trailing/beginning white spaces

                        if (!empty($data)) { // check if there is any data after trimming off the spaces
                            echo "[" . timePretty() . "] Received: [{$key}] " . $data . "<br />"; // send ack back to client -- add a newline character to the end of the message

                            try {
                                $test = $db->q('INSERT INTO `test_landing`(`message`, `remote_ip`) VALUES (?, ?)',
                                    'ss',
                                    $db->escape($data), $key);

                                if ($test) {
                                    //socket_write($read_sock, 'Acknowledged' . "\n");
                                    socket_write($read_sock, 'ack');

                                } else {
                                    //socket_write($read_sock, '[4] Failure DB' . "\n");
                                    socket_write($read_sock, 'fail4');
                                }
                            } catch (Exception $e) {
                                echo $e->getMessage() . "<br />";

                                $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);

                                try {
                                    $test = $db->q('INSERT INTO `test_landing`(`message`, `remote_ip`) VALUES (?, ?)',
                                        'ss',
                                        $db->escape($data), $key);

                                    if ($test) {
                                        socket_write($read_sock, 'ack');

                                    } else {
                                        socket_write($read_sock, 'fail4');
                                    }
                                } catch (Exception $e) {
                                    echo $e->getMessage() . "<br />";
                                    socket_write($read_sock, 'fail3');
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                echo $e->getMessage() . "<br />";
            }

            sleep(10);
        }
    } catch (Exception $e) {
        echo $e->getMessage() . "<br />";
    }
    socket_close($sock);
} catch (Exception $e) {
    echo $e->getMessage() . "<br />";
}
