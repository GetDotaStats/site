<h2>asyncWebRequest</h2>
<p>Ensure that the data returned by the page has a content type of "application/JSON", to enable automatic parsing. Documented <a target="_blank" href="https://developer.valvesoftware.com/wiki/Dota_2_Workshop_Tools/Scripting/Using_CreateHTTPRequest"><span class="glyphicon glyphicon-new-window"></span> here</a> on the official wiki.</p>
<pre>
    CreateHTTPRequest( "GET", "http://www.google.com" ):Send( function( result )
        print( "GET response:\n" )
        for k,v in pairs( result ) do
            print( string.format( "%s : %s\n", k, v ) )
        end
        print( "Done." )
    end )
</pre>

<code>
    CreateHTTPRequest( "GET", "http://www.google.com" ):Send( function( result )
    print( "GET response:\n" )
    for k,v in pairs( result ) do
    print( string.format( "%s : %s\n", k, v ) )
    end
    print( "Done." )
    end )
</code>

<span class="h4">&nbsp;</span>

<div class="text-center">
    <a class="nav-clickable btn btn-default btn-lg" href="#source2__beta_changes">Dota 2 Reborn Changes</a>
    <a class="nav-clickable btn btn-default btn-lg" href="#source2__css">CSS</a>
    <a class="nav-clickable btn btn-default btn-lg" href="#source2__asyncwebrequest">asyncWebRequest</a>
    <a class="nav-clickable btn btn-default btn-lg" href="#source2__convars">convars</a>
</div>