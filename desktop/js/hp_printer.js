/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
})

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = {configuration: {}}
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {}
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">' 
  tr += '<td class="hidden-xs">'
  tr += '<input type="hidden" class="cmdAttr" data-l1key="id">' + init(_cmd.id)
  tr += '</td>'
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
  tr += '<option value="">{{Aucune}}</option>'
  tr += '</select>'
  tr += '</td>'
  tr += '<td>'
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
  tr += '<div style="margin-top:7px;'>'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '</div>'
  tr += '</td>'
  
  tr += '</div>'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '</div>'
  tr += '</td>'
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'; 
  tr += '</td>';
  tr += '<td>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>'
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
  tr += '</tr>'
  $('#table_cmd tbody').append(tr)
  var tr = $('#table_cmd tbody tr').last()
  jeedom.eqLogic.buildSelectCmd({
    id:  $('.eqLogicAttr[data-l1key=id]').val(),
    filter: {type: 'info'},
    error: function (error) {
      $('#div_alert').showAlert({message: error.message, level: 'danger'})
    },
    success: function (result) {
      tr.find('.cmdAttr[data-l1key=value]').append(result)
      tr.setValues(_cmd, '.cmdAttr')
      tr.find('input[data-l1key="logicalId"]').val(_cmd.logicalId);
      jeedom.cmd.changeType(tr, init(_cmd.subType))
    }
  })
}





$(document).ready(function () {
  // Initialisation des workflows si on est sur la page d'équipement
  if ($('#bt_refreshWorkflow').length) {
    showManualWorkflowInput();
  }
  
  // Initialisation du système de gestion des équipements Jeedom
  
  
  $('.eqLogicAction[data-action="save"]').on('click', function () {
    var eqLogic = $('.eqLogic').getValues('.eqLogicAttr')[0];
    
    // Log des données avant envoi
    console.log('Données à sauvegarder:', eqLogic);
    console.log('Workflow ID avant sauvegarde:', eqLogic.configuration ? eqLogic.configuration.workflow_id : 'non défini');
    
    // Validation basique
    if (!eqLogic.name || eqLogic.name.trim() === '') {
      $('#div_alert').showAlert({message: '{{Le nom de l'équipement est obligatoire}}', level: 'warning'});
      return;
    }
    
    $.ajax({
      type: 'POST',
      url: 'plugins/n8nconnect/core/ajax/n8nconnect.ajax.php',
      data: {
        action: 'save',
        eqLogic: JSON.stringify(eqLogic)
      },
      dataType: 'json',
      error: function (request, status, error) {
        console.error('Erreur AJAX:', request.responseText);
        $('#div_alert').showAlert({message: '{{Erreur lors de la sauvegarde}}', level: 'danger'});
      },
      success: function (data) {
        if (data.state != 'ok') {
          $('#div_alert').showAlert({message: data.result, level: 'danger'});
          return;
        }
        
        console.log('Réponse de sauvegarde:', data.result);
        console.log('Workflow ID après sauvegarde:', data.result.configuration ? data.result.configuration.workflow_id : 'non défini');
        
        $('#div_alert').showAlert({message: '{{Équipement sauvegardé}}', level: 'success'});
        
        // Mettre à jour les données de l'équipement avec la réponse du serveur
        if (data.result) {
          $('.eqLogic').setValues(data.result, '.eqLogicAttr');
          
          // Si c'est un nouvel équipement, mettre à jour l'ID
          if (!eqLogic.id && data.result.id) {
            $('.eqLogicAttr[data-l1key=id]').val(data.result.id);
          }
          
          // Recharger les workflows pour s'assurer que la sélection est correcte
          if ($('#bt_refreshWorkflow').length) {
            loadWorkflows();
          }
        }
      }
    });
  });
  
  $('.eqLogicAction[data-action="remove"]').on('click', function () {
    if (confirm('{{Êtes-vous sûr de vouloir supprimer cet équipement ?}}')) {
      var eqLogicId = $('.eqLogicAttr[data-l1key=id]').val();
      console.log('Attempting to delete equipment with ID:', eqLogicId);

      if (!eqLogicId) {
        $('#div_alert').showAlert({message: '{{Impossible de supprimer un équipement non sauvegardé.}}', level: 'warning'});
        return;
      }

      $.ajax({
        type: 'POST',
        url: 'plugins/n8nconnect/core/ajax/n8nconnect.ajax.php',
        data: {
          action: 'remove',
          id: eqLogicId
        },
        dataType: 'json',
        error: function (request, status, error) {
          console.error('Erreur AJAX:', request.responseText);
          $('#div_alert').showAlert({message: '{{Erreur lors de la suppression}}', level: 'danger'});
        },
        success: function (data) {
          if (data.state != 'ok') {
            $('#div_alert').showAlert({message: data.result, level: 'danger'});
            return;
          }
          $('.eqLogic').hide();
          $('.eqLogicThumbnailDisplay').show();
          window.location.href = 'index.php?v=d&m=n8nconnect&p=n8nconnect';
        }
      });
    }
  });
  
  $('.eqLogicAction[data-action="returnToThumbnailDisplay"]').on('click', function () {
    $('.eqLogic').hide();
    $('.eqLogicThumbnailDisplay').show();
  });
  

  
  $('.eqLogicDisplayCard').on('click', function () {
    var eqLogic_id = $(this).attr('data-eqLogic_id');
    $.ajax({
      type: 'POST',
      url: 'plugins/n8nconnect/core/ajax/n8nconnect.ajax.php',
      data: {
        action: 'get',
        id: eqLogic_id
      },
      dataType: 'json',
      error: function (request, status, error) {
        console.error('Erreur AJAX:', request.responseText);
        $('#div_alert').showAlert({message: '{{Erreur lors du chargement}}', level: 'danger'});
      },
      success: function (data) {
        if (data.state != 'ok') {
          $('#div_alert').showAlert({message: data.result, level: 'danger'});
          return;
        }
        $('.eqLogic').setValues(data.result, '.eqLogicAttr');
        $('.eqLogicThumbnailDisplay').hide();
        $('.eqLogic').show();
        loadCmd();
        
      }
    });
  });
  
  function loadCmd() {
    var eqLogic_id = $('.eqLogicAttr[data-l1key=id]').val();
    if (!eqLogic_id) return;
    
    $.ajax({
      type: 'POST',
      url: 'plugins/hp_printer/core/ajax/hp_printer.ajax.php',
      data: {
        action: 'getCmd',
        id: eqLogic_id
      },
      dataType: 'json',
      error: function (request, status, error) {
        console.error('Erreur AJAX:', request.responseText);
        $('#div_alert').showAlert({message: '{{Erreur lors du chargement des commandes}}', level: 'danger'});
      },
      success: function (data) {
        if (data.state != 'ok') {
          $('#div_alert').showAlert({message: data.result, level: 'danger'});
          return;
        }
        $('#table_cmd tbody').empty();
        $.each(data.result, function (i, cmd) {
          addCmdToTable(cmd);
        });
      },
      complete: function() {
      }
    });
  }
  // Gestionnaires d'événements pour les boutons de commande (délégation)
  $(document).on('click', '#table_cmd .cmdAction[data-action=configure]', function () {
    console.log('Configure button clicked!');
    var cmd = $(this).closest('.cmd').getValues('.cmdAttr');
    console.log('Command object for configure:', cmd);
    console.log('Type of jeedom:', typeof jeedom);
    console.log('Type of jeedom.cmd:', typeof jeedom.cmd);
    console.log('jeedom.cmd object:', jeedom.cmd);
    if (typeof jeedom.cmd !== 'undefined' && typeof jeedom.cmd.configure === 'function') {
      jeedom.cmd.configure(cmd);
    } else {
      console.error('jeedom.cmd.configure is not a function or jeedom.cmd is undefined.');
    }
  });

  $(document).on('click', '#table_cmd .cmdAction[data-action=test]', function () {
    console.log('Test button clicked!');
    var cmd = $(this).closest('.cmd').getValues('.cmdAttr');
    console.log('Command object for test:', cmd);
    console.log('Type of jeedom:', typeof jeedom);
    console.log('Type of jeedom.cmd:', typeof jeedom.cmd);
    console.log('jeedom.cmd object:', jeedom.cmd);

    var options = {};
    if (cmd.logicalId === 'run' && cmd.configuration && cmd.configuration.parameters) {
      try {
        options = JSON.parse(cmd.configuration.parameters);
      } catch (e) {
        $('#div_alert').showAlert({message: '{{Les paramètres doivent être un JSON valide.}}', level: 'danger'});
        return;
      }
    }

    if (typeof jeedom.cmd !== 'undefined' && typeof jeedom.cmd.test === 'function') {
      jeedom.cmd.test(cmd, options);
    } else {
      console.error('jeedom.cmd.test is not a function or jeedom.cmd is undefined.');
    }
  });

  


})
}

