<?php
try {
    $code = (!empty($_GET['q']) && is_numeric($_GET['q'])) ? $_GET['q'] : '???';

    switch ($code) {
        case 100:
            $text = 'Continue';
            break;
        case 101:
            $text = 'Switching Protocols';
            break;
        case 200:
            $text = 'OK';
            break;
        case 201:
            $text = 'Created';
            break;
        case 202:
            $text = 'Accepted';
            break;
        case 203:
            $text = 'Non-Authoritative Information';
            break;
        case 204:
            $text = 'No Content';
            break;
        case 205:
            $text = 'Reset Content';
            break;
        case 206:
            $text = 'Partial Content';
            break;
        case 300:
            $text = 'Multiple Choices';
            break;
        case 301:
            $text = 'Moved Permanently';
            break;
        case 302:
            $text = 'Moved Temporarily';
            break;
        case 303:
            $text = 'See Other';
            break;
        case 304:
            $text = 'Not Modified';
            break;
        case 305:
            $text = 'Use Proxy';
            break;
        case 400:
            $text = 'Bad Request';
            break;
        case 401:
            $text = 'Unauthorized';
            break;
        case 402:
            $text = 'Payment Required';
            break;
        case 403:
            $text = 'Forbidden';
            break;
        case 404:
            $text = 'Not Found';
            break;
        case 405:
            $text = 'Method Not Allowed';
            break;
        case 406:
            $text = 'Not Acceptable';
            break;
        case 407:
            $text = 'Proxy Authentication Required';
            break;
        case 408:
            $text = 'Request Time-out';
            break;
        case 409:
            $text = 'Conflict';
            break;
        case 410:
            $text = 'Gone';
            break;
        case 411:
            $text = 'Length Required';
            break;
        case 412:
            $text = 'Precondition Failed';
            break;
        case 413:
            $text = 'Request Entity Too Large';
            break;
        case 414:
            $text = 'Request-URI Too Large';
            break;
        case 415:
            $text = 'Unsupported Media Type';
            break;
        case 500:
            $text = 'Internal Server Error';
            break;
        case 501:
            $text = 'Not Implemented';
            break;
        case 502:
            $text = 'Bad Gateway';
            break;
        case 503:
            $text = 'Service Unavailable';
            break;
        case 504:
            $text = 'Gateway Time-out';
            break;
        case 505:
            $text = 'HTTP Version not supported';
            break;
        case 506:
            $text = 'Variant Also Negotiates (RFC 2295)';
            break;
        case 507:
            $text = 'Insufficient Storage (WebDAV; RFC 4918)';
            break;
        case 508:
            $text = 'Loop Detected (WebDAV; RFC 5842)';
            break;
        case 510:
            $text = 'Not Extended (RFC 2774)';
            break;
        case 511:
            $text = 'Network Authentication Required (RFC 6585)';
            break;
        //UNOFFICIAL CODES
        case 103:
            $text = 'Checkpoint';
            break;
        case 420:
            $text = 'Method Failure (Spring Framework)';
            break;
        case 450:
            $text = 'Blocked by Windows Parental Controls (Microsoft)';
            break;
        case 498:
            $text = 'Invalid Token (Esri)';
            break;
        case 509:
            $text = 'Bandwidth Limit Exceeded (Apache Web Server/cPanel)';
            break;
        case 440:
            $text = 'Login Timeout';
            break;
        case 449:
            $text = 'Retry With';
            break;
        case 451:
            $text = 'Unavailable for legal reasons';
            break;
        case 444:
            $text = 'No Response';
            break;
        case 495:
            $text = 'SSL Certificate Error';
            break;
        case 496:
            $text = 'SSL Certificate Required';
            break;
        case 497:
            $text = 'HTTP Request Sent to HTTPS Port';
            break;
        case 499:
            $text = 'Client Closed Request';
            break;
        case 520:
            $text = 'Unknown Error (CloudFlare)';
            break;
        case 521:
            $text = 'Web Server Is Down (CloudFlare)';
            break;
        case 522:
            $text = 'Connection Timed Out (CloudFlare)';
            break;
        case 523:
            $text = 'Origin Is Unreachable (CloudFlare)';
            break;
        case 524:
            $text = 'A Timeout Occurred (CloudFlare)';
            break;
        case 525:
            $text = 'SSL Handshake Failed (CloudFlare)';
            break;
        case 526:
            $text = 'Invalid SSL Certificate (CloudFlare)';
            break;
        default:
            $text = 'Unknown http status code';
            break;
    }

    echo "<h2>Oops: {$code} <small>{$text}</small></h2>";
    echo "<p>Something went wrong. If you feel this page was displayed in error, send us a mail at jimmydorry [@] getdotastats [.] com</p>";
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}