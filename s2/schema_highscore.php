<div class="page-header">
    <h2>Schema for stat-highscore
        <small>BETA</small>
    </h2>
</div>

<p>Below is the workflow in terms of data communicated between host and GDS through when recording highscores.</p>

<hr/>

<h3>For Consideration</h3>

<ul>
    <li>We can lock this down by having an authKey that is communicated to the client on each new save. Before
        overwriting or loading this save, the client would need to communicate this authKey via the chat or typing a
        code into chat "-code XXXXX" or a custom Panorama UI, as people did in WC3.
    </li>
</ul>

<hr/>

<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

    <!--
    ////////////////////////////////////////////////////
    // Method "SAVE" - Client
    ////////////////////////////////////////////////////
    -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingOne">
            <h4 class="panel-title">
                <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                   href="#collapseOne"
                   aria-expanded="false" aria-controls="collapseOne">
                    Method "SAVE" - Client
                </a>
            </h4>
        </div>
        <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
            <div class="panel-body">

                <p>This is for saving a highscore.</p>

                <p><strong>Endpoint:</strong> <code>POST http://getdotastats.com/s2/api/s2_highscore.php || "payload" =
                        <em>JSONschema</em></code>
                </p>

                <hr/>

                <div>
                    <div class="row">
                        <div class="col-sm-3"><strong>Key</strong></div>
                        <div class="col-sm-2"><strong>Type</strong></div>
                        <div class="col-sm-3"><strong>Example</strong></div>
                        <div class="col-sm-4"><strong>Notes</strong></div>
                    </div>
                    <span class="h4">&nbsp;</span>

                    <div class="row">
                        <div class="col-sm-3">type</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-7">"SAVE"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">modID</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"7689asdfjh1231"</div>
                        <div class="col-sm-4">
                            <a class="nav-clickable" href="#d2mods__my_mods">Unique value assigned to your mod</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">highscoreID</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"hj43152khjb342"</div>
                        <div class="col-sm-4">
                            Ask a site moderator to for one
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">steamID32</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-7">"28755155"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">userName</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-7">"jimmydorry"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">highscoreValue</div>
                        <div class="col-sm-2">integer</div>
                        <div class="col-sm-7">12321</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">schemaVersion</div>
                        <div class="col-sm-2">integer</div>
                        <div class="col-sm-7">1</div>
                    </div>

                    <span class="h4">&nbsp;</span>
                </div>

                <hr/>

            <pre class="pre-scrollable">
{
    "type": "SAVE",
    "modID": "7689asdfjh1231",
    "highscoreID": "hj43152khjb342",
    "steamID32": "28755155",
    "userName": "jimmydorry",
    "highscoreValue": 12321,
    "schemaVersion": 1
}</pre>

            </div>
        </div>
    </div>

    <!--
    ////////////////////////////////////////////////////
    // Method "SAVE" - Server
    ////////////////////////////////////////////////////
    -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingTwo">
            <h4 class="panel-title">
                <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                   href="#collapseTwo"
                   aria-expanded="false" aria-controls="collapseTwo">
                    Method "SAVE" - Server
                </a>
            </h4>
        </div>
        <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
            <div class="panel-body">
                <p>This is the response from the server after receiving the data for the SAVE method. Until we have
                    clients able to enter an authKey, this field can be ignored.</p>

                <p>A success will have result=1, where as a failure will have result=0 with an error field. You may want
                    to display the error to users so they know what happened.</p>

            <pre class="pre-scrollable">
{
    "type": "save",
    "result": 0,
    "error": "Invalid JSON",
    "authKey": "XXXXXX",
    "schemaVersion": 1
}</pre>
            </div>
        </div>
    </div>


    <!--
    ////////////////////////////////////////////////////
    // Method "TOP" - Client
    ////////////////////////////////////////////////////
    -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingThree">
            <h4 class="panel-title">
                <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                   href="#collapseThree"
                   aria-expanded="false" aria-controls="collapseThree">
                    Method "TOP" - Client
                </a>
            </h4>
        </div>
        <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
            <div class="panel-body">

                <p>This is for getting the top 20 players for each highscore type for a specified mod.</p>

                <p><strong>Endpoint:</strong> <code>POST http://getdotastats.com/s2/api/s2_highscore.php || "payload" =
                        <em>JSONschema</em></code>
                </p>

                <hr/>

                <div>
                    <div class="row">
                        <div class="col-sm-3"><strong>Key</strong></div>
                        <div class="col-sm-2"><strong>Type</strong></div>
                        <div class="col-sm-3"><strong>Example</strong></div>
                        <div class="col-sm-4"><strong>Notes</strong></div>
                    </div>
                    <span class="h4">&nbsp;</span>

                    <div class="row">
                        <div class="col-sm-3">type</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-7">"TOP"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">modID</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"7689asdfjh1231"</div>
                        <div class="col-sm-4">
                            <a class="nav-clickable" href="#d2mods__my_mods">Unique value assigned to your mod</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">schemaVersion</div>
                        <div class="col-sm-2">integer</div>
                        <div class="col-sm-7">1</div>
                    </div>

                    <span class="h4">&nbsp;</span>
                </div>

                <hr/>

            <pre class="pre-scrollable">
{
    "type": "TOP",
    "modID": "7689asdfjh1231",
    "schemaVersion": 1
}</pre>

            </div>
        </div>
    </div>

    <!--
    ////////////////////////////////////////////////////
    // Method "TOP" - Server
    ////////////////////////////////////////////////////
    -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingFour">
            <h4 class="panel-title">
                <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                   href="#collapseFour"
                   aria-expanded="false" aria-controls="collapseFour">
                    Method "TOP" - Server
                </a>
            </h4>
        </div>
        <div id="collapseFour" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
            <div class="panel-body">
                <p>This is the response from the server after receiving the data for the TOP method.</p>

                <p>A success will have result=1, where as a failure will have result=0 with an error field. You may want
                    to display the error to users so they know what happened.</p>

            <pre class="pre-scrollable">
{
  "type": "top",
  "jsonData": [
    {
      "highscoreID": "fads780324n23",
      "userName": "BMD",
      "steamID32": 28755156,
      "highscoreValue": 12321,
      "date_recorded": "2015-07-11 19:30:03"
    },
    {
      "highscoreID": "fads780324n23",
      "userName": "jimmydorry",
      "steamID32": 28755155,
      "highscoreValue": 12321,
      "date_recorded": "2015-07-11 19:30:22"
    },
    {
      "highscoreID": "hj43152khjb342",
      "userName": "jimmydorry",
      "steamID32": 28755155,
      "highscoreValue": 12321,
      "date_recorded": "2015-07-11 19:08:43"
    },
    {
      "highscoreID": "hj43152khjb342",
      "userName": "BMD",
      "steamID32": 28755156,
      "highscoreValue": 12321,
      "date_recorded": "2015-07-11 19:12:30"
    }
  ],
  "result": 1,
  "schemaVersion": 1
}</pre>
            </div>
        </div>
    </div>



    <!--
    ////////////////////////////////////////////////////
    // Method "LIST" - Client
    ////////////////////////////////////////////////////
    -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingFive">
            <h4 class="panel-title">
                <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                   href="#collapseFive"
                   aria-expanded="false" aria-controls="collapseFive">
                    Method "LIST" - Client
                </a>
            </h4>
        </div>
        <div id="collapseFive" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFive">
            <div class="panel-body">

                <p>This is for getting all of a user's highscores for a specified mod.</p>

                <p><strong>Endpoint:</strong> <code>POST http://getdotastats.com/s2/api/s2_highscore.php || "payload" =
                        <em>JSONschema</em></code>
                </p>

                <hr/>

                <div>
                    <div class="row">
                        <div class="col-sm-3"><strong>Key</strong></div>
                        <div class="col-sm-2"><strong>Type</strong></div>
                        <div class="col-sm-3"><strong>Example</strong></div>
                        <div class="col-sm-4"><strong>Notes</strong></div>
                    </div>
                    <span class="h4">&nbsp;</span>

                    <div class="row">
                        <div class="col-sm-3">type</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-7">"LIST"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">modID</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"7689asdfjh1231"</div>
                        <div class="col-sm-4">
                            <a class="nav-clickable" href="#d2mods__my_mods">Unique value assigned to your mod</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">steamID32</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-7">"28755155"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">schemaVersion</div>
                        <div class="col-sm-2">integer</div>
                        <div class="col-sm-7">1</div>
                    </div>

                    <span class="h4">&nbsp;</span>
                </div>

                <hr/>

            <pre class="pre-scrollable">
{
    "type": "LIST",
    "steamID32": "28755155",
    "schemaVersion": 1
}</pre>

            </div>
        </div>
    </div>

    <!--
    ////////////////////////////////////////////////////
    // Method "LIST" - Server
    ////////////////////////////////////////////////////
    -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingSix">
            <h4 class="panel-title">
                <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                   href="#collapseSix"
                   aria-expanded="false" aria-controls="collapseSix">
                    Method "LIST" - Server
                </a>
            </h4>
        </div>
        <div id="collapseSix" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSix">
            <div class="panel-body">
                <p>This is the response from the server after receiving the data for the TOP method.</p>

                <p>A success will have result=1, where as a failure will have result=0 with an error field. You may want
                    to display the error to users so they know what happened.</p>

            <pre class="pre-scrollable">
{
  "type": "list",
  "jsonData": [
    {
      "highscoreID": "h2345kjn52314",
      "highscoreAuthKey": "XXXXXX",
      "highscoreValue": 12321,
      "date_recorded": "2015-07-11 19:30:22"
    },
    {
      "highscoreID": "hj43152khjb342",
      "highscoreAuthKey": "XXXXXX",
      "highscoreValue": 12321,
      "date_recorded": "2015-07-11 19:08:43"
    }
  ],
  "result": 1,
  "schemaVersion": 1
}</pre>
            </div>
        </div>
    </div>


</div>

<hr/>


<h3>Latest Highscore Data</h3>
<p>Below is a simple table showing the five latest highscores recorded. It will serve as a debugging tool while we setup
    a more sophisticated testing environment.</p>

<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $latestData = cached_query('s2_highscore_latest_data',
        'SELECT
              shm.`modID`,
              shm.`highscoreID`,
              shm.`steamID32`,
              shm.`highscoreAuthKey`,
              shm.`userName`,
              shm.`highscoreValue`,
              shm.`date_recorded`,

              shms.`highscoreName`,
              shms.`highscoreDescription`,
              shms.`highscoreActive`,
              shms.`highscoreObjective`,
              shms.`highscoreOperator`,
              shms.`highscoreFactor`,
              shms.`highscoreDecimals`
            FROM `stat_highscore_mods` shm
            JOIN `stat_highscore_mods_schema` shms ON shm.`highscoreID` = shms.`highscoreID`
            ORDER BY shm.`date_recorded` DESC
            LIMIT 0,5;',
        NULL,
        NULL,
        5
    );

    if (!empty($latestData)) {
        echo '<div class="row">
                    <div class="col-sm-3"><strong>Highscore</strong></div>
                    <div class="col-sm-3"><strong>Mod ID</strong></div>
                    <div class="col-sm-4">
                        <div class="row">
                            <div class="col-sm-4"><strong>Steam ID</strong></div>
                            <div class="col-sm-4"><strong>Player</strong></div>
                            <div class="col-sm-4"><strong>Value</strong></div>
                        </div>
                    </div>
                    <div class="col-sm-2"><strong>Recorded</strong></div>
                </div>
                <span class="h4">&nbsp;</span>
                ';

        foreach ($latestData as $key => $value) {
            $relativeDateRaw = relative_time_v2($value['date_recorded'], 'hour', true);

            $timeColour = $relativeDateRaw['number'] <= 2
                ? ' hs_lb_recent_score'
                : '';

            echo '<div class="row">
                <div class="col-sm-3"><span>' . $value['highscoreName'] . '</span></div>
                <div class="col-sm-3"><span class="db_link">' . $value['modID'] . '</span></div>
                <div class="col-sm-4">
                    <div class="row">
                        <div class="col-sm-4">' . $value['steamID32'] . '</div>
                        <div class="col-sm-4">' . $value['userName'] . '</div>
                        <div class="col-sm-4">' . $value['highscoreValue'] . '</div>
                    </div>
                </div>
                <div class="col-sm-2"><span class="' . $timeColour . '">' . relative_time_v3($value['date_recorded'], 1) . '</span></div>
            </div>
            ';

            echo '<span class="h4">&nbsp;</span>';
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No data recorded yet!.', 'danger');
    }

    echo '<span class="h4">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}