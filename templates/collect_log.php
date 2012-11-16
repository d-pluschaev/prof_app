<hr/>
<?

echo implode('<br/>', $this->errors);

?>

<br/>
<br/>

<a href="?<?=App::getForwardSafeUri(null, 'default')?>">Go back</a>
<a style="margin-left: 50px;" href="?<?=App::getForwardSafeUri('analyze', 'details')
?>&namespace=<?=$this->namespace_target?>">Go to results page</a>


