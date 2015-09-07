<h2>Update</h2>
<p>Check our news post to see why lobbies are not required any more.</p>

<script>
    $(document).ready(function () {
        pageReloader = setTimeout(function () {
            if (document.getElementById("nav-refresh-holder").getAttribute("href") == "#d2mods__lobby_list_old") {
                loadPage("#d2mods__lobby_list_old", 2);
            }
            else {
                clearTimeout(pageReloader);
            }
        }, 5000);
    });
</script>