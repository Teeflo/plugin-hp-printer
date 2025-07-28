$('.eqLogicAttr[data-l1key=configuration][data-l2key=protocol]').on('change', function () {
    $('.protocol').hide();
    $('.protocol.' + $(this).value()).show();
});

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = { configuration: {} }
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {}
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
    tr += '<td class="hidden-xs">'
    tr += '<span class="cmdAttr" data-l1key="id"></span>'
    tr += '</td>'
    tr += '<td>'
    tr += '<div class="input-group">'
    tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
    tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
    tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
    tr += '</div>'
    tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande information liée}}">'
    tr += '<option value="">{{Aucune}}</option>'
    tr += '</select>'
    tr += '</td>'
    tr += '<td>'
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
    tr += '</td>'
    tr += '<td style="width: 100%;">'
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
    tr += '<div style="margin-top:7px;">'
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '</div>'
    tr += '</td>'
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
    tr += '</td>';
    tr += '<td>'
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure" title="{{Configuration avancée}}"><i class="fas fa-cogs"></i></a> '
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
    tr += '</tr>'
    $('#table_cmd tbody').append(tr)
    var tr = $('#table_cmd tbody tr').last()
    jeedom.eqLogic.buildSelectCmd({
        id: $('.eqLogicAttr[data-l1key=id]').value(),
        filter: { type: 'info' },
        error: function (error) {
            $('#div_alert').showAlert({ message: error.message, level: 'danger' })
        },
        success: function (result) {
            tr.find('.cmdAttr[data-l1key=value]').append(result)
            tr.setValues(_cmd, '.cmdAttr')
            jeedom.cmd.changeType(tr, init(_cmd.subType))

            tr.find('.cmdAttr[data-l1key=type],.cmdAttr[data-l1key=subType]').prop("disabled", true);
        }
    })
}

$(function() {
    $('#bt_testConnection').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).find('i').addClass('fa-spin');

        var ipAddress = $('.eqLogicAttr[data-l1key=configuration][data-l2key=ipAddress]').val();
        var protocol = $('.eqLogicAttr[data-l1key=configuration][data-l2key=protocol]').val();
        var verifySsl = $('.eqLogicAttr[data-l1key=configuration][data-l2key=verifySsl]').is(':checked');

        if (!ipAddress) {
            $('#div_alert').showAlert({ message: '{{L\'adresse IP est requise}}', level: 'danger' });
            $btn.prop('disabled', false).find('i').removeClass('fa-spin');
            return;
        }

        // Test avec le fichier AJAX Jeedom simplifié
        $.ajax({
            type: 'POST',
            url: 'plugins/hp_printer/core/ajax/hp_printer.ajax.php',
            data: {
                action: 'test',
                ip_address: ipAddress,
                protocol: protocol,
                verifySsl: verifySsl
            },
            dataType: 'json',
            success: function (data) {
                if (data.state === 'ok') {
                    $('#div_alert').showAlert({ message: data.result, level: 'success' });
                } else if (data.state === 'warning') {
                    $('#div_alert').showAlert({ message: data.result, level: 'warning' });
                } else {
                    $('#div_alert').showAlert({ message: data.result, level: 'danger' });
                }
                
                // Afficher les infos de debug si disponibles
                if (data.debug && console && console.log) {
                    console.log('HP Printer Test Debug:', data.debug);
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                var errorMsg = 'Erreur lors du test de connexion: ' + errorThrown;
                
                // Essayer de parser la réponse pour plus de détails
                try {
                    var responseData = JSON.parse(xhr.responseText);
                    if (responseData && responseData.result) {
                        errorMsg = responseData.result;
                    }
                } catch (e) {
                    // Si on ne peut pas parser, garder le message par défaut
                }
                
                $('#div_alert').showAlert({ message: errorMsg, level: 'danger' });
                
                // Log de debug
                if (console && console.log) {
                    console.error('Test connection error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        textStatus: textStatus,
                        errorThrown: errorThrown
                    });
                }
            },
            complete: function () {
                $btn.prop('disabled', false).find('i').removeClass('fa-spin');
            }
        });
    });

    $('#bt_refreshData').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).find('i').addClass('fa-spin');

        var eqLogicId = $('.eqLogicAttr[data-l1key=id]').val();

        $.ajax({
            type: 'POST',
            url: 'plugins/hp_printer/core/ajax/hp_printer.ajax.php',
            data: {
                action: 'pullData',
                eqLogic_id: eqLogicId
            },
            dataType: 'json',
            success: function (data) {
                if (data.state === 'ok') {
                    $('#div_alert').showAlert({ message: data.message, level: 'success' });
                } else {
                    $('#div_alert').showAlert({ message: data.message, level: 'danger' });
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                $('#div_alert').showAlert({ message: '{{Erreur lors du rafraîchissement: ' + errorThrown + '}}', level: 'danger' });
            },
            complete: function () {
                $btn.prop('disabled', false).find('i').removeClass('fa-spin');
            }
        });
    });

    $('#bt_createCommands').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).find('i').addClass('fa-spin');

        var eqLogicId = $('.eqLogicAttr[data-l1key=id]').val();
        var ipAddress = $('.eqLogicAttr[data-l1key=configuration][data-l2key=ipAddress]').val();
        var protocol = $('.eqLogicAttr[data-l1key=configuration][data-l2key=protocol]').val();

        if (!ipAddress) {
            $('#div_alert').showAlert({ message: '{{L\'adresse IP est requise}}', level: 'danger' });
            $btn.prop('disabled', false).find('i').removeClass('fa-spin');
            return;
        }

        if (!eqLogicId) {
            $('#div_alert').showAlert({ message: '{{Vous devez d\'abord sauvegarder l\'équipement}}', level: 'danger' });
            $btn.prop('disabled', false).find('i').removeClass('fa-spin');
            return;
        }

        $.ajax({
            type: 'POST',
            url: 'plugins/hp_printer/core/ajax/hp_printer.ajax.php',
            data: {
                action: 'createCommands',
                eqLogic_id: eqLogicId,
                ip_address: ipAddress,
                protocol: protocol
            },
            dataType: 'json',
            success: function (data) {
                if (data.state === 'ok') {
                    $('#div_alert').showAlert({ message: data.result, level: 'success' });
                    
                    // Afficher les erreurs s'il y en a
                    if (data.errors && data.errors.length > 0) {
                        var errorMsg = '{{Quelques endpoints ont échoué:}} ' + data.errors.join(', ');
                        $('#div_alert').showAlert({ message: errorMsg, level: 'warning' });
                    }
                    
                    // Recharger la page pour afficher les nouvelles commandes
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    $('#div_alert').showAlert({ message: data.result, level: 'danger' });
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                var errorMsg = 'Erreur lors de la création des commandes: ' + errorThrown;
                
                try {
                    var responseData = JSON.parse(xhr.responseText);
                    if (responseData && responseData.result) {
                        errorMsg = responseData.result;
                    }
                } catch (e) {
                    // Si on ne peut pas parser, garder le message par défaut
                }
                
                $('#div_alert').showAlert({ message: errorMsg, level: 'danger' });
            },
            complete: function () {
                $btn.prop('disabled', false).find('i').removeClass('fa-spin');
            }
        });
    });
});