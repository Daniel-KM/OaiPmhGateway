<?php if ($gateway):
$textPublic = !empty($textPublic);
$yes = $textPublic ? __('Public') : __('Yes');
$no = $textPublic ? __('Not public') : __('No');
?>
<div class="oai-pmh-gateway-public">
    <?php if (empty($asText)): ?>
    <a href="<?php echo ADMIN_BASE_URL; ?>" id="oai-pmh-gateway-<?php echo $gateway->id; ?>" class="oai-pmh-gateway toggle-public public <?php echo $gateway->isPublic() ? 'true' : 'false'; ?>"><?php
        echo $gateway->isPublic() ? $yes : $no;
    ?></a>
    <?php else: ?>
        <?php echo __('Public Gateway: %s', $gateway->isPublic() ? $yes : $no); ?>
    <?php endif; ?>
</div>
<?php endif; ?>
