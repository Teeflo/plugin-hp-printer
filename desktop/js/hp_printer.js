$('#bt_refresh_data').on('click', function() {
    var eqLogicId = $('.eqLogicAttr[data-l1key=id]').val();
    if (eqLogicId) {
        // Display a loading indicator or disable the button
        $(this).button('loading');
        var that = this;

        $.ajax({
            type: 'POST',
            url: createUrl({
                action: 'pullData',
                plugin: 'hp_printer',
                type: 'ajax'
            }),
            data: {
                action: 'pullData',
                eqLogic_id: eqLogicId
            },
            dataType: 'json',
            success: function(data) {
                if (data.state == 'ok') {
                    // Reload the page to display updated data
                    window.location.reload();
                } else {
                    // Display error message
                    $('#div_alert').showAlert({message: data.message, level: 'danger'});
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('#div_alert').showAlert({message: "Error during data refresh: " + textStatus + " - " + errorThrown, level: 'danger'});
            },
            complete: function() {
                // Re-enable the button
                $(that).button('reset');
            }
        });
    } else {
        $('#div_alert').showAlert({message: 'Please save the equipment first.', level: 'warning'});
    }
});