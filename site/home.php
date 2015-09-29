<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    echo '<h2>News</h2>';

    echo '<p>We are just putting the finishing touches on our new custom stats solution.</p>';

    echo '<p>What we are offering is far more flexible for modders to use and allows us to
        gain a far better insight into how mods are being played.</p>';

    echo '<p>Remember to join our Steam group to ensure you always get the latest news!</p>';

    echo '<p><strong>Posted:</strong> ' . relative_time_v3('1442190074') . '</p>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}