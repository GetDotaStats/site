<?php
$start = time();

$max_r = 200000;
$max_h = $max_g = 100;

if (
    !isset($_GET['h']) || !is_numeric($_GET['h'])
    || !isset($_GET['r']) || !is_numeric($_GET['r'])
    || !isset($_GET['g']) || !is_numeric($_GET['g'])
    || !isset($_GET['asl']) || !is_numeric($_GET['asl'])
    || !isset($_GET['t_min']) || !is_numeric($_GET['t_min'])
    || !isset($_GET['t_max']) || !is_numeric($_GET['t_max'])

    || ($_GET['h'] > $max_h || $_GET['h'] < 1)
    || ($_GET['r'] > $max_r || $_GET['r'] < 1)
    || ($_GET['g'] > $max_g || $_GET['g'] < 1)
    || ($_GET['asl'] > 4 || $_GET['asl'] < 1)
    || ($_GET['t_min'] > 1 || $_GET['t_min'] < 0.01)
    || ($_GET['t_max'] > 1 || $_GET['t_max'] < 0.01)

    || ($_GET['t_min'] > $_GET['t_max'])
    || ($_GET['r'] > ($max_r / 2) && $_GET['h'] > ($max_h / 2))
) {
    header("Location: ./?h=" . ($max_h / 4) . "&r=" . ($max_r / 4) . "&g=5&asl=1&t_min=0.01&t_max=0.03");
}

include('./chart.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Axe Helix Simulation</title>
    <script type="text/javascript" src="//www.google.com/jsapi"></script>
</head>

<body>
<?php
$rd = 0.15;
$prd_c = 0.03221;

$hits = $_GET['h'];
$reps = $_GET['r'];
$groups_hits = $_GET['g'];
$axe_spin_level = $_GET['asl'];
$time_since_last_spin_min = $_GET['t_min'];
$time_since_last_spin_max = $_GET['t_max'];

$axe_spin_dmg_array = array(
    1 => 100,
    2 => 135,
    3 => 170,
    4 => 205
);
$axe_spin_damage = $axe_spin_dmg_array[$axe_spin_level];

$axe_spin_cd_array = array(
    1 => 0.45,
    2 => 0.4,
    3 => 0.35,
    4 => 0.3
);
$axe_spin_cd = $axe_spin_cd_array[$axe_spin_level];

$chart = new Chart('ColumnChart');

$options = array(
    //'title' => 'Average spins in ' . $hits . ' attacks',
    //'theme' => 'maximized',
    'axisTitlesPosition' => 'in',
    'width' => 600,
    'height' => 260,
    'chartArea' => array(
        'width' => '100%',
        'left' => 60
    ),
    'hAxis' => array(
        'title' => 'Spins',
        'maxAlternation' => 1,
        //'textPosition' => 'in',
        //'viewWindowMode' => 'maximized'
    ),
    'vAxis' => array(
        'title' => 'Frequency',
        //'textPosition' => 'in',
    ),
    'legend' => array(
        'position' => 'bottom',
        'textStyle' => array(
            'fontSize' => 10
        )
    ));

$optionsDataTable = array(
    'width' => 600,
    'sortColumn' => 0,
    'sortAscending' => true,
    'alternatingRowStyle' => true,
    'page' => 'enable',
    'pageSize' => 6);
?>
<div id="about" style="width: 600px;">
    <h2>About</h2>

    <p>This page simulates the number of spins Axe would get under the old flat Random Distribution and under the new
        Pseudo Random distribution. It uses the same probability seed for the two scenarios. Unfortunately there is no
        readily available C constant for 17%, so this simulation assumes 20% helix chance as it has a known C constant
        defined.</p>
</div>
<form action="" method="get">
    <table border="1" cellspacing="1">
        <tr>
            <th align="left">Hits</th>
            <td colspan="2"><input name="h" type="number" min="1" max="<?= $max_h ?>" value="<?= $_GET['h'] ?>" required></td>
        </tr>
        <tr>
            <th align="left">Time Between (secs)</th>
            <td><input name="t_min" type="number" min="0.01" max="1" value="<?= $_GET['t_min'] ?>" step="0.01" required>min</td>
            <td><input name="t_max" type="number" min="0.01" max="1" value="<?= $_GET['t_max'] ?>" step="0.01" required>max</td>
        </tr>
        <tr>
            <th align="left">Repetitions</th>
            <td colspan="2"><input name="r" type="number" min="1" max="<?= $max_r ?>" value="<?= $_GET['r'] ?>" required></td>
        </tr>
        <tr>
            <th align="left">Groups</th>
            <td colspan="2"><input name="g" type="number" min="1" max="<?= $max_g ?>" value="<?= $_GET['g'] ?>" required></td>
        </tr>
        <tr>
            <th align="left">Counter Helix</th>
            <td colspan="2"><input name="asl" type="number" min="1" max="4" value="<?= $_GET['asl'] ?>" required>level</td>
        </tr>
        <tr>
            <td colspan="4" align="center"><input type="submit" value="Simulate"></td>
        </tr>
    </table>
</form>
<div id="form_nag" style="width: 600px;font-size: 12px;">
    In the interest of saving CPU cycles, you can't have more than <?= number_format($max_h / 2, 0) ?>hits
    and <?= number_format($max_r / 2, 0) ?>reps at the same time. As long as either is below that level, the other can
    be set up to the maximum of <?= number_format($max_h, 0) ?> and <?= number_format($max_r, 0) ?> respectively.
</div>
<hr/>
<?php
$successes_rd = 0;
$successes_prd = 0;

$success_temp_rd = 0;
$success_temp_prd = 0;

$temp_prd = $temp_rd = 0;

$array_spins_graph = array();
for ($i = 1; $i <= $hits / 3; $i++) {
    $array_spins_graph[$i]['spins'] = $i;
    $array_spins_graph[$i]['rd'] = 0;
    $array_spins_graph[$i]['prd'] = 0;
}

$array_damage_graph = array();

for ($o = 0; $o < $reps; $o++) {

    $damage_temp_rd = 0;
    $damage_temp_prd = 0;

    $prd = $prd_c;
    $time_since_spin_rd = 0;
    $time_since_spin_prd = 0;
    for ($i = 1; $i <= $hits; $i++) {
        $rand = rand(0, 100) / 100; //ROLLING DICE TO SEE IF SPIN
        $rand_spin_time = rand($time_since_last_spin_min*1000, $time_since_last_spin_max*1000)/1000; //TIME SINCE LAST HIT

        //DO THE RANDOM DISTRIBUTION CHECK
        if ($rand < $rd && $time_since_spin_rd >= $axe_spin_cd) {
            $successes_rd++;
            $temp_rd++;

            $damage_temp_rd += $axe_spin_damage;
            $time_since_spin_rd = 0;
        } else {
            $time_since_spin_rd += $rand_spin_time;
        }

        //DO THE PSEUDO RANDOM CHECK
        if ($rand < $prd && $time_since_spin_prd >= $axe_spin_cd) {
            $prd = $prd_c;
            $successes_prd++;
            $temp_prd++;

            $damage_temp_prd += $axe_spin_damage;
            $time_since_spin_prd = 0;
        }
        else if($time_since_spin_prd >= $axe_spin_cd){
            $prd += $prd_c;
            $time_since_spin_prd += $rand_spin_time;
        }
        else {
            $time_since_spin_prd += $rand_spin_time;
        }

        //TALLY THE DAMAGE COUNTS OVER THE SELECTED GROUPING
        if ($i % $groups_hits == 0) {
            //Make sure to define 0 for RD and PRD if not already defined. Can't increment NULL #PHPProblems
            if (!isset($array_damage_graph[$damage_temp_rd]['rd'])) $array_damage_graph[$damage_temp_rd]['rd'] = 0;
            if (!isset($array_damage_graph[$damage_temp_rd]['prd'])) $array_damage_graph[$damage_temp_rd]['prd'] = 0;

            if (!isset($array_damage_graph[$damage_temp_prd]['rd'])) $array_damage_graph[$damage_temp_prd]['rd'] = 0;
            if (!isset($array_damage_graph[$damage_temp_prd]['prd'])) $array_damage_graph[$damage_temp_prd]['prd'] = 0;

            $array_damage_graph[$damage_temp_rd]['rd'] += 1;
            $array_damage_graph[$damage_temp_prd]['prd'] += 1;

            $damage_temp_rd = $damage_temp_prd = 0; //DON'T FORGET TO RESTART THE DAMAGE COUNT
        }

        //echo '<br />';
    }

    //echo $temp_rd . ' - ' . $temp_prd . '<br />';

    //RD
    if (!isset($array_spins_graph[$temp_rd]['spins'])) $array_spins_graph[$temp_rd]['spins'] = $temp_rd;
    if (!isset($array_spins_graph[$temp_rd]['rd'])) $array_spins_graph[$temp_rd]['rd'] = 0;
    if (!isset($array_spins_graph[$temp_rd]['prd'])) $array_spins_graph[$temp_rd]['prd'] = 0;
    $array_spins_graph[$temp_rd]['rd'] += 1;
    //

    //PRD
    if (!isset($array_spins_graph[$temp_prd]['spins'])) $array_spins_graph[$temp_prd]['spins'] = $temp_prd;
    if (!isset($array_spins_graph[$temp_prd]['rd'])) $array_spins_graph[$temp_prd]['rd'] = 0;
    if (!isset($array_spins_graph[$temp_prd]['prd'])) $array_spins_graph[$temp_prd]['prd'] = 0;
    $array_spins_graph[$temp_prd]['prd'] += 1;
    //

    $temp_rd = 0;
    $temp_prd = 0;
}

ksort($array_damage_graph);
ksort($array_spins_graph);

echo '<h2>Spins in ' . $hits . 'hits taken ('.number_format($reps,0).'reps)</h2>';
echo '<div id="about_spins" style="width: 600px;font-size: 12px;">Averaged over the repetitions. The total number of spins was recorded for every repetition, and the frequency of that number of spins occuring was recorded below.</div>';
echo '
<table border="1">
    <tr align="center">
        <th>&nbsp;</th>
        <th>Spins</th>
        <th>Avg. Spins</th>
        <th>Damage</th>
    </tr>
    <tr align="center">
        <th>Random (RD)</th>
        <td>' . number_format($successes_rd, 0) . '</td>
        <td>' . number_format(($successes_rd / $reps), 1) . '</td>
        <td>'.number_format(($successes_rd / $reps * $axe_spin_damage),0).'</td>
    </tr>
    <tr align="center">
        <th>Pseudo (PRD)</th>
         <td>' . number_format($successes_prd, 0) . '</td>
       <td>' . number_format(($successes_prd / $reps), 1) . ' </td>
        <td>'.number_format(($successes_prd / $reps * $axe_spin_damage),0).'</td>
    </tr>
</table>';

$super_array = array();
foreach ($array_spins_graph as $key => $value) {
    $super_array[] = array('c' => array(array('v' => $value['spins']), array('v' => $value['rd']), array('v' => $value['prd'])));
}

$data = array(
    'cols' => array(
        array('id' => '', 'label' => 'Spins', 'type' => 'string'),
        array('id' => '', 'label' => 'RD', 'type' => 'number'),
        array('id' => '', 'label' => 'PRD', 'type' => 'number')
    ),
    'rows' => $super_array
);
$chart->load(json_encode($data));
$options['hAxis']['title'] = 'Spins';
echo $chart->draw('spin_chart', $options, true, $optionsDataTable);
?>
<div id="spin_chart"></div>
<div id="spin_chart_dataTable"></div>

<?= '<hr />' ?>

<?php
$total_damage_rd = 0;
$total_damage_prd = 0;
foreach ($array_damage_graph as $key => $value) {
    $total_damage_rd += $key * $value['rd'];
    $total_damage_prd += $key * $value['prd'];
}

echo '<h2>Damage dealt in groups of ' . $groups_hits . 'hits from ' . $hits . 'hits ('.number_format($reps,0).'reps)</h2>';
echo '<div id="about_spins" style="width: 600px;font-size: 12px;">Averaged over the repetitions. The damage dealt was tallied over every group of hits, and the frequency of that damage occuring was tallied below.</div>';
echo '
<table border="1">
    <tr align="center">
        <th>&nbsp;</th>
        <th>Damage</th>
    </tr>
    <tr align="center">
        <th>Random (RD)</th>
        <td>' . number_format(($total_damage_rd / ($reps * ($hits / $groups_hits))), 1) . '</td>
    </tr>
    <tr align="center">
        <th>Pseudo (PRD)</th>
        <td>'.number_format(($total_damage_prd / ($reps * ($hits / $groups_hits))), 1).'</td>
    </tr>
</table>';

$damage_array_graph = array();
foreach ($array_damage_graph as $key => $value) {
    $damage_array_graph[] = array('c' => array(array('v' => $key), array('v' => $value['rd']), array('v' => $value['prd'])));
}

$data = array(
    'cols' => array(
        array('id' => '', 'label' => 'Damage', 'type' => 'string'),
        array('id' => '', 'label' => 'RD', 'type' => 'number'),
        array('id' => '', 'label' => 'PRD', 'type' => 'number')
    ),
    'rows' => $damage_array_graph
);
$chart->load(json_encode($data));
$options['hAxis']['title'] = 'Damage';
echo $chart->draw('damage_chart', $options, true, $optionsDataTable);
?>
<div id="damage_chart"></div>
<div id="damage_chart_dataTable"></div>

<hr/>

<div id="resources">
    <h2>Resources</h2>
    <a href="http://www.playdota.com/forums/showthread.php?t=7993">http://www.playdota.com/forums/showthread.php?t=7993</a>
    <br/>
    <a href="http://dota2.gamepedia.com/Pseudo-random_distribution">http://dota2.gamepedia.com/Pseudo-random_distribution</a>
    http://www.playdota.com/forums/showthread.php?t=1386680
    http://dev.dota2.com/showthread.php?t=72983
</div>

<div id="pagerendertime" style="font-size: 12px;"><?= '<hr />Page generated in ' . (time() - $start) . 'secs' ?> || <a
        href="">Link ME</a> || Lovingly crafted by <a href="http://reddit.com/u/jimmydorry" target="__new">jimmydorry</a> || <a href="https://github.com/jimmydorry/axe_spins_sim/issues" target="__new">Issues/Feature Requests here</a>
</div>
</body>
</html>