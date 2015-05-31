(function ($) {

    $(document).ready(function() {
        // Handle public.
        $('.oai-pmh-gateway.toggle-public').click(function(event) {
            event.preventDefault();
            var id = $(this).attr('id');
            var current = $('.public#' + id);
            id = id.substr(id.lastIndexOf('-') + 1);
            var ajaxUrl = $(this).attr('href') + '/oai-pmh-gateway/ajax/update';
            $(this).addClass('transmit');
            if ($(this).hasClass('true')) {
                $.post(ajaxUrl,
                    {
                        id: id,
                        'public': 'false'
                    },
                    function(data) {
                        current.addClass('false');
                        current.removeClass('true');
                        current.removeClass('undefined');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.notPublic);
                        }
                    }
                );
            } else {
                $.post(ajaxUrl,
                    {
                        id: id,
                        'public': 'true'
                    },
                    function(data) {
                        current.addClass('true');
                        current.removeClass('false');
                        current.removeClass('undefined');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.public);
                        }
                    }
                );
            }
        });

        // Handle status.
        $('.oai-pmh-gateway.toggle-status').click(function(event) {
            event.preventDefault();
            var id = $(this).attr('id');
            var current = $('.status#' + id);
            id = id.substr(id.lastIndexOf('-') + 1);
            var ajaxUrl = $(this).attr('href') + '/oai-pmh-gateway/ajax/update';
            $(this).addClass('transmit');
            if ($(this).hasClass('initiated')) {
                $.post(ajaxUrl,
                    {
                        id: id,
                        status: 'terminated'
                    },
                    function(data) {
                        current.addClass('terminated');
                        current.removeClass('initiated');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.terminated);
                        }
                    }
                );
            } else {
                $.post(ajaxUrl,
                    {
                        id: id,
                        status: 'initiated'
                    },
                    function(data) {
                        current.addClass('initiated');
                        current.removeClass('terminated');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.initiated);
                        }
                    }
                );
            }
        });

        // Handle friend.
        $('.oai-pmh-gateway.toggle-friend').click(function(event) {
            event.preventDefault();
            var id = $(this).attr('id');
            var current = $('.friend#' + id);
            id = id.substr(id.lastIndexOf('-') + 1);
            var ajaxUrl = $(this).attr('href') + '/oai-pmh-gateway/ajax/update';
            $(this).addClass('transmit');
            if ($(this).hasClass('true')) {
                $.post(ajaxUrl,
                    {
                        id: id,
                        friend: 'false'
                    },
                    function(data) {
                        current.addClass('false');
                        current.removeClass('true');
                        current.removeClass('undefined');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.notFriend);
                        }
                    }
                );
            } else {
                $.post(ajaxUrl,
                    {
                        id: id,
                        friend: 'true'
                    },
                    function(data) {
                        current.addClass('true');
                        current.removeClass('false');
                        current.removeClass('undefined');
                        current.removeClass('transmit');
                        if (current.text() != '') {
                            current.text(Omeka.messages.oaiPmhGateway.friend);
                        }
                    }
                );
            }
        });

        // Handle delete.
        // TODO Row is not set right; add a confirmation.
        $('.oai-pmh-gateway.delete').click(function(event) {
            event.preventDefault();
            var id = $(this).attr('id');
            var current = $('.delete#' + id);
            id = id.substr(id.lastIndexOf('-') + 1);
            var ajaxUrl = $(this).attr('href') + '/oai-pmh-gateway/ajax/delete';
            $(this).addClass('transmit');
            $.post(ajaxUrl,
                {
                    id: id
                },
                function(data) {
                    var row = $(this).closest('tr.oai-pmh-gateway');
                    row.remove();
                }
            );
        });

        // Handle check.
        $('.oai-pmh-gateway.check').click(function(event) {
            event.preventDefault();
            var id = $(this).attr('id');
            var current = $('.check#' + id);
            id = id.substr(id.lastIndexOf('-') + 1);
            var ajaxUrl = $(this).attr('href') + '/oai-pmh-gateway/ajax/check';
            current.addClass('transmit');
            $.post(ajaxUrl,
                {
                    id: id
                },
                function(data) {
                    if (data.result) {
                        current.addClass('green');
                        current.removeClass('red');
                    } else {
                        current.addClass('red');
                        current.removeClass('green');
                        var currentStatus = $('.oai-pmh-gateway-status a.status#oai-pmh-gateway-' + id);
                        currentStatus.addClass('terminated');
                        currentStatus.removeClass('initiated');
                        if (currentStatus.text() != '') {
                            currentStatus.text(Omeka.messages.oaiPmhGateway.terminated);
                        }
                    }
                    current.removeClass('transmit');
                    if (current.text() != '') {
                        if (data.result) {
                            current.text(Omeka.messages.oaiPmhGateway.checkGood);
                        } else {
                            current.text(Omeka.messages.oaiPmhGateway.checkError);
                        }
                    }
                }
            );
        });
    });

})(jQuery);
