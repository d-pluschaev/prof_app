<?php

$dWidth = 100;
$dHeight = 100;

$diff = array(
    'Request time' => $this->diffSummary['time'],
    'HTML Footer Time' => $this->diffSummary['html_footer_time'],
);
foreach ($this->diffSummary['functions'] as $func => $data) {
    $diff[$func . ' <span class="diff_func_type_marker" title="Number of calls">CT</span>'] = $data['ct'];
    //$diff[$func.' <span class="diff_func_type_marker" title="Wall time">WT</span>']=$data['wt'];
}


$index = 0;
$max = 0;
$totalPercentage = $improvementSum = $regressionSum = 0;
foreach ($diff as $k => $v) {
    if ($v['s'] == 0) {
        $diff[$k]['k'] = $v['s'] === $v['t'] ? 0 : -1;
    } elseif ($v['t'] == 0) {
        $diff[$k]['k'] = $v['s'] === $v['t'] ? 0 : 1;
    } else {
        $diff[$k]['k'] = ($v['s'] / $v['t']) - 1;
    }

    $totalPercentage += $diff[$k]['k'];
    $improvementSum += $diff[$k]['k'] > 0 ? 0 : abs($diff[$k]['k']);
    $regressionSum += $diff[$k]['k'] < 0 ? 0 : $diff[$k]['k'];
    $diff[$k]['i'] = $index;
    $max = abs($diff[$k]['k']) > $max ? abs($diff[$k]['k']) : $max;
    $index++;
}
$correctionK = 1;
if ($max > 1) {
    $correctionK = 1 / $max;
}

$diffSize = sizeof($diff);

?>



<table class="diff_diagram" cellpadding="0" cellspacing="0" style="width:<?=$dWidth?>%;">
    <tr>
        <?foreach ($diff as $k => $v) { ?>
        <td style="width:<?=($dWidth / $diffSize)?>%;height:<?=($dHeight / 2) + 20?>px;">
            <?if ($v['k'] >= 0) {
            $val = round(abs($v['k']) * 100, 2);
            ?>
            <div class="bar_label"><?=$val > 0 ? '+' . $val : 0?>%</div>
            <div class="box_regression" style="height:<?=round(abs($v['k']) * $correctionK * $dHeight / 2)?>px"></div>
            <? } else { ?>
            &nbsp;
            <? }?>
        </td>
        <? }?>

        <td>
            <div class="bar" style="height:<?=$dHeight + 40?>px;">
                <div class="bar_track" style="height:<?=round(
                    ($dHeight + 40) * ($regressionSum / ($improvementSum + $regressionSum))
                )?>px;"></div>

                <div class="bar_track improvement" style="height:<?=round(
                    ($dHeight + 40) * ($improvementSum / ($improvementSum + $regressionSum))
                ) - 1?>px;top:<?=round(
                    ($dHeight + 40) * ($regressionSum / ($improvementSum + $regressionSum))
                )?>px"></div>

                <?if ($regressionSum < $improvementSum) { ?>
                <div class="bar_track hover improvement" style="height:<?=floor(($dHeight / 2) + 20) - floor(
                    ($dHeight + 40) * ($regressionSum / ($improvementSum + $regressionSum))
                ) - 1?>px;top:<?=floor(
                    ($dHeight + 40) * ($regressionSum / ($improvementSum + $regressionSum))
                )?>px"></div>
                <? } else { ?>
                <div class="bar_track hover" style="height:<?=abs(floor(($dHeight / 2) + 20) - floor(
                    ($dHeight + 40) * ($regressionSum / ($improvementSum + $regressionSum))
                ) + 1)?>px;top:<?=floor((($dHeight / 2) + 20)) + 1?>px"></div>
                <? }?>

                <div class="label_r<?=$totalPercentage >= 0 ? '' : ' inactive'?>">
                    Regression<?=$totalPercentage > 0 ? ': ~' . round($totalPercentage * 100 / $diffSize, 2) . '%' : ''?>
                </div>
                <div class="label_i<?=$totalPercentage <= 0 ? '' : ' inactive'?>">
                    Improvement<?=$totalPercentage < 0 ? ': ~' . abs(round($totalPercentage * 100 / $diffSize, 2)) . '%' : ''?>
                </div>
            </div>

            <div>&nbsp;</div>
        </td>
    </tr>
    <tr>
        <?foreach ($diff as $k => $v) { ?>
        <td style="width:<?=($dWidth / $diffSize)?>%;height:<?=($dHeight / 2) + 20?>px;">
            <?if ($v['k'] < 0) { ?>
            <div class="box_improvement" style="height:<?=round(abs($v['k']) * $correctionK * $dHeight / 2)?>px"></div>
            <div class="bar_label">-<?=round(abs($v['k']) * 100, 2)?>%</div>
            <? } else { ?>
            &nbsp;
            <? }?>
        </td>
        <? }?>

        <td>&nbsp;</td>
    </tr>

    <tr>
        <?foreach ($diff as $k => $v) { ?>
        <td><?=$k?></td>
        <? }?>

        <td>Conclusion</td>
    </tr>
</table>