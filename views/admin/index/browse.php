<?php
$pageTitle = __('Gateway for Repositories (%d total)', $total_results);
queue_css_file('oai-pmh-gateway');
queue_js_file('oai-pmh-gateway');
queue_js_file('oai-pmh-gateway-browse');
echo head(array(
    'title' => $pageTitle,
    'bodyclass' => 'oai-pmh-gateway browse',
));
?>
<div id="primary">
    <?php echo flash(); ?>
    <h2><?php echo __('Add a Static Repository'); ?></h2>
    <?php if (is_allowed('OaiPmhGateway_Index', 'add')):
        echo $this->addForm;
    else: ?>
    <p><?php echo __("You don't have the right to add a gateway."); ?></p>
    <?php endif; ?>
    <h2><?php echo __('Status of Static Repositories'); ?></h2>
<?php if (iterator_count(loop('OaiPmhGateway'))): ?>
    <form action="<?php echo html_escape(url('oai-pmh-gateway/index/batch-edit')); ?>" method="post" accept-charset="utf-8">
        <div class="table-actions batch-edit-option">
            <?php if (is_allowed('OaiPmhGateway_Index', 'edit')): ?>
            <input type="submit" class="small green batch-action button" name="submit-batch-set-public" value="<?php echo __('Make Public'); ?>">
            <input type="submit" class="small green batch-action button" name="submit-batch-set-not-public" value="<?php echo __('Make Reserved'); ?>">
            <input type="submit" class="small green batch-action button" name="submit-batch-initiate" value="<?php echo __('Intiate'); ?>">
            <input type="submit" class="small green batch-action button" name="submit-batch-terminate" value="<?php echo __('Terminate'); ?>">
            <input type="submit" class="small green batch-action button" name="submit-batch-set-friend" value="<?php echo __('Make Friends'); ?>">
            <input type="submit" class="small green batch-action button" name="submit-batch-set-not-friend" value="<?php echo __('Unmake Friends'); ?>">
            <input type="submit" class="small blue batch-action button" name="submit-batch-check" value="<?php echo __('Check'); ?>">
            <?php endif; ?>
            <?php if (is_allowed('OaiPmhGateway_Index', 'delete')): ?>
            <input type="submit" class="small red batch-action button" name="submit-batch-delete" value="<?php echo __('Delete'); ?>">
            <?php endif; ?>
        </div>
        <?php echo common('quick-filters'); ?>
        <div class="pagination"><?php echo $paginationLinks = pagination_links(); ?></div>
        <table id="oai-pmh-gateways" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <?php if (is_allowed('OaiPmhGateway_Index', 'edit')): ?>
                    <th class="batch-edit-heading"><?php // echo __('Select'); ?></th>
                    <?php endif;
                    $browseHeadings[__('URL')] = 'url';
                    $browseHeadings[__('Public ')] = 'public';
                    $browseHeadings[__('Status')] = 'status';
                    $browseHeadings[__('Friend ')] = 'friend';
                    $browseHeadings[__('Added')] = 'added';
                    $browseHeadings[__('Action')] = null;
                    echo browse_sort_links($browseHeadings, array('link_tag' => 'th scope="col"', 'list_tag' => ''));
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php $key = 0; ?>
                <?php
                foreach (loop('OaiPmhGateway') as $gateway):
                    $harvests = $gateway->getHarvests();
                ?>
                <tr class="oai-pmh-gateway <?php if (++$key%2 == 1) echo 'odd'; else echo 'even'; ?>">
                    <?php if (is_allowed('OaiPmhGateway_Index', 'edit')): ?>
                    <td class="batch-edit-check" scope="row">
                        <input type="checkbox" name="gateways[]" value="<?php echo $gateway->id; ?>" />
                    </td>
                    <?php endif; ?>
                    <td><a href="<?php echo html_escape($gateway->getBaseUrl() . '?verb=Identify'); ?>" target="_blank"><?php echo html_escape($gateway->url); ?></a></td>
                    <td><?php echo common('gateway-public', array('gateway' => $gateway)); ?></td>
                    <td><?php
                        echo common('gateway-status', array('gateway' => $gateway));
                        if (plugin_is_active('OaipmhHarvester')): ?>
                        <p>
                            <em><?php echo __('Harvester:'); ?></em>
                            <div>
                            <?php
                            if ($harvests):
                                foreach ($harvests as $harvest):
                                    echo common('harvest-status', array('harvest' => $harvest, 'asText' => true)); ?>
                                    <div class="harvest-full-status">
                                        <?php echo '<div class="harvest-prefix">[' . $harvest->metadata_prefix . ']</div>'; ?>
                                        <a href="<?php echo url('oaipmh-harvester/index/status', array('harvest_id' => $harvest->id)); ?>"><?php echo __('Full status'); ?></a>
                                    </div>
                                <?php endforeach;
                            else: ?>
                                <div>
                                    <?php echo __('None'); ?>
                                </div>
                            <?php endif; ?>
                            </div>
                        </p>
                        <?php endif; ?>
                    </td>
                    <td><?php echo common('gateway-friend', array('gateway' => $gateway)); ?></td>
                    <td><?php echo html_escape(format_date($gateway->added, Zend_Date::DATETIME_SHORT)); ?></td>
                    <td class="oai-pmh-gateway-action">
                        <a href="<?php echo ADMIN_BASE_URL; ?>" id="oai-pmh-gateway-<?php echo $gateway->id; ?>" class="oai-pmh-gateway check small green button"><?php echo __('Check'); ?></a>
                        <?php foreach ($harvests as $harvest): ?>
                        <a href="<?php echo url('oai-pmh-gateway/index/harvest', array('id' => $gateway->id, 'prefix' => $harvest->metadata_prefix)); ?>" class="small blue button"><?php echo __('Harvest [%s]', $harvest->metadata_prefix); ?></a>
                        <?php endforeach; ?>
                        <?php /* TODO To finish. /* if (is_allowed('OaiPmhGateway_Index', 'delete')): ?>
                        <a href="<?php echo ADMIN_BASE_URL; ?>" id="oai-pmh-gateway-<?php echo $gateway->id; ?>" class="oai-pmh-gateway delete small red button"><?php echo __('Delete'); ?></a>
                        <?php endif; */ ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="text-align: right;"><em><?php echo __('Click public, status or friend to switch it.'); ?></em></p>
        <div class="pagination"><?php echo $paginationLinks; ?></div>
    </form>
    <script type="text/javascript">
        Omeka.messages = jQuery.extend(Omeka.messages,
            {'oaiPmhGateway':{
                'public':<?php echo json_encode(__('Yes')); ?>,
                'reserved':<?php echo json_encode(__('No')); ?>,
                'initiated':<?php echo json_encode(__('Initiated')); ?>,
                'terminated':<?php echo json_encode(__('Terminated')); ?>,
                'friend':<?php echo json_encode(__('Yes')); ?>,
                'notFriend':<?php echo json_encode(__('No')); ?>,
                'undefined':<?php echo json_encode(__('Undefined')); ?>,
                'checkGood':<?php echo json_encode(__('Checked good')); ?>,
                'checkError':<?php echo json_encode(__('Checked error')); ?>,
                'confirmation':<?php echo json_encode(__('Are your sure to remove these gateways?')); ?>
            }}
        );
        Omeka.addReadyCallback(Omeka.OaiPmhGatewaysBrowse.setupBatchEdit);
    </script>
<?php else: ?>
    <?php if (total_records('OaiPmhGateway') == 0): ?>
        <p><?php echo __('No url have been added.'); ?></p>
    <?php else: ?>
        <p><?php echo __('The query searched %s records and returned no results.', total_records('OaiPmhGateway')); ?></p>
        <p><a href="<?php echo url('oai-pmh-gateway'); ?>"><?php echo __('See all static repositories.'); ?></a></p>
    <?php endif; ?>
<?php endif; ?>
</div>
<?php
    echo foot();
