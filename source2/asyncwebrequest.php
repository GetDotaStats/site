<h2>asyncWebRequest</h2>
<p>As originally discovered by SelenaGomez, and confirmed with additional detail by Penguinwizzard. Ensure that the data returned by the page has a content type of "application/JSON", to enable automatic parsing.</p>
<pre>
    $.AsyncWebRequest(
        'http://getdotastats.com/d2mods/api/test.php',
        {
            type: 'POST',
            data: {
                'best': 'Selena'
            },
            complete: function(a,b,c,d,e) {
                $.Msg('woo');
            },
            timeout: 50000
        }
    );
</pre>

<code>
    $.AsyncWebRequest(
        'http://getdotastats.com/d2mods/api/test.php',
        {
            type: 'POST',
            data: {
                'best': 'Selena'
            },
            complete: function(a,b,c,d,e) {
                $.Msg('woo');
            },
            timeout: 50000
        }
    );
</code>

<span class="h4">&nbsp;</span>

<div class="text-center">
    <a class="nav-clickable btn btn-default btn-lg" href="#source2__beta_changes">Dota 2 Reborn Changes</a>
    <a class="nav-clickable btn btn-default btn-lg" href="#source2__css">CSS</a>
    <a class="nav-clickable btn btn-default btn-lg" href="#source2__createhttprequest">CreateHTTPRequest</a>
    <a class="nav-clickable btn btn-default btn-lg" href="#source2__convars">convars</a>
</div>