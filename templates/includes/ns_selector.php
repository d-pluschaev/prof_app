<?
/*

need vars:

ns_status_label
ns_status
ns_namespace
ns_description
ns_link

 */

$redStar = '<span title="The field is required" class="required">*</span>';

?>

<div class="ns_selector">
    <form action="<?=$this->link($this->ns_link)?>" method="post">
        <div class="status"><?=$this->ns_status_label?> <span><?=$this->ns_status?></span></div>

        <div class="namespace">
            <?=$this->ns_status == 'ON' ? '' : $redStar?>
            Namespace:
            <input name="namespace" type="text" value="<?=$this->ns_namespace?>"
                <?=$this->ns_status == 'ON' ? 'readonly="readonly"' : ''?>/>
        </div>
        <div style="clear:both">
            <div class="description">
                Description:
            </div>
            <textarea name="description" rows="2" cols="20"
                <?=$this->ns_status == 'ON' ? 'readonly="readonly"' : ''?>
                    ><?=$this->ns_description?></textarea>
        </div>

        <?if ($this->ns_status == 'ON') { ?>
        <input type="submit" value="Turn OFF"/>
        <? } else { ?>
        <input type="submit" value="Create new namespace and turn ON"/>
        <? } ?>

    </form>
</div>
