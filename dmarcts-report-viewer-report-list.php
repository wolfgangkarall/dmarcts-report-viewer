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
// 
// 
//####################################################################
//### functions ######################################################
//####################################################################

function tmpl_reportList($reports, $sort) {

    $reportlist[] = "";

	if (sizeof($reports) == 0) {
		$reportlist[] = "<div class='center'><b>No Reports Match this filter</b><br />Click the <i>Reset</i> button or choose a different value for <i>DMARC Result</i>, <i>Month</i>, <i>Domain(s)</i> or <i>Reporter(s)</i>.</div>";
	} else {
		$title_message = "Click to toggle sort direction by this column";
	// echo $sort;
		$reportlist[] = "<table id='reportlistTbl' class='reportlist'>";
		$reportlist[] = "  <thead>";
		$reportlist[] = "    <tr>";
		$reportlist[] = "      <th class='circle_container' style='padding-left: 5px' title='DMARC Result. " . $title_message . "'><div class='circle circle_left circle_black'></div><span style='display:none;'>1</span></span></th>";
		$reportlist[] = "      <th class='circle_container'></th>";
		$reportlist[] = "      <th class='circle_container' title='SPF/DKIM/DMARC Results. " . $title_message . "'><div class='circle circle_right circle_black'></div><span style='display:none;'>1</span></span></th>";
		$reportlist[] = "      <th class=\"" . strtolower($sort) . "_triangle\" title='" . $title_message . "'>Start Date</th>";
		$reportlist[] = "      <th title='" . $title_message . "'>End Date</th>";
		$reportlist[] = "      <th title='" . $title_message . "'>Domain</th>";
		$reportlist[] = "      <th title='" . $title_message . "'>Reporting Organization</th>";
		$reportlist[] = "      <th title='" . $title_message . " (currently doesn&#39;t really sort well)'>Report ID</th>";
		$reportlist[] = "      <th title='" . $title_message . "'>Messages</th>";
		$reportlist[] = "    </tr>";
		$reportlist[] = "  </thead>";

		$reportlist[] = "  <tbody>";
		$reportsum    = 0;

		foreach ($reports as $row) {
			$row = array_map('htmlspecialchars', $row);
			$date_output_format = "Y-m-d G:i:s T";
			$reportlist[] =  "    <tr class='linkable' onclick=\"showReport('" . $row['serial'] . "')\" id='report" . $row['serial'] . "'>";

			$reportlist[] =  "      <td class='circle_container'><span style='display:none;'>" . get_dmarc_result($row)[1] . "</span></td>"; // Col 0
			$reportlist[] =  "      <td class='circle_container'><div style='white-space: nowrap;' title='DMARC Result: " . get_dmarc_result($row)[2] . "\nSPF/DKIM/DMARC Reuslts: " . get_dmarc_status($row)[2] . "'><div class='circle circle_left circle_" . get_dmarc_result($row)[0] . "'></div><div class='circle circle_right circle_" . get_dmarc_status($row)[0] . "'></div></div></td>"; // Col 0
			$reportlist[] =  "      <td class='circle_container'><span style='display:none;'>" . get_dmarc_status($row)[1] . "</span></span></td>"; // Col 0
			$reportlist[] =  "      <td class='right'>". format_date($row['mindate'], $date_output_format). "</td>";   // Col 1
			$reportlist[] =  "      <td class='right'>". format_date($row['maxdate'], $date_output_format). "</td>";   // Col 3
			$reportlist[] =  "      <td class='center'>". $row['domain']. "</td>";                                     // Col 5
			$reportlist[] =  "      <td class='center'>". $row['org']. "</td>";                                        // Col 6
			$reportlist[] =  "      <td class='center'>". $row['reportid'] . "</td>";
			$reportlist[] =  "      <td class='right'>". number_format($row['rcount']+0,0). "</td>";                  // Col 9
			$reportlist[] =  "    </tr>";
			$reportsum += $row['rcount'];
		}

		$reportlist[] =  "  </tbody>";
		$reportlist[] = "<tr class='sum'><td class='circle_container'></td><td class='circle_container'></td><td class='circle_container'></td><td></td><td></td><td></td><td></td><td class='right'>Sum:</td><td class='right'>".number_format($reportsum,0)."</td></tr>";
		$reportlist[] =  "</table>";

		$reportlist[] = "<!-- End of report list -->";
		$reportlist[] = "";
	}
    #indent generated html by 2 extra spaces
    return implode("\n  ",$reportlist);
}

//####################################################################
//### main ###########################################################
//####################################################################

// These files are expected to be in the same folder as this script, and must exist.
include "dmarcts-report-viewer-config.php";
include "dmarcts-report-viewer-common.php";

$dom_select= '';
$org_select= '';
$per_select= '';
$dmarc_select= '';
$where = '';

// Parameters of GET
// --------------------------------------------------------------------------

if(isset($_GET['hostlookup']) && is_numeric($_GET['hostlookup'])){
	$hostlookup=$_GET['hostlookup']+0;
}elseif(!isset($_GET['hostlookup'])){
	$hostlookup= isset( $default_lookup ) ? $default_lookup : 1;
}else{
	die('Invalid hostlookup flag');
}

if(isset($_GET['sortorder']) && is_numeric($_GET['sortorder'])){
	$sortorder=$_GET['sortorder']+0;
}elseif(!isset($_GET['sortorder'])){
	$sortorder= isset( $default_sort ) ? $default_sort : 1;
}else{
	die('Invalid sortorder flag');
}

if(isset($_GET['d'])){
	$dom_select=$_GET['d'];
}else{
	$dom_select= '';
}

if( $dom_select == "all" ) {
	$dom_select= '';
}

if(isset($_GET['o'])){
	$org_select=$_GET['o'];
}else{
	$org_select= '';
}

if( $org_select == "all" ) {
	$org_select= '';
}

if(isset($_GET['p'])){
	$per_select=$_GET['p'];
}else{
	$per_select= date( 'Y-m' );
}

if( $per_select == "all" ) {
	$per_select= '';
}

if(isset($_GET['dmarc'])){
	$dmarc_select=$_GET['dmarc'];
}else{
	$dmarc_select= '';
}

// Debug
// echo "<br />D=$dom_select <br /> O=$org_select <br />";
// echo "<br />DMARC=$dmarc_select<br />";

// Make a MySQL Connection using mysqli
// --------------------------------------------------------------------------
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport);

if ($mysqli->connect_errno) {
	echo "Errno: " . $mysqli->connect_errno . " ";
	echo "Error: " . $mysqli->connect_error . " ";
// Debug ONLY. This will expose database credentials when database connection fails
// 	echo "Database connection information: <br />dbhost: " . $dbhost . "<br />dbuser: " . $dbuser . "<br />dbpass: " . $dbpass . "<br />dbname: " . $dbname . "<br />dbport: " . $dbport . "<br />";
	exit;
}

// Get allowed reports and cache them - using serial as key
// --------------------------------------------------------------------------
$reports = array();

// set sort direction
// --------------------------------------------------------------------------
$sort = '';
if( $sortorder ) {
  $sort = "ASC";
} else {
  $sort = "DESC";
}

// Build SQL WHERE clause

// DMARC
// dkimresult spfresult
// --------------------------------------------------------------------------
switch ($dmarc_select) {
	case "all": // Everything
		break;
	case 0: // DMARC Fail
		$where .= ( $where <> '' ? " AND" : " WHERE" ) . " dmarc_result_min=0 AND dmarc_result_max=0";
		break;
	case 1: // DMARC Pass and Fail
		$where .= ( $where <> '' ? " AND" : " WHERE" ) . " dmarc_result_min=0 AND (dmarc_result_max=1 OR dmarc_result_max=2)";
		break;
	case 2: // Other condition: Yellow
		$where .= ( $where <> '' ? " AND" : " WHERE" ) . " dmarc_result_min>=3 AND dmarc_result_max>=3";
		break;
	case 3: // DMARC Pass
		$where .= ( $where <> '' ? " AND" : " WHERE" ) . " (dmarc_result_min=1 OR dmarc_result_min=2) AND (dmarc_result_max<=2)";
		break;
	default: 
		break;
}

// Domains
// --------------------------------------------------------------------------
if( $dom_select <> '' ) {
	$where .= ( $where <> '' ? " AND" : " WHERE" ) . " domain='" . $mysqli->real_escape_string($dom_select) . "'";
}

// Organisations
// --------------------------------------------------------------------------
if( $org_select <> '' ) {
	$where .= ( $where <> '' ? " AND" : " WHERE" ) . " org='" . $mysqli->real_escape_string($org_select) . "'";
}

// Periods
// --------------------------------------------------------------------------
if( $per_select <> '' ) {
	$ye = substr( $per_select, 0, 4) + 0;
	$mo = substr( $per_select, 5, 2) + 0;
	$where .= ( $where <> '' ? " AND" : " WHERE" ) . " ((year(mindate)=$ye AND month(mindate) =$mo) OR (year(maxdate)=$ye AND month(maxdate) =$mo)) ";
}

// Include the rcount via left join, so we do not have to make an sql query
// for every single report.
// --------------------------------------------------------------------------

// Query explanation
// This query links the report and rptrecord tables together by the serial id (report LEFT JOIN ... ON report.serial = rptrecord.serial FROM rptrecord)
//
// The CASE ... END statements assign a numerical value to the test results:
//     In the case of dkimresult and spfresult:
//         'fail' is given a value of 0
//         'pass' is given a value of 2
//         other conditions, like NULL, 'neutral' or 'softfail' are given a value of 1
//     In the case of dkim_align and spf_align:
//         'fail' is given a value of 0
//         'pass' is given a value of 1 or 2, depending on what is being analyzed
//         other conditions, like NULL, 'neutral' or 'softfail' are given a value of 1 or 3, depending on what is being analyzed
// 
$sql = "
SELECT
    report.*,
    rcount,
    dkim_align_min,
    spf_align_min,
    dkim_result_min,
    spf_result_min,
    dmarc_result_min,
    dmarc_result_max    
FROM
    report
	LEFT JOIN
		(
			SELECT
				SUM(rcount) as rcount,
				serial,
				dkim_align,
				spf_align,
				dkimresult,
				spfresult,
				MIN(
					(CASE
						WHEN dkim_align = 'fail' THEN 0
						WHEN dkim_align = 'pass' THEN 2
						ELSE 1
					END)
				)
				AS dkim_align_min,
				MIN(
					(CASE
						WHEN spf_align = 'fail' THEN 0
						WHEN spf_align = 'pass' THEN 2
						ELSE 1
					END)
				)
				AS spf_align_min,
				MIN(
					(CASE
						WHEN dkimresult = 'fail' THEN 0
						WHEN dkimresult = 'pass' THEN 2
						ELSE 1
					END)
				)
				AS dkim_result_min,
				MIN(
					(CASE
						WHEN spfresult = 'fail' THEN 0
						WHEN spfresult = 'pass' THEN 2
						ELSE 1
					END)
				)
				AS spf_result_min,
				MIN(
					(CASE
						WHEN dkim_align = 'fail' THEN 0
						WHEN dkim_align = 'pass' THEN 1
						ELSE 3
					END)
					+
					(CASE
						WHEN spf_align = 'fail' THEN 0
						WHEN spf_align = 'pass' THEN 1
						ELSE 3
					END)
				)
				AS dmarc_result_min,
				MAX(
					(CASE
						WHEN dkim_align = 'fail' THEN 0
						WHEN dkim_align = 'pass' THEN 1
						ELSE 3
					END)
					+
					(CASE
						WHEN spf_align = 'fail' THEN 0
						WHEN spf_align = 'pass' THEN 1
						ELSE 3
					END)
				)
				AS dmarc_result_max
			FROM
				rptrecord
			GROUP BY serial
		)
	AS rptrecord
ON
	report.serial = rptrecord.serial
$where
GROUP BY serial
ORDER BY
    mindate $sort,
    org";
    
// Debug
// echo "<br />sql where = $where<br />";
// echo "<br /><b>Data List sql:</b>  $sql<br />";
// echo "<br />per_select = " . urlencode($per_select) . "<br />";

$query = $mysqli->query($sql) or die("Query failed: ".$mysqli->error." (Error #" .$mysqli->errno.")");
while($row = $query->fetch_assoc()) {
    if (true) {
        //add data by serial
        $reports[$row['serial']] = $row;
    }
}

// Generate Report List
// --------------------------------------------------------------------------
echo tmpl_reportList($reports, $sort);

?>
