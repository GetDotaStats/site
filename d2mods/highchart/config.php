<?php

/**
 * Paths and names for the javascript libraries needed by higcharts/highstock charts
 */

$CDNgeneric = '//static.getdotastats.com';
$imageCDN = '//dota2.photography';

$path_lib_jQuery = '/bootstrap/js/';
$path_lib_jQuery_name = 'jquery-1-3-2.min.js?20';
$path_lib_jQuery_full = $CDNgeneric . $path_lib_jQuery . $path_lib_jQuery_name;

$path_lib_jQuery2 = '/bootstrap/js/';
$path_lib_jQuery2_name = 'jquery-1-11-0.min.js?20';
$path_lib_jQuery2_full = $CDNgeneric . $path_lib_jQuery2 . $path_lib_jQuery2_name;

$path_lib_bootstrap = '/bootstrap/js/';
$path_lib_bootstrap_name = 'bootstrap.min.js?20';
$path_lib_bootstrap_full = $CDNgeneric . $path_lib_bootstrap . $path_lib_bootstrap_name;

$path_lib_respondJS = '/bootstrap/js/';
$path_lib_respondJS_name = 'respond-1-4-2.min.js?20';
$path_lib_respondJS_full = $CDNgeneric . $path_lib_respondJS . $path_lib_respondJS_name;

$path_lib_html5shivJS = '/bootstrap/js/';
$path_lib_html5shivJS_name = 'html5shiv-3-7-0.js?20';
$path_lib_html5shivJS_full = $CDNgeneric . $path_lib_html5shivJS . $path_lib_html5shivJS_name;

$path_lib_siteJS = '/';
$path_lib_siteJS_name = 'getdotastats.js?20';
$path_lib_siteJS_full = $CDNgeneric . $path_lib_siteJS . $path_lib_siteJS_name;

$path_lib_highcharts = '/bootstrap/js/';
$path_lib_highcharts_name = 'highcharts-4-1-4.js?20';
$path_lib_highcharts_full = $CDNgeneric . $path_lib_highcharts . $path_lib_highcharts_name;

$jsFiles = array(
    'jQuery' => array(
        'name' => $path_lib_jQuery_name,
        'path' => $CDNgeneric . $path_lib_jQuery
    ),

    /*'mootools' => array(
        'name' => 'mootools-yui-compressed.js',
        'path' => '//ajax.googleapis.com/ajax/libs/mootools/1.4.5/'
    ),

    'prototype' => array(
        'name' => 'prototype.js',
        'path' => '//ajax.googleapis.com/ajax/libs/prototype/1.7.0.0/'
    ),*/

    'highcharts' => array(
        'name' => $path_lib_highcharts_name,
        'path' => $CDNgeneric . $path_lib_highcharts
    ),

    /*'highchartsMootoolsAdapter' => array(
        'name' => 'mootools-adapter.js',
        'path' => '//code.highcharts.com/adapters/'
    ),

    'highchartsPrototypeAdapter' => array(
        'name' => 'prototype-adapter.js',
        'path' => '//code.highcharts.com/adapters/'
    ),

    'highstock' => array(
        'name' => 'highstock.js',
        'path' => '//code.highcharts.com/stock/'
    ),

    'highstockMootoolsAdapter' => array(
        'name' => 'mootools-adapter.js',
        'path' => '//code.highcharts.com/stock/adapters/'
    ),

    'highstockPrototypeAdapter' => array(
        'name' => 'prototype-adapter.js',
        'path' => '//code.highcharts.com/stock/adapters/'
    ),

    //Extra scripts used by Highcharts 3.0 charts
    'extra' => array(
        'highcharts-more' => array(
            'name' => 'highcharts-more.js',
            'path' => '//code.highcharts.com/'
        ),
        'exporting' => array(
            'name' => 'exporting.js',
            'path' => '//code.highcharts.com/modules/'
        ),
    )*/
);
