<?php if ($gateway):
$textFriend = !empty($textFriend);
$yes = $textFriend ? __('Friend') : __('Yes');
$no = $textFriend ? __('Not friend') : __('No');
?>
<div class="oai-pmh-gateway-friend">
    <?php if (empty($asText)): ?>
    <a href="<?php echo ADMIN_BASE_URL; ?>" id="oai-pmh-gateway-<?php echo $gateway->id; ?>" class="oai-pmh-gateway toggle-friend friend <?php echo $gateway->isFriend() ? 'true' : 'false'; ?>"><?php
        echo $gateway->isFriend() ? $yes : $no;
    ?></a>
    <?php else: ?>
        <?php echo __('Gateway friend: %s', $gateway->isFriend() ? $yes : $no); ?>
    <?php endif; ?>
</div>
<?php endif; ?>
