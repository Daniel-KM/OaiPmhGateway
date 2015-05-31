<ul class="quick-filter-wrapper">
    <li><a href="#" tabindex="0"><?php echo __('Quick Filter'); ?></a>
    <ul class="dropdown">
        <li><span class="quick-filter-heading"><?php echo __('Quick Filter') ?></span></li>
        <li><a href="<?php echo url('oai-pmh-gateway'); ?>"><?php echo __('View All') ?></a></li>
        <li><a href="<?php echo url('oai-pmh-gateway', array('public' => 1)); ?>"><?php echo __('Is Public'); ?></a></li>
        <li><a href="<?php echo url('oai-pmh-gateway', array('status' => 0)); ?>"><?php echo __('Is Reserved'); ?></a></li>
        <li><a href="<?php echo url('oai-pmh-gateway', array('status' => 'initiated')); ?>"><?php echo __('Status Initiated'); ?></a></li>
        <li><a href="<?php echo url('oai-pmh-gateway', array('status' => 'terminated')); ?>"><?php echo __('Status Terminated'); ?></a></li>
        <li><a href="<?php echo url('oai-pmh-gateway', array('friend' => 1)); ?>"><?php echo __('Is Friend'); ?></a></li>
        <li><a href="<?php echo url('oai-pmh-gateway', array('friend' => 0)); ?>"><?php echo __('Is Not Friend'); ?></a></li>
    </ul>
    </li>
</ul>
