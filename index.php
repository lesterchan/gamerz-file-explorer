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
|	- Main Page																			|
|	- index.php																			|
|																							|
+----------------------------------------------------------------+
*/


### Require Config, Setting And Function Files
require('config.php');
require('settings.php');
require('functions.php');

### Start Timer
StartTimer();

### Get And Check Current Directory Path
$url_path = urldecode(trim(stripslashes($_GET['dir'])));
if(strpos($url_path, '../') !== false || strpos($url_path, './') !== false || strpos($url_path, '//') !== false) {
	display_error('Invalid Directory');
}

### Check Whether Directory Is In The Ignore Folders
if(in_array($url_path, $ignore_folders)) {
	display_error('Invalid Directory');
}

### Variables Variables Variables
$get_sort_order = trim($_GET['order']);
$get_sort_by = trim($_GET['by']);
$full_directory_path = '';
$directories_before_current = '';
$directories_before_current_path = '';
$current_directory_name = '';
$current_directory_path = '';
$sort_order = '';
$sort_order_text = '';
$sort_by = '';
$gmz_files = array();
$gmz_directories = array();
$directory_names = explode('/', $url_path);

### Current Directory Name
$current_directory_name = $directory_names[(sizeof($directory_names)-1)];

### Unset Current Directory Name
unset($directory_names[(sizeof($directory_names)-1)]);

### Directory Path Up To Current Directory
if(!empty($directory_names)) {
	foreach($directory_names as $directory_name) {
		$directories_before_current .= $directory_name.'/';
	}
	$directories_before_current = substr($directories_before_current, 0, -1);
}

### If No Directory Is Specified
if(empty($url_path)) {
	$full_directory_path = $root_directory;
} else {
	$full_directory_path = $root_directory.'/'.$url_path;
}

### If Current Directory Is Not Empty, Add A Trailing Slash
if(!empty($current_directory_name)) {
	$current_directory_path = $current_directory_name.'/';
}

### If There Is Directory Before The Current Directory, Add A Trailing Slash
if(!empty($directories_before_current)) {
	$directories_before_current_path = $directories_before_current.'/';
}

### Full URL
$full_url = $root_url.'/'.$directories_before_current_path.$current_directory_path;

### Determine Sort Order
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

### Determine Sort By
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

### Execute The Function To List Files/Directories, It Will Return An Array
list_dir($full_directory_path);

### Sort The Array
if($sort_by == 'name') {
	$gmz_files = array_alphabetsort($gmz_files, $sort_by, $sort_order);
	$gmz_directories = array_alphabetsort($gmz_directories, $sort_by, $sort_order);
} elseif($sort_by == 'type') {
	$gmz_files = array_alphabetsort($gmz_files, $sort_by, $sort_order);
} else {
	usort($gmz_files, 'array_numbersort');
	usort($gmz_directories, 'array_numbersort');
	if($sort_order == SORT_DESC) {
		$gmz_files = array_reverse($gmz_files);
		$gmz_directories = array_reverse($gmz_directories);
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title><?php echo $site_name; if(!empty($current_directory_name)) { echo ' - Viewing Directory - '.$current_directory_name; }?></title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="<?php echo $gfe_url; ?>/resources/favicon.ico" type="image/ico" />
	<style type="text/css" media="screen, print">
		@import url( <?php echo $gfe_url; ?>/resources/style.css );
	</style>
	<script src="<?php echo $gfe_url; ?>/resources/javascript.js" type="text/javascript"></script>
</head>
<body>

<!-- Breadcrumbs -->
<div id="Breadcrumbs"><?php echo breadcrumbs(); ?></div>

<!-- List Directories/Files -->
<table cellspacing="0" cellpadding="3" style="width: 100%; border: 0px;">
	<tr>
		<td class="Header" colspan="2" onclick="parent.location.href='<?php echo create_sort_url('name'); ?>';" onmouseover="this.style.cursor = 'pointer';" title="Sort By Name">Name&nbsp;<?php echo create_sort_image('name'); ?></td>
		<td class="Header" onclick="parent.location.href='<?php echo create_sort_url('size'); ?>';" onmouseover="this.style.cursor = 'pointer';" title="Sort By Size">Size&nbsp;<?php echo create_sort_image('size'); ?></td>
		<td class="Header" onclick="parent.location.href='<?php echo create_sort_url('type'); ?>';" onmouseover="this.style.cursor = 'pointer';" title="Sort By Type">Type&nbsp;<?php echo create_sort_image('type'); ?></td>
		<td class="Header" onclick="parent.location.href='<?php echo create_sort_url('date'); ?>';" onmouseover="this.style.cursor = 'pointer';" title="Sort By Date">Date&nbsp;<?php echo create_sort_image('date'); ?></td>
	</tr>
<?php
	// If It Is Down One Level, Provide "Up One Level"
	if(!empty($url_path)) {
		if(!empty($directory_names)) {
			$parent_directory = $directories_before_current;
		} else {
			$parent_directory = 'home';
		}
		echo '<tr onmouseover="this.className=\'MouseOver\';" onmouseout="this.className=\'MouseOut\';">'."\n";
		echo "<td style=\"width: 1%;\" title=\"Up One Level\"><img src=\"$gfe_url/resources/icons/back.gif\" alt=\"Parent Directory\" /></td>";
		echo '<td style="width: 50%;" title="Parent Directory"><a href="'.url($parent_directory, 'dir').'">Parent Directory</a></td>'."\n";
		echo '<td style="width: 9%;">&nbsp;</td>'."\n";
		echo '<td style="width: 20%;">&nbsp;</td>'."\n";
		echo '<td style="width: 20%;">&nbsp;</td>'."\n";	
		echo '</tr>'."\n";
	}
	// If There Is Directory
	if(!empty($gmz_directories)) {
		foreach($gmz_directories as $key => $value) {
			$directory_name = $value['name'];
			$directory_size = format_size($value['size']);
			$directory_date = date('jS F Y', $value['date']);
			echo '<tr onmouseover="this.className=\'MouseOver\';" onmouseout="this.className=\'MouseOut\';">'."\n";
			echo "<td style=\"width: 1%;\" title=\"$directory_name ($directory_size)\"><img src=\"$gfe_url/resources/icons/folder.gif\" alt=\"$directory_name ($directory_size)\" /></td>\n";
			echo "<td style=\"width: 50%\" title=\"Folder: $directory_name\"><a href=\"".url($directories_before_current_path.$current_directory_path.$directory_name,'dir')."\">$directory_name</a></td>\n";
			echo "<td style=\"width: 9%;\" title=\"Size: $directory_size\">$directory_size</td>\n";
			echo '<td style="width: 20%;" title="Type: File Folder">File Folder</td>'."\n";
			echo "<td style=\"width: 20%;\" title=\"Date: $directory_date\">$directory_date</td>\n";	
			echo '</tr>'."\n";
		}
	}
	// If There Is Files
	if(!empty($gmz_files)) {
		foreach($gmz_files as $key => $value) {
			$file_name = $value['name'];
			$file_size = format_size($value['size']);
			$file_date = date('jS F Y', $value['date']);
			$file_extension = $value['type'];
			$file_extension_icon = $extensions[$value['ext']][1];
			if(!is_file($gfe_directory.'/resources/icons/'.$file_extension_icon)) {
				$file_extension = 'Unknown';
				$file_extension_icon = 'unknown.gif';
			}
			echo '<tr onmouseover="this.className=\'MouseOver\';" onmouseout="this.className=\'MouseOut\';">'."\n";
			echo "<td style=\"width: 1%;\" title=\"$file_name ($file_size)\"><img src=\"$gfe_url/resources/icons/$file_extension_icon\" alt=\"$file_name ($file_size)\" /></td>\n";
			echo "<td style=\"width: 50%;\" title=\"File: $file_name\"><a href=\"".url($directories_before_current_path.$current_directory_path.$file_name,'file')."\">$file_name</a></td>\n";
			echo "<td style=\"width: 9%;\" title=\"Size: $file_size\">$file_size</td>\n";
			echo "<td style=\"width: 20%;\" title=\"Type: $file_extension\">$file_extension</td>\n";
			echo "<td style=\"width: 20%;\" title=\"Date: $file_date\">$file_date</td>\n";
			echo '</tr>'."\n";
		}
	} else {
		echo '<tr><td colspan="5" style="text-align: center;" title="No Files Found"><b>No Files Found</b></td></tr>';
	}
	// Folder And File Stats Variables 
	$total_folders = sizeof($gmz_directories);
	$total_files = sizeof($gmz_files);
	$total_size = format_size(dir_size($full_directory_path));
	$total_folders_name = 'Folders';
	$total_files_name = 'Files';
	if($total_folders <= 1) { $total_folders_name = 'Folder'; }
	if($total_files <= 1) { $total_files_name = 'File'; }
	$total_folders_files = $total_folders.' '.$total_folders_name.', '.$total_files.' '.$total_files_name;
?>
</table>

<!-- Directory Information -->
<table cellspacing="0" cellpadding="3"  style="width: 100%; border: 0px;">
	<tr>
		<td class="Footer" style="width: 95%;" title="<?php echo $total_folders_files; ?>"><?php echo $total_folders_files; ?></td>
		<td class="Footer" style="width: 5%; text-align: center;" title="Size: <?php echo $total_size; ?>"><?php echo $total_size; ?></td>
	</tr>
</table>

<!-- Current File Directory Path -->
<div id="BottomBreadcrumbs"><?php echo $full_url; ?></div>

<?php 
	if($can_search) {
?>
<!-- Search Engine -->
<form id="search" method="get" action="<?php echo $gfe_url; ?>/search.php">
<table cellspacing="0" cellpadding="3"  style="width: 100%; border: 0px;">
	<tr>
		<td title="Search For Files">		
			<br />Search For Files:&nbsp;<input type="text" class="TextField" size="30" maxlength="30" name="search" />&nbsp;&nbsp;
			<input type="submit" value="Search" class="Button" /><br />
			<b>&raquo;</b>&nbsp;<a href="<?php echo $gfe_url; ?>/search.php">Advanced Search</a>
		</td>
	</tr>
</table>
</form>
<?php
	}
?>

<!-- Copyright -->
<p style="text-align: center;">
	Powered By <a href="http://lesterchan.net/">GaMerZ File Explorer Version <?php echo $gfe_version; ?></a><br />Copyright &copy; 2004-<?php echo date('Y'); ?> Lester "GaMerZ" Chan, All Rights Reserved.<br /><br />Page Generated In <?php echo StopTimer(); ?> Seconds
</p>
</body>
</html>