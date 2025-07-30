// Gère le changement de protocole (HTTP/HTTPS) dans la configuration de l'équipement.
// Affiche ou masque les éléments HTML en fonction du protocole sélectionné.
$('.eqLogicAttr[data-l1key=configuration][data-l2key=protocol]').on('change', function () {
    $('.protocol').hide();
    $('.protocol.' + $(this).value()).show();
});

// Fonction pour ajouter une ligne de commande à la table des commandes de l'équipement.
// Cette fonction est utilisée dynamiquement pour construire l'interface utilisateur.
function addCmdToTable(_cmd) {
    // Initialise l'objet de commande si non défini ou vide.
    if (!isset(_cmd)) {
        var _cmd = { configuration: {} }
    }
    // Assure que la propriété `configuration` existe.
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {}
    }
    // Construit la structure HTML d'une ligne de tableau pour une commande.
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
    tr += '<td class="hidden-xs">'
    // Affiche l'ID de la commande.
    tr += '<span class="cmdAttr" data-l1key="id"></span>'
    tr += '</td>'
    tr += '<td>'
    tr += '<div class="input-group">'
    // Champ pour le nom de la commande.
    tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
    // Bouton pour choisir une icône pour la commande.
    tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
    // Affiche l'icône sélectionnée.
    tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
    tr += '</div>'
    // Sélecteur pour lier une commande d'information (si applicable).
    tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande information liée}}">'
    tr += '<option value="">{{Aucune}}</option>'
    tr += '</select>'
    tr += '</td>'
    tr += '<td>'
    // Affiche le type de la commande (info/action).
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
    // Affiche le sous-type de la commande.
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
    tr += '</td>'
    tr += '<td style="width: 100%;">'
    // Case à cocher pour la visibilité de la commande.
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
    // Case à cocher pour l'historisation de la commande.
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
    // Case à cocher pour inverser la valeur binaire (pour les commandes binaires).
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
    tr += '<div style="margin-top:7px;">'
    // Champs pour les valeurs minimale et maximale (pour les commandes numériques).
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    // Champ pour l'unité de la commande.
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '</div>'
    tr += '</td>'
    tr += '<td>';
    // Affiche l'état HTML de la commande.
    tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
    tr += '</td>';
    tr += '<td>'
    // Boutons d'action (configuration avancée, test) si la commande a un ID numérique.
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure" title="{{Configuration avancée}}"><i class="fas fa-cogs"></i></a> '
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
    }
    // Bouton pour supprimer la commande.
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
    tr += '</tr>'
    // Ajoute la ligne construite au corps de la table des commandes.
    $('#table_cmd tbody').append(tr)
    // Récupère la dernière ligne ajoutée pour la manipuler.
    var tr = $('#table_cmd tbody tr').last()
    // Construit le sélecteur de commandes liées pour la commande actuelle.
    jeedom.eqLogic.buildSelectCmd({
        id: $('.eqLogicAttr[data-l1key=id]').value(),
        filter: { type: 'info' },
        // Gère les erreurs lors de la construction du sélecteur.
        error: function (error) {
            $('#div_alert').showAlert({ message: error.message, level: 'danger' })
        },
        // Callback en cas de succès.
        success: function (result) {
            // Ajoute les options de commandes liées au sélecteur.
            tr.find('.cmdAttr[data-l1key=value]').append(result)
            // Définit les valeurs des attributs de la commande.
            tr.setValues(_cmd, '.cmdAttr')
            // Change le type de la commande si nécessaire.
            jeedom.cmd.changeType(tr, init(_cmd.subType))

            // Désactive les champs de type et sous-type pour éviter les modifications directes.
            tr.find('.cmdAttr[data-l1key=type],.cmdAttr[data-l1key=subType]').prop("disabled", true);
        }
    })
}

// Variable globale pour empêcher les exécutions multiples - avec namespace unique
// Ceci est une mesure de protection pour éviter que le code ne s'exécute plusieurs fois
// si le script est inclus ou appelé de manière inattendue.
window.HP_PRINTER_GLOBALS = window.HP_PRINTER_GLOBALS || {
    initialized: false
};

// Exécute le code une fois que le DOM est entièrement chargé.
$(function() {
    // Empêcher l'initialisation multiple
    if (window.HP_PRINTER_GLOBALS.initialized) {
        console.log('HP Printer déjà initialisé, ignoré');
        return;
    }
    
    // Marque le script comme initialisé.
    window.HP_PRINTER_GLOBALS.initialized = true;

    // Gestionnaire d'événements pour le bouton de test de connexion.
    $('#bt_testConnection').off('click').on('click', function (e) {
        // Empêche le comportement par défaut du bouton et la propagation de l'événement.
        e.preventDefault();
        e.stopPropagation();

        // Référence au bouton cliqué.
        var $btn = $(this);
        // Vérifie si le bouton est déjà désactivé ou en cours de traitement.
        if ($btn.prop('disabled') || $btn.hasClass('processing')) {
            return false;
        }

        // Désactive le bouton et ajoute une classe pour indiquer le traitement (avec une icône de chargement).
        $btn.prop('disabled', true).addClass('processing').find('i').addClass('fa-spin');

        // Récupère l'adresse IP et le protocole depuis les champs de configuration.
        var ipAddress = $('.eqLogicAttr[data-l1key=configuration][data-l2key=ipAddress]').val();
        var protocol = $('.eqLogicAttr[data-l1key=configuration][data-l2key=protocol]').val();

        // Vérifie si l'adresse IP est fournie.
        if (!ipAddress) {
            // Affiche une alerte si l'adresse IP est manquante.
            $('#div_alert').showAlert({ message: '{{L\'adresse IP est requise}}', level: 'danger' });
            // Réactive le bouton et retire l'icône de chargement.
            $btn.prop('disabled', false).removeClass('processing').find('i').removeClass('fa-spin');
            return false;
        }

        // Effectue une requête AJAX pour tester la connexion à l'imprimante.
        $.ajax({
            type: 'POST',
            url: 'plugins/hp_printer/core/ajax/hp_printer.ajax.php',
            data: {
                action: 'testConnection',
                ipAddress: ipAddress,
                protocol: protocol
            },
            dataType: 'json',
            cache: false,
            // Callback en cas de succès de la requête AJAX.
            success: function (data) {
                // Affiche un message de succès ou d'erreur en fonction de la réponse du serveur.
                if (data.state === 'ok') {
                    $('#div_alert').showAlert({ message: data.result, level: 'success' });
                } else {
                    $('#div_alert').showAlert({ message: data.result, level: 'danger' });
                }
            },
            // Callback en cas d'erreur de la requête AJAX.
            error: function (xhr, textStatus, errorThrown) {
                // Construit un message d'erreur détaillé.
                var errorMsg = '{{Erreur lors du test de connexion: }}' + errorThrown;
                try {
                    // Tente de parser la réponse JSON pour un message d'erreur plus spécifique.
                    var responseData = JSON.parse(xhr.responseText);
                    if (responseData && responseData.result) {
                        errorMsg = responseData.result;
                    }
                } catch (e) {
                    // Ignore l'erreur de parsing si la réponse n'est pas un JSON valide.
                }
                // Affiche l'alerte d'erreur.
                $('#div_alert').showAlert({ message: errorMsg, level: 'danger' });
            },
            // Callback exécuté à la fin de la requête (succès ou erreur).
            complete: function () {
                // Réactive le bouton et retire l'icône de chargement.
                $btn.prop('disabled', false).removeClass('processing').find('i').removeClass('fa-spin');
            }
        });

        return false;
    });

    // Gestionnaire d'événements pour le bouton de rafraîchissement des données.
    $('#bt_refreshData').off('click').on('click', function (e) {
        // Empêche le comportement par défaut du bouton et la propagation de l'événement.
        e.preventDefault();
        e.stopPropagation();
        
        // Référence au bouton cliqué.
        var $btn = $(this);
        
        // Vérifie si le bouton est déjà désactivé ou en cours de traitement.
        if ($btn.prop('disabled') || $btn.hasClass('processing')) {
            return false;
        }
        
        // Désactive le bouton et ajoute une classe pour indiquer le traitement.
        $btn.prop('disabled', true).addClass('processing').find('i').addClass('fa-spin');

        // Récupère l'ID de l'équipement logique.
        var eqLogicId = $('.eqLogicAttr[data-l1key=id]').val();

        // Effectue une requête AJAX pour rafraîchir les données de l'imprimante.
        $.ajax({
            type: 'POST',
            url: 'plugins/hp_printer/core/ajax/hp_printer.ajax.php',
            data: {
                action: 'pullData',
                eqLogic_id: eqLogicId
            },
            dataType: 'json',
            cache: false,
            // Callback en cas de succès.
            success: function (data) {
                // Affiche un message de succès ou d'erreur.
                if (data.state === 'ok') {
                    $('#div_alert').showAlert({ message: data.message, level: 'success' });
                } else {
                    $('#div_alert').showAlert({ message: data.message, level: 'danger' });
                }
            },
            // Callback en cas d'erreur.
            error: function (xhr, textStatus, errorThrown) {
                // Affiche un message d'erreur générique.
                $('#div_alert').showAlert({ message: '{{Erreur lors du rafraîchissement: ' + errorThrown + '}}', level: 'danger' });
            },
            // Callback exécuté à la fin de la requête.
            complete: function () {
                // Réactive le bouton et retire l'icône de chargement.
                $btn.prop('disabled', false).removeClass('processing').find('i').removeClass('fa-spin');
            }
        });
        
        return false;
    });

    // Gestionnaire d'événements pour le bouton de création des commandes.
    $('#bt_createCommands').off('click').on('click', function (e) {
        // Empêche le comportement par défaut du bouton et la propagation de l'événement.
        e.preventDefault();
        e.stopPropagation();
        
        // Référence au bouton cliqué.
        var $btn = $(this);
        
        // Vérifie si le bouton est déjà désactivé ou en cours de traitement.
        if ($btn.prop('disabled') || $btn.hasClass('processing')) {
            return false;
        }
        
        // Désactive le bouton et ajoute une classe pour indiquer le traitement.
        $btn.prop('disabled', true).addClass('processing').find('i').addClass('fa-spin');

        // Récupère l'ID de l'équipement, l'adresse IP et le protocole.
        var eqLogicId = $('.eqLogicAttr[data-l1key=id]').val();
        var ipAddress = $('.eqLogicAttr[data-l1key=configuration][data-l2key=ipAddress]').val();
        var protocol = $('.eqLogicAttr[data-l1key=configuration][data-l2key=protocol]').val();

        // Vérifie si l'adresse IP est fournie.
        if (!ipAddress) {
            // Affiche une alerte si l'adresse IP est manquante.
            $('#div_alert').showAlert({ message: '{{L\'adresse IP est requise}}', level: 'danger' });
            // Réactive le bouton.
            $btn.prop('disabled', false).removeClass('processing').find('i').removeClass('fa-spin');
            return false;
        }

        // Vérifie si l'équipement a été sauvegardé (s'il a un ID).
        if (!eqLogicId) {
            // Affiche une alerte si l'équipement n'est pas sauvegardé.
            $('#div_alert').showAlert({ message: '{{Vous devez d\'abord sauvegarder l\'équipement}}', level: 'danger' });
            // Réactive le bouton.
            $btn.prop('disabled', false).removeClass('processing').find('i').removeClass('fa-spin');
            return false;
        }

        // Effectue une requête AJAX pour créer les commandes.
        $.ajax({
            type: 'POST',
            url: 'plugins/hp_printer/core/ajax/hp_printer.ajax.php',
            data: {
                action: 'createCommands',
                eqLogic_id: eqLogicId
            },
            dataType: 'json',
            cache: false,
            // Callback en cas de succès.
            success: function (data) {
                // Affiche un message de succès.
                if (data.state === 'ok') {
                    $('#div_alert').showAlert({ message: data.message, level: 'success' });
                    
                    // Afficher les erreurs s'il y en a
                    if (data.errors && data.errors.length > 0) {
                        var errorMsg = '{{Quelques endpoints ont échoué:}} ' + data.errors.join(', ');
                        $('#div_alert').showAlert({ message: errorMsg, level: 'warning' });
                    }
                    
                    // Recharger la page pour afficher les nouvelles commandes après un court délai.
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Affiche un message d'erreur.
                    $('#div_alert').showAlert({ message: data.message, level: 'danger' });
                }
            },
            // Callback en cas d'erreur.
            error: function (xhr, textStatus, errorThrown) {
                // Construit un message d'erreur.
                var errorMsg = 'Erreur lors de la création des commandes: ' + errorThrown;
                
                try {
                    // Tente de parser la réponse JSON pour un message plus précis.
                    var responseData = JSON.parse(xhr.responseText);
                    if (responseData && responseData.message) {
                        errorMsg = responseData.message;
                    }
                } catch (e) {
                    // Si on ne peut pas parser, garder le message par défaut
                }
                
                // Affiche l'alerte d'erreur.
                $('#div_alert').showAlert({ message: errorMsg, level: 'danger' });
            },
            // Callback exécuté à la fin de la requête.
            complete: function () {
                // Réactive le bouton et retire l'icône de chargement.
                $btn.prop('disabled', false).removeClass('processing').find('i').removeClass('fa-spin');
            }
        });
        
        return false;
    });
});