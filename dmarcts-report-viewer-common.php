<?php

// dmarcts-report-viewer - A PHP based viewer of parsed DMARC reports.
// Copyright (C) 2016 TechSneeze.com, John Bieling and John P. New
// with additional extensions (sort order) of Klaus Tachtler.
//
// Available at:
// https://github.com/techsneeze/dmarcts-report-viewer
//
// This program is free software: you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or
// FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
// more details.
//
// You should have received a copy of the GNU General Public License along with
// this program.  If not, see <http://www.gnu.org/licenses/>.
//
//####################################################################
//### configuration ##################################################
//####################################################################

// Copy dmarcts-report-viewer-config.php.sample to
// dmarcts-report-viewer-config.php and edit with the appropriate info
// for your database authentication and location.
//
// Edit the configuration variables in dmarcts-report-viewer.js with your preferences.

//####################################################################
//### variables ######################################################
//####################################################################

// The order in which the options appear here is the order they appear in the DMARC Results dropdown box
$dmarc_result = array(

	'DMARC_PASS' => array(
		'text' => 'Pass',
		'color' => 'green',
		'status_num' => 3,
	),
	'DMARC_FAIL' => array(
		'text' => 'Fail',
		'color' => 'red',
		'status_num' => 0,
	),
	'DMARC_PASS_AND_FAIL' => array(
		'text' => 'Mixed',
		'color' => 'orange',
		'status_num' => 1,
	),
	'DMARC_OTHER_CONDITION' => array(
		'text' => 'Other',
		'color' => 'gold',
		'status_num' => 2,
	),
);

$dmarc_status = array(

	'STATUS_FAIL' => array(
		'text' => 'All Failed',
		'color' => 'red',
		'status_num' => 0,
	),
	'STATUS_ONE_OR_MORE_FAIL' => array(
		'text' => 'At least one failed',
		'color' => 'orange',
		'status_num' => 1,
	),
	'STATUS_OTHER_CONDITION' => array(
		'text' => 'Other condition',
		'color' => 'yellow',
		'status_num' => 2,
	),
	'STATUS_PASS' => array(
		'text' => 'All Passed',
		'color' => 'green',
		'status_num' => 3,
	),
);

//####################################################################
//### functions ######################################################
//####################################################################

function main() {

	include "dmarcts-report-viewer-config.php";
}

// This function sets variables for the DMARC Result portion (left half-circle) in the Report List
function get_dmarc_result($row) {

	global $dmarc_result;
	$status = "";
	$status_num = "";
	$result_text = "";
	
	if (($row['dmarc_result_min'] == 0) && ($row['dmarc_result_max'] == 0)) {
		$status     = $dmarc_result['DMARC_FAIL']['color'];
		$status_num = $dmarc_result['DMARC_FAIL']['status_num'];
		$result_text = $dmarc_result['DMARC_FAIL']['text'];
	} elseif (($row['dmarc_result_min'] == 0) && ($row['dmarc_result_max'] == 1 || $row['dmarc_result_max'] == 2)) {
		$status     = $dmarc_result['DMARC_PASS_AND_FAIL']['color'];
		$status_num = $dmarc_result['DMARC_PASS_AND_FAIL']['status_num'];
		$result_text = $dmarc_result['DMARC_PASS_AND_FAIL']['text'];
	} elseif (($row['dmarc_result_min'] == 1 || $row['dmarc_result_min'] == 2) && ($row['dmarc_result_max'] == 1 || $row['dmarc_result_max'] == 2)) {
		$status     = $dmarc_result['DMARC_PASS']['color'];
		$status_num = $dmarc_result['DMARC_PASS']['status_num'];
		$result_text = $dmarc_result['DMARC_PASS']['text'];
	} else {
		$status     = $dmarc_result['DMARC_OTHER_CONDITION']['color'];
		$status_num = $dmarc_result['DMARC_OTHER_CONDITION']['status_num'];
		$result_text = $dmarc_result['DMARC_OTHER_CONDITION']['text'];
	}
	return array($status, $status_num, $result_text);
}

// This function sets variables for the All Results portion (right half-circle) in the Report List table
function get_dmarc_status($row) {

	global $dmarc_status;
	$status = "";
	$status_num = "";
	$result_text = "";

	$dmarc_status_min = min($row['dkim_align_min'],$row['spf_align_min'],$row['dkim_result_min'],$row['spf_result_min'],$row['dmarc_result_min']);
	
	if ($row['dkim_align_min'] == 0 && $row['spf_align_min'] == 0 && $row['dkim_result_min'] == 0 && $row['spf_result_min'] == 0 && $row['dmarc_result_min'] == 0) {
		$status = $dmarc_status['STATUS_FAIL']['color'];
		$status_num = $dmarc_status['STATUS_FAIL']['status_num'];
		$result_text = $dmarc_status['STATUS_FAIL']['text'];
	} else {	
		switch ($dmarc_status_min) {
			case 0:
				$status = $dmarc_status['STATUS_ONE_OR_MORE_FAIL']['color'];
				$status_num = $dmarc_status['STATUS_ONE_OR_MORE_FAIL']['status_num'];
				$result_text = $dmarc_status['STATUS_ONE_OR_MORE_FAIL']['text'];
				break;
			case 1:
				$status = $dmarc_status['STATUS_OTHER_CONDITION']['color'];
				$status_num = $dmarc_status['STATUS_OTHER_CONDITION']['status_num'];
				$result_text = $dmarc_status['STATUS_OTHER_CONDITION']['text'];
				break;
			case 2:
				$status = $dmarc_status['STATUS_PASS']['color'];
				$status_num = $dmarc_status['STATUS_PASS']['status_num'];
				$result_text = $dmarc_status['STATUS_PASS']['text'];
				break;
			default:
				break;
		}
	}

	return array($status, $status_num, $result_text);
}

// This function sets variables for individual cells in the Report Data table
function get_status_color($result) {

	global $dmarc_status;
	$status = "";
	$status_num = "";
	
	if ($result == "fail") {
		$status = $dmarc_status['STATUS_FAIL']['color'];
#		$status_num = $dmarc_status['STATUS_FAIL']['status_num'];
	} elseif ($result == "pass") {
		$status = $dmarc_status['STATUS_PASS']['color'];
#		$status_num = $dmarc_status['STATUS_PASS']['status_num'];
	} else {
		$status = $dmarc_status['STATUS_OTHER_CONDITION']['color'];
#		$status_num = $dmarc_status['STATUS_OTHER_CONDITION']['status_num'];
	}

    return array($status, $status_num);
}

function format_date($date, $format) {

    $answer = date($format, strtotime($date));
    return $answer;
};

