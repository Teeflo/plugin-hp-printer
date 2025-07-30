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

// Ce fichier sert de point d'entrée pour inclure la classe principale du plugin HP Printer.
// Il assure que la classe `hp_printer` est disponible pour d'autres parties de Jeedom qui pourraient en avoir besoin.

// Inclut le fichier `hp_printer.class.php` qui contient la définition des classes `hp_printer` et `hp_printerCmd`.
// `dirname(__FILE__)` retourne le chemin du répertoire courant, assurant que le chemin est correct quelle que soit l'emplacement du fichier.
require_once dirname(__FILE__) . '/hp_printer.class.php';

?>
