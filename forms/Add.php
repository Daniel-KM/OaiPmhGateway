<?php

class OaiPmhGateway_Form_Add extends Omeka_Form
{
    public function init()
    {
        parent::init();

        $this->setAttrib('id', 'oai-pmh-gateway');
        $this->setMethod('post');

        $this->addElement('text', 'url', array(
            'label' => __('URL'),
            'description' => __('The url of the static repository file.'),
            'required' => true,
            'filters' => array(
                'StringTrim',
                'StripTags',
            ),
            'validators' => array(
                'NotEmpty',
                array(
                    'Callback',
                    true,
                    array(
                        'callback' => function($value) {
                            return Zend_Uri::check($value);
                        }
                    ),
                    'messages' => array(
                        Zend_Validate_Callback::INVALID_VALUE => __('An url or a path is required to add a folder.'),
                    ),
                ),
            ),
        ));

        /*
        $this->addElement('checkbox', 'public', array(
            'label' => __('Public'),
            'description' => __('If set, the gateway will be public.'),
            'value' => true,
        ));
         */

        /*
        $this->addElement('checkbox', 'friend', array(
            'label' => __('Friend'),
            'description' => __('If set, the gateway will be listed as a friend when the OAI-PMH verb "Identify" will be used.'),
            'value' => true,
        ));
         */

        $this->applyOmekaStyles();
        $this->setAutoApplyOmekaStyles(false);

        $this->addElement('sessionCsrfToken', 'csrf_token');

        $this->addElement('submit', 'submit', array(
            'label' => __('Add this url'),
            'class' => 'submit submit-medium',
            'decorators' => (array(
                'ViewHelper',
                array('HtmlTag', array('tag' => 'div', 'class' => 'field'))))
        ));
    }
}
