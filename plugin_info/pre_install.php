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

// Ce fichier contient les fonctions exécutées avant la mise à jour du plugin HP Printer.
// Il est utilisé pour effectuer des opérations préparatoires avant qu'une nouvelle version du plugin ne soit installée.

// Inclut le fichier `core.inc.php` qui contient les fonctions et classes de base de Jeedom.
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement avant la mise à jour du plugin
// Cette fonction est appelée par Jeedom juste avant le processus de mise à jour du plugin.
// Elle peut être utilisée pour des tâches telles que la sauvegarde de données, la vérification de prérequis,
// ou la préparation de la base de données pour les changements de la nouvelle version.
function hp_printer_pre_update() {
    // Ajoute une entrée dans les logs de Jeedom pour indiquer que le hook de pré-mise à jour a été exécuté.
    log::add('hp_printer', 'info', 'hp_printer pre-update hook executed.');
}
