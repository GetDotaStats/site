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

                <p>This is for saving a highscore. If you have not secured your highscores, then you do not need the
                    `userAuthKey` field.</p>

                <p><strong>Endpoint:</strong> <code>POST https://api.getdotastats.com/s2_highscore.php || "payload" =
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
                        <div class="col-sm-3">modIdentifier</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"7689asdfjh1231"</div>
                        <div class="col-sm-4">
                            <a class="nav-clickable" href="#s2__my__mods">Unique value assigned to your mod</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">highscoreID</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"hj43152khjb342"</div>
                        <div class="col-sm-4">
                            Ask a site moderator for one
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
                        <div class="col-sm-3">userAuthKey</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"XJAHVAS"</div>
                        <div class="col-sm-4">
                            OPTIONAL FIELD (see method description)
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">matchID</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"127398123"</div>
                        <div class="col-sm-4">
                            OPTIONAL FIELD
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
    "type": "SAVE",
    "modIdentifier": "7689asdfjh1231",
    "highscoreID": "hj43152khjb342",
    "steamID32": "28755155",
    "userName": "jimmydorry",
    "highscoreValue": 12321,
    "userAuthKey": "XJAHVAS",
    "matchID": "1235124321",
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

                <p><strong>Endpoint:</strong> <code>POST https://api.getdotastats.com/s2_highscore.php || "payload" =
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
                        <div class="col-sm-3">modIdentifier</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"7689asdfjh1231"</div>
                        <div class="col-sm-4">
                            <a class="nav-clickable" href="#s2__my__mods">Unique value assigned to your mod</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">highscoreID</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"fads780324n23"</div>
                        <div class="col-sm-4">
                            Ask a site moderator for one
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
    "modIdentifier": "7689asdfjh1231",
    "highscoreID": "fads780324n23",
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
      "userName": "BMD",
      "steamID32": 28755156,
      "highscoreValue": 12321,
      "matchID": 112312321,
      "date_recorded": "2015-07-11 19:30:03"
    },
    {
      "userName": "jimmydorry",
      "steamID32": 28755155,
      "highscoreValue": 11111,
      "date_recorded": "2015-07-11 19:30:22"
    },
    {
      "userName": "jimmydorry",
      "steamID32": 28755155,
      "highscoreValue": 9999,
      "date_recorded": "2015-07-11 19:08:43"
    },
    {
      "userName": "BMD",
      "steamID32": 28755156,
      "highscoreValue": 1234,
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

                <p><strong>Endpoint:</strong> <code>POST https://api.getdotastats.com/s2_highscore.php || "payload" =
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
                        <div class="col-sm-3">modIdentifier</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"7689asdfjh1231"</div>
                        <div class="col-sm-4">
                            <a class="nav-clickable" href="#s2__my__mods">Unique value assigned to your mod</a>
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
    "modIdentifier": "7689asdfjh1231",
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
      "highscoreValue": 12321,
      "highscoreAuthKey": "XXXXXXXX",
      "date_recorded": "2015-07-11 19:30:22"
    },
    {
      "highscoreID": "hj43152khjb342",
      "highscoreValue": 12321,
      "highscoreAuthKey": "XXXXXXXX",
      "matchID": "12315415",
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

    $memcached = new Cache(NULL, NULL, $localDev);

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
              shms.`highscoreDecimals`,

              ml.`mod_name`
            FROM `stat_highscore_mods` shm
            JOIN `stat_highscore_mods_schema` shms ON shm.`highscoreID` = shms.`highscoreID`
            JOIN `mod_list` ml ON shm.`modID` = ml.`mod_id`
            ORDER BY shm.`date_recorded` DESC
            LIMIT 0,5;',
        NULL,
        NULL,
        5
    );

    if (!empty($latestData)) {
        echo '<div class="row">
                    <div class="col-sm-3"><strong>Highscore</strong></div>
                    <div class="col-sm-3"><strong>Mod</strong></div>
                    <div class="col-sm-4">
                        <div class="row">
                            <div class="col-sm-4 text-center"><strong>Steam ID</strong></div>
                            <div class="col-sm-4 text-center"><strong>Player</strong></div>
                            <div class="col-sm-4 text-center"><strong>Value</strong></div>
                        </div>
                    </div>
                    <div class="col-sm-2 text-center"><strong>Recorded</strong></div>
                </div>
                <span class="h4">&nbsp;</span>
                ';

        foreach ($latestData as $key => $value) {
            $relativeDateRaw = relative_time_v3($value['date_recorded'], 1, 'hour', true);

            $timeColour = $relativeDateRaw['number'] <= 2
                ? ' hs_lb_recent_score'
                : '';

            echo '<div class="row">
                <div class="col-sm-3"><span>' . $value['highscoreName'] . '</span></div>
                <div class="col-sm-3"><span>' . $value['mod_name'] . '</span></div>
                <div class="col-sm-4">
                    <div class="row">
                        <div class="col-sm-4">' . $value['steamID32'] . '</div>
                        <div class="col-sm-4">' . $value['userName'] . '</div>
                        <div class="col-sm-4">' . number_format($value['highscoreValue']) . '</div>
                    </div>
                </div>
                <div class="col-sm-2 text-right"><span class="' . $timeColour . '">' . relative_time_v3($value['date_recorded'], 1) . '</span></div>
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
    if (isset($memcached)) $memcached->close();
}