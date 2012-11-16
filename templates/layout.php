<?= $this->includeFragment('header')
; ?>

<div id="container">

    <?=$this->includeFragment('top_menu');?>
    <?=$this->includeFragment('breadcrumb');?>
    <?=$this->includeFragment('status');?>
    <?=$this->includeFragment('messages');?>

    <div class="page_content">
        <?=$html?>
    </div>


</div>

<?= $this->includeFragment('footer')
; ?>


</body>
</html>
