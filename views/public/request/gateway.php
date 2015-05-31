<?php
    $title = __('OAI-PMH Static Repository Gateway');
    echo head(array(
        'title' => html_escape($title),
    ));
?>
<div id="primary">
    <?php echo flash(); ?>
    <h2><?php echo $title; ?></h2>
    <p><?php
        if (!empty($url)):
            echo __('URL: %s', $url);
        endif;
    ?></p>
    <p><?php
        if (!empty($message)):
            if (empty($message_type)):
                echo $message;
            else:
                echo __(ucfirst($message_type)) . ': ' . $message;
            endif;
        endif
    ?></p>
    <?php if (!empty($gateway)): ?>
    <p><?php
        echo __('This static repository can be harvested at %s.', '<a href="' . $gateway->getBaseUrl() . '" target=”_blank”>' . $gateway->getBaseUrl() . '</a>');
    ?></p>
    <?php endif; ?>
    <p><?php
        echo __('See guidelines of the %sOAI-PMH Static Repository%s protocol.', '<a href="http://www.openarchives.org/OAI/2.0/guidelines-static-repository.htm" target=”_blank”>', '</a>');
    ?></p>
</div>
<?php
    echo foot();
