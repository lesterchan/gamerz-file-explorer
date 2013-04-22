<?php
/*
+----------------------------------------------------------------+
|																							|
|	GaMerZ File Explorer Version 1.20											|
|	Copyright (c) 2004-2008 Lester "GaMerZ" Chan							|
|																							|
|	File Written By:																	|
|	- Lester "GaMerZ" Chan															|
|	- http://lesterchan.net															|
|																							|
|	File Information:																	|
|	- Search For Files																	|
|	- search.php																		|
|																							|
+----------------------------------------------------------------+
*/


### Require Config, Setting And Function Files
require('config.php');
require('settings.php');
require('functions.php');

### Start Timer
StartTimer();

### Check Whether Search Is Enabled
if(!$can_search) {
	display_error('The Administrator Has Disabled The Searching Of Files');
}

### Variables Variables Variables
$get_sort_order = trim($_GET['order']);
$get_sort_by = trim($_GET['by']);
$search_keyword = trim(strip_tags(stripslashes($_GET['search'])));
$search_in = trim(strip_tags(stripslashes($_GET['in'])));

### Process Search
if(!empty($_GET['search'])) {
	// Variables Variables Variables
	$sort_order = '';
	$sort_order_text = '';
	$sort_order_image = '';
	$sort_by = '';
	$search_results = array();

	// Determine Sort Order
	if(empty($get_sort_order)) { $get_sort_order = $default_sort_order; }
	switch($get_sort_order) {
		case 'asc':
			$sort_order = SORT_ASC;
			$sort_order_text = 'Ascending';
			$sort_order_image = $gfe_url.'/resources/arrow_ascending.gif';
			break;
		case 'desc':
		default:
			$sort_order = SORT_DESC;
			$sort_order_text = 'Descending';
			$sort_order_image = $gfe_url.'/resources/arrow_descending.gif';
	}

	// Determine Sort By
	if(empty($get_sort_by)) { $get_sort_by = $default_sort_by; }
	switch($get_sort_by) {
		case 'name':
		case 'size':
		case 'type':
		case 'date':
			$sort_by = $get_sort_by;
			break;
		default:
			$sort_by = 'date';
	}
	// Determine Search In
	if(empty($search_in)) {
		$search_in = 'all';
	}

	// List All The files
	list_files($root_directory);

	// Check For Matches
	foreach($gmz_files as $gmz_file) {
		if($search_in != 'all') {
			if(strpos(strtolower($gmz_file['name']), strtolower($search_keyword)) !== false && strpos($gmz_file['path'], $search_in) !== false) {
				$search_results[] = $gmz_file;			
			}
		} else {
			if(strpos(strtolower($gmz_file['name']), strtolower($search_keyword)) !== false) {
				$search_results[] = $gmz_file;			
			}
		}
	}

	// We Do Not Need The File Listings Anymore
	unset($gmz_files);

	// Sort The Array
	if($sort_by == 'name') {
		$search_results = array_alphabetsort($search_results, $sort_by, $sort_order);
	} elseif($sort_by == 'type') {
		$search_results = array_alphabetsort($search_results, $sort_by, $sort_order);
	} else {
		usort($search_results, 'array_numbersort');
		if($sort_order == SORT_DESC) {
			$search_results = array_reverse($search_results);
		}
	}
} else {
	// List All Directories
	list_directories($root_directory);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title><?php echo $site_name; ?> - Search<?php if(!empty($search_keyword)) echo ' - '.$search_keyword; ?></title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="<?php echo $gfe_url; ?>/resources/favicon.ico" type="image/ico" />
	<style type="text/css" media="screen, print">
		@import url( <?php echo $gfe_url; ?>/resources/style.css );
	</style>
	<script src="<?php echo $gfe_url; ?>/resources/javascript.js" type="text/javascript"></script>
</head>
<body>

<!-- Breadcrumbs -->
<div id="Breadcrumbs"><a href="<?php echo url('home', 'dir'); ?>">Home</a> <b>&raquo;</b><?php if(!empty($search_keyword)) { echo " <a href=\"$gfe_url/search.php\">Search</a> <b>&raquo;</b> $search_keyword"; } else { echo ' Search'; } ?></div>

<!-- Search Files -->
<form id="search" method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<table border="0" cellspacing="0" cellpadding="3" style="width: 100%; border: 0px;">
	<tr>
		<td class="Header" colspan="2" style="width: 100%;" title="Search Engine">Search Engine</td>
	</tr>
	<tr>
		<td title="Search Term:">Search Term:</td>
		<td title="Search Term">
			<input type="text" name="search" class="TextField" size="30" maxlength="30" value="<?php echo $search_keyword; ?>" />
		</td>
	</tr>
	<tr>
		<td title="Search In:">Search In:</td>
		<td title="Search In">
			<select name="in" size="1">
				<option value="all">All Folders</option>
				<?php
					foreach($gmz_directories as $gmz_directory) {
						if($gmz_directory == $search_in) {
							echo "<option value=\"$gmz_directory\" selected=\"selected\">$gmz_directory</option>\n";
						} else {
							echo "<option value=\"$gmz_directory\">$gmz_directory</option>\n";
						}
					}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td title="Sort By:">Sort By:</td>
		<td title="Sort By">
			<select name="by" size="1">
				<option value="name"<?php if($sort_by == 'name') { echo ' selected="selected"'; } ?>>File Name</option>
				<option value="size"<?php if($sort_by == 'size') { echo ' selected="selected"'; } ?>>File Size</option>
				<option value="type"<?php if($sort_by == 'type') { echo ' selected="selected"'; } ?>>File Type</option>
				<option value="date"<?php if($sort_by == 'date') { echo ' selected="selected"'; } ?>>File Date</option>
			</select>
		</td>
	</tr>
	<tr>
		<td title="Sort Order:">Sort Order:</td>
		<td title="Sort Order">
			<select name="order" size="1">
				<option value="asc"<?php if($sort_order_text == 'Ascending') { echo ' selected="selected"'; } ?>>Ascending</option>
				<option value="desc"<?php if($sort_order_text == 'Descending') { echo ' selected="selected"'; } ?>>Descending</option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="text-align: center;"><input type="submit" value="Search" class="Button" /></td>
	</tr>
</table>
</form>
<?php
	### If Not Searching, Don't Display Results Page
	if(!empty($search_keyword)) {
?>
	<!-- List Search Results Files -->
	<table cellspacing="0" cellpadding="3" style="width: 100%; border: 0px;">
		<tr>
			<td class="Header" colspan="2" title="File Name">Name</td>
			<td class="Header" title="File Size">Size</td>
			<td class="Header" title="File Type">Type</td>
			<td class="Header" title="File Date">Date</td>
		</tr>
	<?php
		if(!empty($search_results)) {
			foreach($search_results as $key => $value) {
				$file_name = $value['name'];
				$file_size = format_size($value['size']);
				$file_date = date('jS F Y', $value['date']);
				$file_extension = $value['type'];
				$file_extension_icon = $extensions[$value['ext']][1];
				$total_size += $value['size'];
				if(!is_file($gfe_directory.'/resources/icons/'.$file_extension_icon)) {
					$file_extension = 'Unknown';
					$file_extension_icon = 'unknown.gif';
				}
				echo '<tr onmouseover="this.className=\'MouseOver\';" onmouseout="this.className=\'MouseOut\';">'."\n";
				echo "<td style=\"width: 1%;\" title=\"$file_name ($file_size)\"><img src=\"$gfe_url/resources/icons/$file_extension_icon\" alt=\"$file_name ($file_size)\" /></td>\n";
				echo "<td style=\"width: 50%;\" title=\"File: $file_name\"><a href=\"".url($value['path'],'file')."\">$file_name</a></td>\n";
				echo "<td style=\"width: 9%;\" title=\"Size: $file_size\">$file_size</td>\n";
				echo "<td style=\"width: 20%;\" title=\"Type: $file_extension\">$file_extension</td>\n";
				echo "<td style=\"width: 20%;\" title=\"Date: $file_date\">$file_date</td>\n";
				echo '</tr>'."\n";
			}
		} else {
			echo "<tr><td colspan=\"5\" style=\"text-align: center;\" title=\"No Files Found With The Search Term '$search_keyword'\"><b>No Files Found With The Search Term '$search_keyword'</b></td></tr>";
		}

		// File Stats Variables 
		$total_files = sizeof($search_results);
		$total_size = format_size($total_size);
		$total_files_name = 'Files';
		if($total_files <= 1) { $total_files_name = 'File'; }
	?>
	</table>

	<!-- Search Results Information -->
	<table cellspacing="0" cellpadding="3" style="width: 100%; border: 0px;">
		<tr>
			<td class="Footer" style="width: 95%;" title="<?php echo $total_files.' '.$total_files_name; ?>"><?php echo $total_files.' '.$total_files_name; ?></td>
			<td class="Footer" style="width: 5%; text-align: center;" title="Size: <?php echo $total_size; ?>"><?php echo $total_size; ?></td>
		</tr>
	</table>
<?php
	}
?>
<!-- Search Path -->
<div id="BottomBreadcrumbs"><?php echo $gfe_url.'/search.php'; ?></div>

<!-- Copyright -->
<p style="text-align: center;">
	Powered By <a href="http://lesterchan.net/">GaMerZ File Explorer Version <?php echo $gfe_version; ?></a><br />Copyright &copy; 2004-<?php echo date('Y'); ?> Lester "GaMerZ" Chan, All Rights Reserved.<br /><br />Page Generated In <?php echo StopTimer(); ?> Seconds
</p>
</body>
</html>