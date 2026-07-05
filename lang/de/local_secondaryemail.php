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
 * Strings for secondary email local plugin (German).
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowuserexclusions'] = 'Nutzer dürfen Benachrichtigungen anpassen';
$string['allowuserexclusions_help'] = 'Wenn aktiviert, können Nutzer einzelne Benachrichtigungstypen deaktivieren. Nutzer können nur Benachrichtigungen deaktivieren, die vom Admin aktiviert wurden.';
$string['availabletags'] = 'Verfügbare Tags';
$string['availabletags_help'] = 'Geben Sie einen Tag pro Zeile ein. Diese stehen als Optionen beim Taggen von zweiten E-Mail-Adressen zur Verfügung.';
$string['confirmationemailbody'] = 'Hallo,

du hast {$a->email} als zweite E-Mail-Adresse für {$a->fullname} auf {$a->sitename} angegeben.

Bitte bestätige die Adresse über diesen Link: {$a->link}

{$a->policyline}

Wenn du dies nicht angefordert hast, kannst du diese E-Mail ignorieren.';
$string['confirmationemailpolicy'] = 'Datenschutzrichtlinie: {$a}';
$string['confirmationemailsubject'] = 'Bitte bestätige die zweite E-Mail-Adresse für {$a->fullname}';
$string['confirmationexpired'] = 'Der Bestätigungslink ist abgelaufen. Bitte fordere eine neue Bestätigungs-E-Mail an.';
$string['confirmationinvalid'] = 'Der Bestätigungslink ist ungültig oder abgelaufen.';
$string['confirmationpagetitle'] = 'Zweite E-Mail bestätigen';
$string['confirmationsuccess'] = 'Die zweite E-Mail-Adresse wurde verifiziert.';
$string['editsecondaryemail'] = 'Zweite E-Mail-Adresse bearbeiten';
$string['enabledproviders'] = 'Benachrichtigungen aktivieren';
$string['enabledproviders_help'] = 'Wählen Sie aus, welche Benachrichtigungstypen an die zweite E-Mail-Adresse gesendet werden sollen. Nur aktivierte Benachrichtigungen werden weitergeleitet. Standardmäßig ist nichts aktiviert.';
$string['enabletag'] = 'Tagging aktivieren';
$string['enabletag_help'] = 'Ermöglicht Admins, zweite E-Mail-Adressen mit einem Tag zu versehen (z.B. Eltern, Sorgeberechtigte, Arbeitgeber).';
$string['fieldlockedbyplugin'] = 'Gesperrt durch Plugin';
$string['invalidsecondaryemailnotice'] = 'Der Wert der zweiten E-Mail-Adresse wurde entfernt, weil er keine gültige E-Mail-Adresse ist. Bitte geben Sie eine gültige Adresse hier ein: {$a}';
$string['invalidtag'] = 'Ungültiger Tag';
$string['noprovidersenabled'] = 'Es wurden keine Benachrichtigungstypen vom Administrator für die Weiterleitung an die zweite E-Mail aktiviert.';
$string['notificationsettings'] = 'Benachrichtigungsfilter';
$string['notificationsettings_desc'] = 'Standardmäßig werden keine Benachrichtigungen an die zweite E-Mail-Adresse gesendet (datenschutzkonform). Aktivieren Sie gezielt die gewünschten Benachrichtigungstypen.';
$string['pluginname'] = 'Zweite E-Mail Benachrichtigungen';
$string['preferencessaved'] = 'Ihre Benachrichtigungseinstellungen wurden gespeichert.';
$string['privacy:metadata:prefdisabled'] = 'Speichert, ob der Versand an die zweite E-Mail-Adresse gesperrt ist.';
$string['privacy:metadata:prefdisabledproviders'] = 'Speichert die vom Nutzer deaktivierten Benachrichtigungstypen für die zweite E-Mail.';
$string['privacy:metadata:prefpending'] = 'Speichert die zweite E-Mail-Adresse, die auf Bestätigung wartet.';
$string['privacy:metadata:prefrelationship'] = 'Speichert den Tag für die zweite E-Mail-Adresse.';
$string['privacy:metadata:preftoken'] = 'Speichert das Bestätigungs-Token für die zweite E-Mail-Adresse.';
$string['privacy:metadata:preftokentime'] = 'Speichert den Zeitpunkt der Token-Erstellung für die zweite E-Mail-Adresse.';
$string['privacy:metadata:prefverified'] = 'Speichert die verifizierte zweite E-Mail-Adresse.';
$string['profilecategory'] = 'Zusätzliche E-Mail';
$string['profilefieldlink'] = 'Profilfeld-Einstellungen';
$string['quicklinks'] = 'Schnellzugriff';
$string['quicklinks_desc'] = 'Profilfeld-Sichtbarkeit verwalten und Nutzerliste anzeigen:';
$string['relationshipemployer'] = 'Arbeitgeber';
$string['relationshipfather'] = 'Vater';
$string['relationshipguardian'] = 'Sorgeberechtigte/r';
$string['relationshipmother'] = 'Mutter';
$string['relationshipother'] = 'Sonstige';
$string['removetagaction'] = 'Tag entfernen';
$string['secondaryemail:configureown'] = 'Eigene zweite E-Mail-Adresse konfigurieren';
$string['secondaryemail:manage'] = 'Zweite E-Mail-Adressen für andere Nutzer verwalten';
$string['secondaryemail:viewreport'] = 'Nutzerliste mit zweiter E-Mail-Adresse anzeigen';
$string['secondaryemailaddaction'] = 'Zweite E-Mail hinzufügen';
$string['secondaryemailalreadyverified'] = 'Die zweite E-Mail-Adresse ist bereits bestätigt.';
$string['secondaryemailblockaction'] = 'Sperren';
$string['secondaryemailblocked'] = 'Der Versand an die zweite E-Mail-Adresse wurde gesperrt.';
$string['secondaryemailblockedtag'] = 'gesperrt';
$string['secondaryemailcategorylocked'] = 'Die Kategorie für die zweite E-Mail ist gesperrt und kann nicht bearbeitet, verschoben oder gelöscht werden.';
$string['secondaryemailcategorywarning'] = 'Diese Kategorie wird vom Plugin "Zweite E-Mail-Adresse" verwaltet. Der Name ist gesperrt.';
$string['secondaryemailconfirmationsent'] = 'Der Bestätigungslink für die zweite E-Mail wurde erneut gesendet.';
$string['secondaryemaildeleteaction'] = 'Zweite E-Mail löschen';
$string['secondaryemaildeleteconfirm'] = 'Die zweite E-Mail-Adresse "{$a}" löschen?';
$string['secondaryemaildeleted'] = 'Die zweite E-Mail-Adresse wurde gelöscht.';
$string['secondaryemailfielddesc'] = 'Zusätzliche E-Mail-Adresse für verifizierte Benachrichtigungskopien.';
$string['secondaryemailfieldlocked'] = 'Das Profilfeld für die zweite E-Mail ist gesperrt und kann nicht umbenannt oder gelöscht werden.';
$string['secondaryemailfieldname'] = 'Zweite E-Mail';
$string['secondaryemailfieldwarning'] = 'Dieses Profilfeld wird vom Plugin "Zweite E-Mail-Adresse" verwaltet. Die markierten Felder sind gesperrt, um die Funktionalität des Plugins zu gewährleisten.';
$string['secondaryemailinvalid'] = 'Die zweite E-Mail-Adresse ist ungültig oder nicht erlaubt.';
$string['secondaryemaillockedlabel'] = 'Gesperrt';
$string['secondaryemailmissing'] = 'Es ist keine zweite E-Mail-Adresse hinterlegt.';
$string['secondaryemailnotverified'] = 'nicht bestätigt';
$string['secondaryemailpendingtag'] = 'ausstehend';
$string['secondaryemailreport'] = 'Nutzerliste mit zweiter E-Mail-Adresse';
$string['secondaryemailresendaction'] = 'Bestätigungslink erneut senden';
$string['secondaryemailstatusfilter'] = 'Status zweite E-Mail';
$string['secondaryemailunblockaction'] = 'Entsperren';
$string['secondaryemailunblocked'] = 'Der Versand an die zweite E-Mail-Adresse wurde entsperrt.';
$string['secondaryemailverifiedtag'] = 'verifiziert';
$string['settagaction'] = 'Tag festlegen';
$string['taggingsettings'] = 'Tagging';
$string['tagremoved'] = 'Tag entfernt';
$string['tagset'] = 'Tag auf "{$a}" gesetzt.';
$string['userexclusionsdisabled'] = 'Die Nutzeranpassung von Benachrichtigungen ist derzeit vom Administrator deaktiviert.';
$string['userpreferences_info'] = 'Die folgenden Benachrichtigungstypen wurden vom Administrator für die zweite E-Mail-Adresse aktiviert. Markieren Sie die Benachrichtigungen, die Sie deaktivieren möchten. Markierte Benachrichtigungen werden NICHT an Ihre zweite E-Mail gesendet.';
$string['userpreferencestitle'] = 'Benachrichtigungen für zweite E-Mail';
$string['usersettings'] = 'Nutzeranpassung';
$string['verificationexpiry'] = 'Gültigkeit des Bestätigungslinks (Stunden)';
$string['verificationexpiry_help'] = 'Lege fest, wie viele Stunden der Bestätigungslink gültig ist. 0 bedeutet keine Begrenzung.';
$string['verificationsettings'] = 'Verifizierung';
$string['verifiedemailbody'] = 'Hallo,

die E-Mail-Adresse {$a->email} wurde erfolgreich als zweite E-Mail-Adresse für {$a->fullname} auf {$a->sitename} bestätigt.

Ab sofort erhalten Sie Kopien ausgewählter Benachrichtigungen von {$a->sitename}.

Falls Sie diese E-Mails nicht mehr erhalten möchten, wenden Sie sich bitte an die Website-Administration, um die Profileinstellungen anzupassen.';
$string['verifiedemailsubject'] = 'Zweite E-Mail-Adresse für {$a->fullname} bestätigt';
