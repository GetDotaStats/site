<?php
//DEPRECATED???
$version_specs = array();

$version_specs['dbe-standard']['version'] = '0.5.1';
$version_specs['dbe-standard']['released'] = '1383505322';
$version_specs['dbe-standard']['patch_notes'] = 'http://dotabuff.com/topics/2013-03-05-dotabuff-extended-mozilla-addon?page=2#comment-76099';
$version_specs['dbe-standard']['download_firefox'] = 'https://addons.mozilla.org/en-US/firefox/addon/dotabuff-extended/';
$version_specs['dbe-standard']['download_chrome'] = 'http://getdotastats.com/dbe/DotabuffExtended-0.5.1.crx';

$version_specs['dbe-vote']['version'] = '0.1';
$version_specs['dbe-vote']['released'] = '1383505322';
$version_specs['dbe-vote']['patch_notes'] = 'http://dotabuff.com/topics/2013-03-05-dotabuff-extended-mozilla-addon?page=2#comment-76099';
$version_specs['dbe-vote']['download_firefox'] = 'https://addons.mozilla.org/en-US/firefox/addon/dotabuff-extended-vote/';
$version_specs['dbe-vote']['download_chrome'] = 'http://getdotastats.com/dbe/DotabuffExtendedVote-0.1.crx';

echo json_encode($version_specs);
?>