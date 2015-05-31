if (!Omeka) {
    var Omeka = {};
}

Omeka.OaiPmhGatewaysBrowse = {};

(function ($) {

    Omeka.OaiPmhGatewaysBrowse.setupBatchEdit = function () {
        var oaiPmhGatewayCheckboxes = $("table#oai-pmh-gateways tbody input[type=checkbox]");
        var globalCheckbox = $('th.batch-edit-heading').html('<input type="checkbox">').find('input');
        var batchEditSubmit = $('.batch-edit-option input');
        /**
         * Disable the batch submit button first, will be enabled once records
         * checkboxes are checked.
         */
        batchEditSubmit.prop('disabled', true);

        /**
         * Check all the oaiPmhGatewayCheckboxes if the globalCheckbox is checked.
         */
        globalCheckbox.change(function() {
            oaiPmhGatewayCheckboxes.prop('checked', !!this.checked);
            checkBatchEditSubmitButton();
        });

        /**
         * Uncheck the global checkbox if any of the oaiPmhGatewayCheckboxes are
         * unchecked.
         */
        oaiPmhGatewayCheckboxes.change(function(){
            if (!this.checked) {
                globalCheckbox.prop('checked', false);
            }
            checkBatchEditSubmitButton();
        });

        /**
         * Check whether the batchEditSubmit button should be enabled.
         * If any of the oaiPmhGatewayCheckboxes is checked, the batchEditSubmit button
         * is enabled.
         */
        function checkBatchEditSubmitButton() {
            var checked = false;
            oaiPmhGatewayCheckboxes.each(function() {
                if (this.checked) {
                    checked = true;
                    return false;
                }
            });

            batchEditSubmit.prop('disabled', !checked);
        }
    };

    // TODO To factorize.
    $(document).ready(function() {
        // Set public.
        $('.oai-pmh-gateway input[name="submit-batch-set-public"]').click(function(event) {
            event.preventDefault();
            $('table#oai-pmh-gateways thead tr th.batch-edit-heading input').attr('checked', false);
            $('.batch-edit-option input').prop('disabled', true);
            $('table#oai-pmh-gateways tbody input[type=checkbox]:checked').each(function(){
                var checkbox = $(this);
                var current = $('.public#oai-pmh-gateway-' + this.value);
                var ajaxUrl = current.attr('href') + '/oai-pmh-gateway/ajax/update';
                current.addClass('transmit');
                $.post(ajaxUrl,
                    {
                        id: this.value,
                        'public': 'true'
                    },
                    function(data) {
                        checkbox.attr('checked', false);
                        current.addClass('true');
                        current.removeClass('false');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.public);
                        }
                    }
                );
            });
        });

        // Set not public.
        $('.oai-pmh-gateway input[name="submit-batch-set-not-public"]').click(function(event) {
            event.preventDefault();
            $('table#oai-pmh-gateways thead tr th.batch-edit-heading input').attr('checked', false);
            $('.batch-edit-option input').prop('disabled', true);
            $('table#oai-pmh-gateways tbody input[type=checkbox]:checked').each(function(){
                var checkbox = $(this);
                var current = $('.public#oai-pmh-gateway-' + this.value);
                var ajaxUrl = current.attr('href') + '/oai-pmh-gateway/ajax/update';
                current.addClass('transmit');
                $.post(ajaxUrl,
                    {
                        id: this.value,
                        'public': 'false'
                    },
                    function(data) {
                        checkbox.attr('checked', false);
                        current.addClass('false');
                        current.removeClass('true');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.notPublic);
                        }
                    }
                );
            });
        });

        // Set Initiated from any status.
        $('.oai-pmh-gateway input[name="submit-batch-initiate"]').click(function(event) {
            event.preventDefault();
            $('table#oai-pmh-gateways thead tr th.batch-edit-heading input').attr('checked', false);
            $('.batch-edit-option input').prop('disabled', true);
            $('table#oai-pmh-gateways tbody input[type=checkbox]:checked').each(function(){
                var checkbox = $(this);
                var current = $('.status#oai-pmh-gateway-' + this.value);
                var ajaxUrl = current.attr('href') + '/oai-pmh-gateway/ajax/update';
                current.addClass('transmit');
                $.post(ajaxUrl,
                    {
                        id: this.value,
                        status: 'initiated'
                    },
                    function(data) {
                        checkbox.attr('checked', false);
                        current.addClass('initiated');
                        current.removeClass('terminated');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.initiated);
                        }
                    }
                );
            });
        });

        // Set Terminated from any status.
        $('.oai-pmh-gateway input[name="submit-batch-terminate"]').click(function(event) {
            event.preventDefault();
            $('table#oai-pmh-gateways thead tr th.batch-edit-heading input').attr('checked', false);
            $('.batch-edit-option input').prop('disabled', true);
            $('table#oai-pmh-gateways tbody input[type=checkbox]:checked').each(function(){
                var checkbox = $(this);
                var current = $('.status#oai-pmh-gateway-' + this.value);
                var ajaxUrl = current.attr('href') + '/oai-pmh-gateway/ajax/update';
                current.addClass('transmit');
                $.post(ajaxUrl,
                    {
                        id: this.value,
                        status: 'terminated'
                    },
                    function(data) {
                        checkbox.attr('checked', false);
                        current.addClass('terminated');
                        current.removeClass('initiated');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.terminated);
                        }
                    }
                );
            });
        });

        // Set friend.
        $('.oai-pmh-gateway input[name="submit-batch-set-friend"]').click(function(event) {
            event.preventDefault();
            $('table#oai-pmh-gateways thead tr th.batch-edit-heading input').attr('checked', false);
            $('.batch-edit-option input').prop('disabled', true);
            $('table#oai-pmh-gateways tbody input[type=checkbox]:checked').each(function(){
                var checkbox = $(this);
                var current = $('.friend#oai-pmh-gateway-' + this.value);
                var ajaxUrl = current.attr('href') + '/oai-pmh-gateway/ajax/update';
                current.addClass('transmit');
                $.post(ajaxUrl,
                    {
                        id: this.value,
                        friend: 'true'
                    },
                    function(data) {
                        checkbox.attr('checked', false);
                        current.addClass('true');
                        current.removeClass('false');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.friend);
                        }
                    }
                );
            });
        });

        // Set not friend.
        $('.oai-pmh-gateway input[name="submit-batch-set-not-friend"]').click(function(event) {
            event.preventDefault();
            $('table#oai-pmh-gateways thead tr th.batch-edit-heading input').attr('checked', false);
            $('.batch-edit-option input').prop('disabled', true);
            $('table#oai-pmh-gateways tbody input[type=checkbox]:checked').each(function(){
                var checkbox = $(this);
                var current = $('.friend#oai-pmh-gateway-' + this.value);
                var ajaxUrl = current.attr('href') + '/oai-pmh-gateway/ajax/update';
                current.addClass('transmit');
                $.post(ajaxUrl,
                    {
                        id: this.value,
                        friend: 'false'
                    },
                    function(data) {
                        checkbox.attr('checked', false);
                        current.addClass('false');
                        current.removeClass('true');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.notFriend);
                        }
                    }
                );
            });
        });

        // Delete a simple record.
        $('.oai-pmh-gateway input[name="submit-batch-delete"]').click(function(event) {
            event.preventDefault();
            if (!confirm(Omeka.messages.oaiPmhGateway.confirmation)) {
                return;
            }
            $('table#oai-pmh-gateways thead tr th.batch-edit-heading input').attr('checked', false);
            $('.batch-edit-option input').prop('disabled', true);
            $('table#oai-pmh-gateways tbody input[type=checkbox]:checked').each(function(){
                var checkbox = $(this);
                var row = $(this).closest('tr.oai-pmh-gateway');
                var current = $('#oai-pmh-gateway-' + this.value);
                var ajaxUrl = current.attr('href') + '/oai-pmh-gateway/ajax/delete';
                checkbox.addClass('transmit');
                $.post(ajaxUrl,
                    {
                        id: this.value
                    },
                    function(data) {
                        row.remove();
                    }
                );
            });
        });

        // Check a simple record.
        // TODO Factorize.
        $('.oai-pmh-gateway input[name="submit-batch-check"]').click(function(event) {
            event.preventDefault();
            $('table#oai-pmh-gateways thead tr th.batch-edit-heading input').attr('checked', false);
            $('.batch-edit-option input').prop('disabled', true);
            $('table#oai-pmh-gateways tbody input[type=checkbox]:checked').each(function(){
                var checkbox = $(this);
                var row = $(this).closest('tr.oai-pmh-gateway');
                var id = this.value;
                var current = $('#oai-pmh-gateway-' + id);
                var currentCheck = $('.oai-pmh-gateway-action a.check#oai-pmh-gateway-' + id);
                var ajaxUrl = current.attr('href') + '/oai-pmh-gateway/ajax/check';
                currentCheck.addClass('transmit');
                $.post(ajaxUrl,
                    {
                        id: id
                    },
                    function(data) {
                        checkbox.attr('checked', false);
                        if (data.result) {
                            currentCheck.addClass('green');
                            currentCheck.removeClass('red');
                        } else {
                            currentCheck.addClass('red');
                            currentCheck.removeClass('green');
                            var currentStatus = $('.oai-pmh-gateway-status a.status#oai-pmh-gateway-' + id);
                            currentStatus.addClass('terminated');
                            currentStatus.removeClass('initiated');
                            if (currentStatus.text() != '') {

                                currentStatus.text(Omeka.messages.oaiPmhGateway.terminated);
                            }
                        }
                        currentCheck.removeClass('transmit');
                        if (currentCheck.text() != '') {
                            if (data.result) {
                                currentCheck.text(Omeka.messages.oaiPmhGateway.checkGood);
                            } else {
                                currentCheck.text(Omeka.messages.oaiPmhGateway.checkError);
                            }
                        }
                    }
                );
            });
        });
    });

})(jQuery);
