<?php $isOmekaBefore26 = version_compare(OMEKA_VERSION, '2.6', '<'); ?>
<?php if ($isOmekaBefore26): ?>

<?php echo js_tag('vendor/tiny_mce/tiny_mce'); ?>
<script type="text/javascript">
jQuery(window).load(function () {
    Omeka.wysiwyg({
        mode: 'specific_textareas',
        editor_selector: 'html-editor'
    });
});
</script>

<?php else: ?>

<?php echo js_tag('vendor/tinymce/tinymce.min'); ?>
<script type="text/javascript">
jQuery(document).ready(function () {
    Omeka.wysiwyg({
        selector: '.html-editor'
    });
});
</script>

<?php endif; ?>

<fieldset id="fieldset-oai-pmh-gateway-identify"><legend><?php echo __('Identify'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('oaipmh_gateway_identify_friends',
                __('Friends')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('List confederated repositories of the gateway, for the verb "Identifier".'); ?>
                <?php echo __('Each repository may be hidden individually.'); ?>
                <?php echo __('See %s.', '<a href="http://www.openarchives.org/OAI/2.0/guidelines-friends.htm">http://www.openarchives.org/OAI/2.0/guidelines-friends.htm</a>'); ?>
            </p>
            <?php echo $this->formCheckbox('oaipmh_gateway_identify_friends', true,
                array('checked' => (boolean) get_option('oaipmh_gateway_identify_friends'))); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('oaipmh_gateway_notes_url',
                __('Url for policy')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('This url could be used to described usage policies, software versions, and related information.'); ?>
                <?php echo __('It will be added to the description of the gateway for the verb "Identifier".'); ?>
                <?php echo __('It can be any url, or the default one "%s" with the text below, if not empty.',
                        '<a href="' . html_escape(public_url('oai-pmh-gateway/policy')) . '">' . html_escape(public_url('oai-pmh-gateway/policy')) . '</a>'); ?>
            </p>
            <?php echo $this->formText('oaipmh_gateway_notes_url', get_option('oaipmh_gateway_notes_url'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('oaipmh_gateway_notes',
                __('Policy notes')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('This policy about the gateway will be published only if no url is set above.'); ?>
            </p>
            <?php echo $this->formTextarea('oaipmh_gateway_notes', get_option('oaipmh_gateway_notes'), array('rows' => '10', 'class' => array('textinput', 'html-editor'))); ?>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-oai-pmh-gateway"><legend><?php echo __('Gateway'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('oaipmh_gateway_check_xml',
                __('Check Xml')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('Check conformity of the xml of the static repository for each request'); ?>
                <?php echo __('If unchecked, a simple check of the validity is done.'); ?>
            </p>
            <?php echo $this->formCheckbox('oaipmh_gateway_check_xml', true,
                array('checked' => (boolean) get_option('oaipmh_gateway_check_xml'))); ?>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-oai-pmh-gateway-rights"><legend><?php echo __('Rights and Roles'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('oaipmh_gateway_allow_roles', __('Roles that can use OAI-PMH Gateway')); ?>
        </div>
        <div class="inputs five columns omega">
            <div class="input-block">
                <?php
                    $currentRoles = unserialize(get_option('oaipmh_gateway_allow_roles')) ?: array();
                    $userRoles = get_user_roles();
                    echo '<ul>';
                    foreach ($userRoles as $role => $label) {
                        echo '<li>';
                        echo $this->formCheckbox('oaipmh_gateway_allow_roles[]', $role,
                            array('checked' => in_array($role, $currentRoles) ? 'checked' : ''));
                        echo $label;
                        echo '</li>';
                    }
                    echo '</ul>';
                ?>
            </div>
        </div>
    </div>
</fieldset>
