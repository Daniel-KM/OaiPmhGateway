<?php
/**
 * Controller for Achive Gateway policy page.
 *
 * @package OaiPmhGateway
 */
class OaiPmhGateway_PolicyController extends Omeka_Controller_AbstractActionController
{
    /**
     * Display the policy of the gateway.
     */
    public function indexAction()
    {
        $policy = get_option('oaipmh_gateway_notes');
        if (empty($policy)) {
            throw new Omeka_Controller_Exception_404;
        }

        $notesUrl = get_option('oaipmh_gateway_notes_url');
        if (!empty($notesUrl) && $notesUrl != public_url('/oai-pmh-gateway/policy')) {
            throw new Omeka_Controller_Exception_404;
        }

        $this->view->policy = $policy;
    }
}
