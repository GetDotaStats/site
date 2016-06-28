<div class="page-header">
    <h2>Schema for stat-save
        <small>BETA</small>
    </h2>
</div>

<p>Below is the workflow in terms of data communicated between host and GDS through when saving user data.</p>

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

                <p>This is for saving user data.</p>

                <p>If you have not secured your saves, then you do not need the `5` (`userAuth`) field.</p>

                <p><strong>Endpoint:</strong> <code>POST https://api.getdotastats.com/s2_save.php || "payload" =
                        <em>JSONschema</em></code>
                </p>

                <hr/>

                <div>
                    <div class="row">
                        <div class="col-sm-1"><strong>Key</strong></div>
                        <div class="col-sm-2"><strong>Name</strong></div>
                        <div class="col-sm-1"><strong>Type</strong></div>
                        <div class="col-sm-2"><strong>Example</strong></div>
                        <div class="col-sm-6"><strong>Notes</strong></div>
                    </div>
                    <span class="h4">&nbsp;</span>

                    <div class="row">
                        <div class="col-sm-1">t</div>
                        <div class="col-sm-2">type</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"SAVE"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">1</div>
                        <div class="col-sm-2">modID</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"7689asdfjh1231"</div>
                        <div class="col-sm-6">
                            <a class="nav-clickable" href="#s2__my__mods">Unique value assigned to your mod</a>.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">2</div>
                        <div class="col-sm-2">saveTypeID</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"fads780324n23"</div>
                        <div class="col-sm-6">
                            Ask a site moderator for one.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">3</div>
                        <div class="col-sm-2">steamID32</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"28755155"</div>
                        <div class="col-sm-6">
                            The user that this save is bound to.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">4</div>
                        <div class="col-sm-2">userName</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"jimmydorry"</div>
                        <div class="col-sm-6">
                            The username of the user assigned to this save.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">5</div>
                        <div class="col-sm-2">saveValue</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"Has a pretty hat."</div>
                        <div class="col-sm-6">
                            The unstructured value of the save. The modder can choose to use JSON to store complex
                            objects, if they want.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">6</div>
                        <div class="col-sm-2">saveID</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"hnjkln123nj12kln"</div>
                        <div class="col-sm-6">
                            <strong>OPTIONAL FIELD</strong>. If not defined or is NULL, then a new save is made.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">7</div>
                        <div class="col-sm-2">userAuth</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"XKAFD"</div>
                        <div class="col-sm-6">
                            <strong>OPTIONAL FIELD</strong> (see method description). Leave undefined or NULL if this is
                            a new save, or the saves are not secured by userAuth. If user saves are secured by userAuth,
                            this code is required when over-writing an existing save.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">8</div>
                        <div class="col-sm-2">matchID</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"1231241242"</div>
                        <div class="col-sm-6">
                            <strong>OPTIONAL FIELD</strong> The matchID that this save was created from.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">v</div>
                        <div class="col-sm-2">schemaVersion</div>
                        <div class="col-sm-1">integer</div>
                        <div class="col-sm-2">1</div>
                        <div class="col-sm-6">
                            Denotes which version of communication is used.
                        </div>
                    </div>

                    <span class="h4">&nbsp;</span>
                </div>

                <hr/>

            <pre class="pre-scrollable">
{
    "t": "SAVE",
    "1": "7689asdfjh1231",
    "2": "fads780324n23",
    "3": "28755155",
    "4": "jimmydorry",
    "5": "Has a pretty hat.",
    "6": "hnjkln123nj12kln",
    "7": "XJAHVAS",
    "8": "1235124321",
    "v": 1
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

                <div>
                    <div class="row">
                        <div class="col-sm-1"><strong>Key</strong></div>
                        <div class="col-sm-2"><strong>Name</strong></div>
                        <div class="col-sm-1"><strong>Type</strong></div>
                        <div class="col-sm-2"><strong>Example</strong></div>
                        <div class="col-sm-6"><strong>Notes</strong></div>
                    </div>
                    <span class="h4">&nbsp;</span>

                    <div class="row">
                        <div class="col-sm-1">t</div>
                        <div class="col-sm-2">type</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"load"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">r</div>
                        <div class="col-sm-2">result</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">1 or 0</div>
                        <div class="col-sm-6">
                            Has a value of "1" if there is no significant error.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">e</div>
                        <div class="col-sm-2">errorMessage</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"Invalid saveID!"</div>
                        <div class="col-sm-6">
                            Only important if the `r` field is not equal to 1.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">1</div>
                        <div class="col-sm-2">authKey</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"XXXXXX"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">v</div>
                        <div class="col-sm-2">schemaVersion</div>
                        <div class="col-sm-1">integer</div>
                        <div class="col-sm-2">1</div>
                        <div class="col-sm-6">
                            Denotes which version of communication is used.
                        </div>
                    </div>

                    <span class="h4">&nbsp;</span>
                </div>

                <hr/>

            <pre class="pre-scrollable">
{
  "t": "save",
  "r": 1,
  "e": "Invalid JSON!",
  "v": 1,
  "1": "XXXXXX"
}</pre>
            </div>
        </div>
    </div>


    <!--
    ////////////////////////////////////////////////////
    // Method "LOAD" - Client
    ////////////////////////////////////////////////////
    -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingThree">
            <h4 class="panel-title">
                <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                   href="#collapseThree"
                   aria-expanded="false" aria-controls="collapseThree">
                    Method "LOAD" - Client
                </a>
            </h4>
        </div>
        <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
            <div class="panel-body">

                <p>This is for loading user data for a specified mod. Keep in mind that a mod can only have <strong>three</strong>
                    different save types (i.e. hero data, user preferences, hero performance) active at the same time.
                    Each user can only have <strong>five</strong> save instances per save type.</p>

                <p>If you have not secured your saves, then you do not need the `5` (`userAuth`) field.</p>

                <p><strong>Endpoint:</strong> <code>POST https://api.getdotastats.com/s2_save.php || "payload" =
                        <em>JSONschema</em></code>
                </p>

                <hr/>

                <div>
                    <div class="row">
                        <div class="col-sm-1"><strong>Key</strong></div>
                        <div class="col-sm-2"><strong>Name</strong></div>
                        <div class="col-sm-1"><strong>Type</strong></div>
                        <div class="col-sm-2"><strong>Example</strong></div>
                        <div class="col-sm-6"><strong>Notes</strong></div>
                    </div>
                    <span class="h4">&nbsp;</span>

                    <div class="row">
                        <div class="col-sm-1">t</div>
                        <div class="col-sm-2">type</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"LOAD"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">1</div>
                        <div class="col-sm-2">modID</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"7689asdfjh1231"</div>
                        <div class="col-sm-6">
                            <a class="nav-clickable" href="#s2__my__mods">Unique value assigned to your mod</a>.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">2</div>
                        <div class="col-sm-2">saveTypeID</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"fads780324n23"</div>
                        <div class="col-sm-6">
                            Ask a site moderator for one.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">3</div>
                        <div class="col-sm-2">saveID</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"hnjkln123nj12kln"</div>
                        <div class="col-sm-6">
                            Returned when saving or in the listing call.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">4</div>
                        <div class="col-sm-2">steamID32</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"12341245"</div>
                        <div class="col-sm-6">
                            The user that this save is bound to.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">5</div>
                        <div class="col-sm-2">userAuth</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"XKAFD"</div>
                        <div class="col-sm-6">
                            <strong>OPTIONAL FIELD</strong> (see method description). Returned when saving.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">v</div>
                        <div class="col-sm-2">schemaVersion</div>
                        <div class="col-sm-1">integer</div>
                        <div class="col-sm-2">1</div>
                        <div class="col-sm-6">
                            Denotes which version of communication is used.
                        </div>
                    </div>

                    <span class="h4">&nbsp;</span>
                </div>

                <hr/>

            <pre class="pre-scrollable">
{
    "t": "LOAD",
    "1": "7689asdfjh1231",
    "2": "fads780324n23",
    "3": "hnjkln123nj12kln",
    "4": "12341245",
    "5": "XKAFD",
    "v": 1
}</pre>

            </div>
        </div>
    </div>

    <!--
    ////////////////////////////////////////////////////
    // Method "LOAD" - Server
    ////////////////////////////////////////////////////
    -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingFour">
            <h4 class="panel-title">
                <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                   href="#collapseFour"
                   aria-expanded="false" aria-controls="collapseFour">
                    Method "LOAD" - Server
                </a>
            </h4>
        </div>
        <div id="collapseFour" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
            <div class="panel-body">
                <p>This is the response from the server after receiving the data for the LOAD method.</p>

                <p>A success will have result=1, where as a failure will have result=0 with an error field. You may want
                    to display the error to users so they know what happened.</p>

                <div>
                    <div class="row">
                        <div class="col-sm-1"><strong>Key</strong></div>
                        <div class="col-sm-2"><strong>Name</strong></div>
                        <div class="col-sm-1"><strong>Type</strong></div>
                        <div class="col-sm-2"><strong>Example</strong></div>
                        <div class="col-sm-6"><strong>Notes</strong></div>
                    </div>
                    <span class="h4">&nbsp;</span>

                    <div class="row">
                        <div class="col-sm-1">t</div>
                        <div class="col-sm-2">type</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"load"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">r</div>
                        <div class="col-sm-2">result</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">1 or 0</div>
                        <div class="col-sm-6">
                            Has a value of "1" if there is no significant error.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">e</div>
                        <div class="col-sm-2">errorMessage</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"Invalid saveID!"</div>
                        <div class="col-sm-6">
                            Only important if the `r` field is not equal to 1.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">1</div>
                        <div class="col-sm-2">userName</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"jimmydorry"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">2</div>
                        <div class="col-sm-2">steamID32</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"28755155"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">3</div>
                        <div class="col-sm-2">saveValue</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"hnjkln123nj12kln"</div>
                        <div class="col-sm-6">
                            Unstructured value that matches what is stored. For complex objects, use JSON.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">4</div>
                        <div class="col-sm-2">matchID</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"112312321"</div>
                        <div class="col-sm-6">
                            The match that this save was made from.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">v</div>
                        <div class="col-sm-2">schemaVersion</div>
                        <div class="col-sm-1">integer</div>
                        <div class="col-sm-2">1</div>
                        <div class="col-sm-6">
                            Denotes which version of communication is used.
                        </div>
                    </div>

                    <span class="h4">&nbsp;</span>
                </div>

                <hr/>

            <pre class="pre-scrollable">
{
  "t": "load",
  "r": 1,
  "e": "Invalid saveID!",
  "v": 1,
  "1": "BMD",
  "2": 28755156,
  "3": 12321,
  "4": 112312321,
  "5": "2015-07-11 19:30:03"
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
                        <div class="col-sm-1"><strong>Key</strong></div>
                        <div class="col-sm-2"><strong>Name</strong></div>
                        <div class="col-sm-2"><strong>Type</strong></div>
                        <div class="col-sm-3"><strong>Example</strong></div>
                        <div class="col-sm-4"><strong>Notes</strong></div>
                    </div>
                    <span class="h4">&nbsp;</span>

                    <div class="row">
                        <div class="col-sm-1">t</div>
                        <div class="col-sm-2">type</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-7">"LIST"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">1</div>
                        <div class="col-sm-2">modID</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-3">"7689asdfjh1231"</div>
                        <div class="col-sm-4">
                            <a class="nav-clickable" href="#s2__my__mods">Unique value assigned to your mod</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">2</div>
                        <div class="col-sm-2">steamID32</div>
                        <div class="col-sm-2">string</div>
                        <div class="col-sm-7">"28755155"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">v</div>
                        <div class="col-sm-2">schemaVersion</div>
                        <div class="col-sm-2">integer</div>
                        <div class="col-sm-7">1</div>
                    </div>

                    <span class="h4">&nbsp;</span>
                </div>

                <hr/>

            <pre class="pre-scrollable">
{
    "t": "LIST",
    "1": "7689asdfjh1231",
    "2": "28755155",
    "v": 1
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

                <div>
                    <div class="row">
                        <div class="col-sm-1"><strong>Key</strong></div>
                        <div class="col-sm-2"><strong>Name</strong></div>
                        <div class="col-sm-1"><strong>Type</strong></div>
                        <div class="col-sm-2"><strong>Example</strong></div>
                        <div class="col-sm-6"><strong>Notes</strong></div>
                    </div>
                    <span class="h4">&nbsp;</span>

                    <div class="row">
                        <div class="col-sm-1">t</div>
                        <div class="col-sm-2">type</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"load"</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">r</div>
                        <div class="col-sm-2">result</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">1 or 0</div>
                        <div class="col-sm-6">
                            Has a value of "1" if there is no significant error.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">e</div>
                        <div class="col-sm-2">errorMessage</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"Invalid saveID!"</div>
                        <div class="col-sm-6">
                            Only important if the `r` field is not equal to 1.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">v</div>
                        <div class="col-sm-2">schemaVersion</div>
                        <div class="col-sm-1">integer</div>
                        <div class="col-sm-2">1</div>
                        <div class="col-sm-6">
                            Denotes which version of communication is used.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">d</div>
                        <div class="col-sm-2">saveData</div>
                        <div class="col-sm-1">array</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">1</div>
                        <div class="col-sm-2">saveID</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"h2345kjn52314"</div>
                        <div class="col-sm-6">
                            Used in the LOAD or SAVE methods.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">2</div>
                        <div class="col-sm-2">saveValue</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"Has a nice hat"</div>
                        <div class="col-sm-6">
                            The unstructured save value.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">3</div>
                        <div class="col-sm-2">matchID</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"1231241242"</div>
                        <div class="col-sm-6">
                            The match this save is associated with.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-1">4</div>
                        <div class="col-sm-2">dateRecorded</div>
                        <div class="col-sm-1">string</div>
                        <div class="col-sm-2">"2015-07-11 19:30:22"</div>
                        <div class="col-sm-6">
                            When this save was recorded.
                        </div>
                    </div>

                    <span class="h4">&nbsp;</span>
                </div>

                <hr/>

            <pre class="pre-scrollable">
{
  "t": "list",
  "r": 1,
  "e": "No saves found for that user!",
  "v": 1,
  "d": [
    {
      "1": "h2345kjn52314",
      "2": 12321,
      "3": "1231241242",
      "4": "2015-07-11 19:30:22"
    },
    {
      "1": "asfdasdf213412",
      "2": "Has a nice hat",
      "3": "12315415",
      "4": "2015-07-11 19:08:43"
    }
  ]
}</pre>
            </div>
        </div>
    </div>


</div>

<hr/>


<h3>Latest Save Data</h3>
<p>Below is a simple table showing the five latest saves recorded. It will serve as a debugging tool while we setup
    a more sophisticated testing environment.</p>

<?php
/*require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    $latestData = cached_query('s2_highscore_latest_data',
        'SELECT
              shm.`modID`,
              shm.`highscoreID`,
              shm.`matchID`,
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
                    <div class="col-sm-2"><strong>Highscore</strong></div>
                    <div class="col-sm-3"><strong>Mod</strong></div>
                    <div class="col-sm-2"><strong>Match</strong></div>
                    <div class="col-sm-3">
                        <div class="row">
                            <div class="col-sm-4 text-center"><strong>Steam ID</strong></div>
                            <div class="col-sm-5 text-center"><strong>Player</strong></div>
                            <div class="col-sm-3 text-center"><strong>Value</strong></div>
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
                <div class="col-sm-2"><span>' . $value['highscoreName'] . '</span></div>
                <div class="col-sm-3"><span>' . $value['mod_name'] . '</span></div>
                <div class="col-sm-2"><span>' . $value['matchID'] . '</span></div>
                <div class="col-sm-3">
                    <div class="row">
                        <div class="col-sm-4">' . $value['steamID32'] . '</div>
                        <div class="col-sm-5">' . $value['userName'] . '</div>
                        <div class="col-sm-3">' . number_format($value['highscoreValue']) . '</div>
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