#!/usr/bin/php -q
<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

try {
    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if ($db) {

        $port = 4445;

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); // create a streaming socket, of type TCP/IP

        socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1); // set the option to reuse the port

        socket_bind($sock, 0, $port); // "bind" the socket to the address to "localhost", on port $port

        socket_listen($sock); // start listen for connections

        echo "Waiting for connections...\n";

        $clients = array($sock); // create a list of all the clients that will be connected to us and add the listening socket to this list

        $write = NULL; //hacks cause we can't pass null
        $except = NULL; //hacks cause we can't pass null

        while (true) {
            $read = $clients; // create a copy, so $clients doesn't get modified by socket_select()

            // get a list of all the clients that have data to be read from if there are no clients with data, go to next iteration

            if (socket_select($read, $write, $except, 0) < 1)
                continue;

            if (in_array($sock, $read)) { // check if there is a client trying to connect
                // accept the client, and add him to the $clients array
                $clients[] = $newsock = socket_accept($sock);

                // send the client a welcome message
                socket_write($newsock, "I'm listening. There are " . (count($clients) - 1) . " client(s) connected\n");

                socket_getpeername($newsock, $ip);
                echo "New client connected: {$ip}\n";

                // remove the listening socket from the clients-with-data array
                $key = array_search($sock, $read);
                unset($read[$key]);
            }

            // loop through all the clients that have data to read from
            foreach ($read as $read_sock) {
                // read until newline or 15360 bytes
                // socket_read while show errors when the client is disconnected, so silence the error messages
                $data = @socket_read($read_sock, 15360, PHP_NORMAL_READ);

                // check if the client is disconnected
                if ($data === false) {
                    // remove client for $clients array
                    $key = array_search($read_sock, $clients);
                    unset($clients[$key]);
                    echo "client disconnected.\n";
                    // continue to the next client to read from, if any
                    continue;
                }

                // trim off the trailing/beginning white spaces
                $data = trim($data);

                // check if there is any data after trimming off the spaces
                if (!empty($data)) {
                    socket_getpeername($read_sock, $ip, $port);

                    // send ack back to client -- add a newline character to the end of the message
                    socket_write($read_sock, 'Acknowledged' . "\n");
                    echo 'Received: [' . $ip . ':' . $port . '] ' . $data . "\n";

                    $db->q('INSERT INTO `test_landing`(`message`, `remote_ip`) VALUES (?, ?)',
                        'ss',
                        $db->escape($data), $ip);
                }
            }
        }

        socket_close($sock);
    } else {
        echo 'No DB!';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}