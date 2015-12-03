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
 *
 * @package   plagiarism_vericite
 * @copyright 2015 Longsight, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'VeriCite';
$string['studentdisclosuredefault']  = 'Text and file submissions will be uploaded to a plagiarism detection service for instructor review.';
$string['studentdisclosure'] = 'Student Disclosure';
$string['studentdisclosure_help'] = '';
$string['vericiteexplain'] = 'VeriCite is a cloud-based service that identifies plagiarism by comparing submitted work against an ever-increasing database of sources. VeriCite is a commercial service and requires a valid subscription. 30-days trials are available at <a href="https://www.vericite.com" target="_blank">www.VeriCite.com</a>.<br/><br/>VeriCite is continuously monitored and <a href="http://status.vericite.com/" target="_blank"">status updates are available</a>.<br/><br/>News and system updates are available at <a href="https://updates.vericite.com" target="_blank"/>updates.vericite.com</a>.<br/><hr/>';
$string['vericite'] = 'VeriCite plagiarism plugin';
$string['usevericite'] = 'Enable VeriCite';
$string['savedconfigsuccess'] = 'Plagiarism Settings Saved';
$string['vericiteaccountid'] = 'Account Id';
$string['vericiteaccountid_help'] = 'The id provided as part of a trial agreement with VeriCite';
$string['vericitesecretkey'] = 'Secret Key';
$string['vericitesecretkey_help'] = 'The secret provided as part of a trial agreement with VeriCite';
$string['vericiteapi'] = 'API URL';
$string['vericiteapi_help'] = 'The API URL provided as part of a trial agreement with VeriCite';
$string['similarity'] = 'Similarity';
$string['vericitedefaultsettings'] = 'Default settings for new assignments:';
$string['vericitedefaultsettingsforums'] = 'Default settings for new forums:';
$string['usevericite_help'] = 'Enable if you want new assignments to have VeriCite enabled by default.';
$string['usevericite'] = 'Enable VeriCite Plagiarism Service';
$string['studentscorevericite'] = 'Allow students to see scores';
$string['studentscorevericite_help'] = 'Select checkbox if you want to allow students to see their scores.';
$string['studentreportvericite'] = 'Allow students to see reports';
$string['studentreportvericite_help'] = 'Select checkbox if you want to allow students to see their reports.';
$string['advanced_settings'] = 'Advanced Settings';
$string['disable_dynamic_inline'] = 'Disable dynamic inline submissions';
$string['disable_dynamic_inline_help'] = 'Disabling dynamic inline submissions will result in one-time submissions only. Modifications to the inline text by the student will not be re-submitted to VeriCite.';
$string['enable_debugging'] = 'Enable debugging';
$string['enable_debugging_help'] = 'Enable debugging for the VeriCite module. Errors will be printed to a vericite.log file in the Moodle dataroot.';
$string['excludequotesvericite'] = 'Exclude Quotes';
$string['excludequotesvericite_help'] = 'The default for all reports submitted to a Moodle assignment. To reduce the number of false VeriCite matches, we recommend excluding quotes by default. Instructors will still retain the ability to view matches within quotes while viewing an individual VeriCite report.';
