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

// Ce fichier contient les fonctions de gestion du cycle de vie du plugin HP Printer.
// Ces fonctions sont automatiquement appelées par Jeedom lors de l'installation, la mise à jour et la suppression du plugin.

// Inclut le fichier `core.inc.php` qui contient les fonctions et classes de base de Jeedom.
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement après l'installation du plugin
// Cette fonction est appelée une seule fois lors de la première installation du plugin.
// Elle peut être utilisée pour initialiser des paramètres, créer des tables en base de données, etc.
function hp_printer_install() {
    // Ajoute une entrée dans les logs de Jeedom pour confirmer l'installation.
    log::add('hp_printer', 'info', 'hp_printer installed successfully.');
}

// Fonction exécutée automatiquement après la mise à jour du plugin
// Cette fonction est appelée à chaque fois que le plugin est mis à jour vers une nouvelle version.
// Elle peut être utilisée pour migrer des données, ajouter de nouvelles configurations, etc.
function hp_printer_update() {
    // Ajoute une entrée dans les logs de Jeedom pour confirmer la mise à jour.
    log::add('hp_printer', 'info', 'hp_printer updated successfully.');
}

// Fonction exécutée automatiquement après la suppression du plugin
// Cette fonction est appelée lorsque le plugin est désinstallé de Jeedom.
// Elle peut être utilisée pour nettoyer les données, supprimer les configurations, etc.
function hp_printer_remove() {
    // Ajoute une entrée dans les logs de Jeedom pour confirmer la suppression.
    log::add('hp_printer', 'info', 'hp_printer removed successfully.');
}