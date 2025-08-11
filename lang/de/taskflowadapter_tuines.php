<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains language strings for the taskflow adapter.
 *
 * @package     taskflowadapter_tuines
 * @copyright   2025 Wunderbyte GmbH
 * @author      David Ala
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['apisettings'] = "INES API Einstellungen";
$string['apisettings_desc'] = "Konfiguriere Taskflow-Schlüssel-Wert-Paare.";
$string['assignedpackages'] = "Zugewiesene Pakete";
$string['change_reason_errordeclined'] = "Bei Ablehnung der Fristverlängerung darf kein Grund ausgewählt werden";
$string['change_reason_errorextension'] = "Bei Fristverlängerung muss ein Grund ausgewählt werden";
$string['choose'] = "Wähle...";
$string['comment_denied_errordeclined'] = "Bei Ablehnung der Fristverlängerung muss der Kommentar ausgefüllt sein";
$string['comment_denied_errorextension'] = "Bei Fristverlängerung muss der Kommentar für die Ablehnung leer sein";
$string['denyextension'] = "Verlängerung ablehnen";
$string['denytext'] = "Liegen keine triftigen Gründe für eine Fristverlängerung vor, kann diese abgelehnt werden. Die Angabe einer Begründung für die Ablehnung ist verpflichtend. Achtung: Mitarbeitende, die Standardschulungen nicht fristgerecht absolvieren, müssen mit einer schriftlichen Verwarnung und entsprechenden arbeitsrechtlichen Konsequenzen rechnen.";
$string['enter_value'] = 'Gib einen passenden JSON- Schlüssel für diese Einstellung ein';
$string['extensiontext'] = 'Wenn entsprechende triftige Gründe vorliegen, sodass Mitarbeitende die Standardschulung nicht innerhalb der vorgesehenen Frist ablegen können, haben Vorgesetzte die Möglichkeit, die Frist einmalig zu verlängern. Die verpflichtende Angabe eines Grundes ist hierfür erforderlich.';
$string['function'] = 'Funktion zuordnen zu Benutzerprofilfeld: ';
$string['grantextension'] = "Frist verlängern";
$string['internalid'] = 'Interne-ID';
$string['jsonkey'] = 'JSON Schlüssel für Benutzerprofilfeld: ';
$string['lessfunctions'] = '<div class="alert alert-danger" role="alert">Nicht alle Funktionen wurden beim letzten Speichern ausgewählt. Dies kann zu Fehlern führen.</div>';
$string['manyfunctions'] = '<div class="alert alert-danger" role="alert">Funktionen wurden mehrfach ausgewählt beim letzten Speichern. Dies kann zu Fehlern führen.</div>';
$string['mappingdescription'] = 'Taskflow-Schlüssel-Wert-Paare Erklärung';
$string['mappingdescription_desc'] = 'Hier wird die Verknüpfung erstellt. Das obere Feld gibt an zu welchen JSON- Feld mit dem Benutzerprofilfeld verknüpft werden soll. Das untere Feld gibt an, welche Fuktion dieses Feld representiert. Nicht jedes Benutzerprofilfeld muss eine Funktion haben.';
$string['necessaryuserprofilefields'] = "Benutzerprofilfelder für Taskflow die unbedingt befüllt sien müssen";
$string['necessaryuserprofilefieldsdesc'] = "Benutzerprofilfelder, die befüllt sein müssen, damit der Benutzer*innen für ein Taskflow-Update berücksichtigt wird. Wenn die ausgewählten Felder nicht befüllt sind, werden Änderungen von Benutzer*innen in Wunderbyte Taskflow nicht berücksichtig. Lassen Sie diese Einstellung leer, wenn es keine Felder gibt die befüllt sein müssen.";
$string['pluginname'] = "INES";
$string['set:function'] = 'Wählen Sie eine Funktion aus';
$string['subplugintype_taskflowadapter_plural'] = 'Taskflow-Adapter-Erweiterungen';
$string['targetgroupfields'] = '<i class="fa-solid fa-people-group" aria-hidden="true"></i><strong> Felder für die Einheit </strong>';
$string['tuines'] = "Ines API";
$string['usermappingfields'] = '<i class="fa-solid fa-user" aria-hidden="true"></i> <strong> Felder für Benutzer </strong>';
