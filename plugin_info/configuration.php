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

// Ce fichier gère la page de configuration globale du plugin HP Printer.
// Dans ce cas, il indique qu'aucune configuration globale n'est nécessaire.

// Inclut le fichier `core.inc.php` qui contient les fonctions et classes de base de Jeedom.
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
// Inclut le fichier d'authentification de Jeedom pour vérifier les droits d'accès.
include_file('core', 'authentification', 'php');

// Vérifie si l'utilisateur est connecté.
// Si l'utilisateur n'est pas connecté, il est redirigé vers une page d'erreur 404.
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>

<!-- Début du formulaire de configuration -->
<form class="form-horizontal">
  <fieldset>
    <!-- Groupe de formulaire pour le message d'information -->
    <div class="form-group">
      <!-- Label affichant le message indiquant qu'aucune configuration globale n'est requise. -->
      <label class="col-md-4 control-label">{{Aucune configuration globale nécessaire pour ce plugin.}}</label>
    </div>
  </fieldset>
</form>