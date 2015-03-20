<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    echo '<h2>Page has been moved!</h2>';

    echo '<p>You will be directed to the new page shortly.</p>';

    echo '
        <script>
            $(document).ready(function () {
                pageReloader = setTimeout(function () {
                    if (document.getElementById("nav-refresh-holder").getAttribute("href") == "#sig__generator") {
                        clearTimeout(pageReloader);
                    }
                    else {
                        loadPage("#sig__generator", 0);
                    }
                }, 1000);
            });
        </script>
    ';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}
