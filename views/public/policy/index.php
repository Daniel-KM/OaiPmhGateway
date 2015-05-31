<?php
$title = __('OAI-PMH Gateway - Policy Statement');
echo head(array(
    'title' => $title,
));
?>
<div id="primary">
    <?php echo flash(); ?>
    <h1><?php echo __('OAI-PMH Gateway'); ?></h1>
    <h2><?php echo __('Policy Statement'); ?></h2>
    <?php echo $policy; ?>
</div>
<?php echo foot(); ?>
