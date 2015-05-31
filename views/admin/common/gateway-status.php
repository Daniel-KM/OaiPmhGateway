<?php
if ($gateway):
    switch ($gateway->status):
        case OaiPmhGateway::STATUS_INITIATED: $status = __('Initiated'); break;
        case OaiPmhGateway::STATUS_TERMINATED: $status = __('Terminated'); break;
        default: $status = __('Undefined');
    endswitch;
?>
<div class="oai-pmh-gateway-status">
    <?php if (empty($asText)): ?>
        <a href="<?php echo ADMIN_BASE_URL; ?>" id="oai-pmh-gateway-<?php echo $gateway->id; ?>" class="oai-pmh-gateway toggle-status status <?php echo $gateway->status; ?>"><?php
            echo $status;
        ?></a>
    <?php else: ?>
        <?php echo __('Gateway status: %s', $status); ?>
    <?php endif; ?>
</div>
<?php endif; ?>
