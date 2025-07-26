<?php
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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Adresse IP de l'imprimante}}</label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="hp_printer_ip" placeholder="192.168.1.100"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Protocole}}</label>
      <div class="col-md-4">
        <select class="configKey form-control" data-l1key="hp_printer_protocol">
          <option value="http">{{HTTP}}</option>
          <option value="https">{{HTTPS}}</option>
        </select>
      </div>
      <div class="col-md-2">
        <a class="btn btn-default" id="bt_testHpPrinter"><i class="fas fa-check"></i> {{Tester}}</a>
      </div>
    </div>
  </fieldset>
</form>
<script>
$('#bt_testHpPrinter').on('click', function(){
  var ip_address = $('.configKey[data-l1key=hp_printer_ip]').val();
  var protocol = $('.configKey[data-l1key=hp_printer_protocol]').val(); // Assuming a protocol field will be added
  jeedomUtils.hideAlert();

  // Simple validation for IP address format (IPv4)
  var ipRegex = /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/;
  if (!ipRegex.test(ip_address)) {
      $('#div_alert').showAlert({message: '{{Veuillez entrer une adresse IP valide (ex: 192.168.1.100)}}', level: 'danger'});
      return; // Stop execution if IP is invalid
  }

  $.ajax({
    type: 'POST',
    url: 'plugins/hp_printer/core/ajax/hp_printer.ajax.php',
    data: {
      action: 'test',
      ip_address: ip_address,
      protocol: protocol
    },
    dataType: 'json',
    error: function (request, status, error) {
      console.error('Erreur AJAX:', request.responseText);
            $('#div_alert').showAlert({message: '{{Erreur lors du test de connexion}}', level: 'danger'});
    },
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({message: data.result, level: 'danger'});
      } else {
        $('#div_alert').showAlert({message: data.result, level: 'success'});
      }
    }
  });
});
</script>
