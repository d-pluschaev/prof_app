<?php

function toBytes($v, $level = 0)
{
    $v = intval($v);
    $e = array(' bytes', 'KB', 'MB', 'GB', 'TB');
    while ($level < sizeof($e) && $v >= 1024) {
        $v = $v / 1024;
        $level++;
    }
    return ($level > 0 ? round($v, 2) : $v) . $e[$level];
}

function microtimeToTimeUnits($seconds, $is_float = true, $full_info = false)
{
    $use_key = $is_float ? 'float' : 'int';
    $os = $seconds = floatval($seconds);
    $l = array('seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years', 'centuries');
    $fl = array(1, 60, 60 * 60, 60 * 60 * 24, 60 * 60 * 24 * 7, 60 * 60 * 24 * 30.5, 60 * 60 * 24 * 365, 60 * 60 * 24 * 365 * 100);
    $data = array('float' => array(), 'int' => array());
    for ($i = sizeof($l) - 1; $i >= 0; $i--) {
        $data['int'][$l[$i]] = floor($seconds / $fl[$i]);
        $seconds -= $data['int'][$l[$i]] * $fl[$i];
    }
    for ($i = sizeof($fl) - 1; $i >= 0; $i--) {
        if (($os / $fl[$i]) >= 1 || $i == 0) {
            $data['float'][$l[$i]] = $os / $fl[$i];
        }
    }
    $nearest_title = array_keys($data[$use_key]);
    $nearest_title = reset($nearest_title);
    $nearest_val = reset($data[$use_key]);
    $rnd = ($nearest_val / 10) >= 1 ? 0 : ($nearest_val < 3 ? 2 : 1);
    return !$full_info ? round($nearest_val, $rnd) . ' ' . $nearest_title : $data;
}

