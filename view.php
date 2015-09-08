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
|	- View/Download Files															|
|	- view.php																			|
|																							|
+----------------------------------------------------------------+
*/


### Require Config, Setting And Function Files
require('config.php');
require('settings.php');
require('functions.php');

### Start Timer
StartTimer();

### Get And Check File Path
$file = ! empty( $_GET['file'] ) ? urldecode( stripslashes( trim( $_GET['file'] ) ) ) : '';
if(strpos($file, '../') !== false || strpos($file, './') !== false || strpos($file, '//') !== false) {
	display_error('Invalid Directory');
}
$temp = explode('/', $file);
$file_name = $temp[(sizeof($temp)-1)];

### Get File Extension
$file_ext = explode('.', $file_name);
$file_ext = $file_ext[sizeof($file_ext)-1];
$file_ext = strtolower($file_ext);

### Check Whether File Is In The Ignore Files
if(in_array($file, $ignore_files)) {
	display_error('Invalid Directory');
}

### Check Whether Extension Is In The Ignore Extensions
if(in_array($file_ext, $ignore_ext)) {
	display_error('Invalid Extension');
}

### Check Whether File Exists
if(!is_file($root_directory.'/'.$file)) {
	display_error('File Does Not Exist');
}

### If User Wants To Download Text Or Image
if( ! empty( $_GET['dl'] ) && intval ($_GET['dl'] ) === 1 ) {
	$download_filename = $file_name;
	$download_filename = preg_replace('/\s+/', '_', $download_filename );
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");
	header("Content-Disposition: attachment; filename=".basename($root_directory.'/'.$download_filename).";");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".filesize($root_directory.'/'.$file));
	@readfile($root_directory.'/'.$file);
	exit();
}

### Display Text
if(in_array($file_ext, $text_ext)) {
	// Get Number Of Lines In Text File
	$lines = 0;
	$lines_text = 'Lines';
	$text_size = format_size(filesize($root_directory.'/'.$file));
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title><?php echo $site_name; ?> - Viewing Text File - <?php echo $file_name; ?></title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="<?php echo $gfe_url; ?>/resources/favicon.ico" type="image/ico" />
		<style type="text/css" media="screen, print">
			@import url( <?php echo $gfe_url; ?>/resources/style.css );
		</style>
		<script src="<?php echo $gfe_url; ?>/resources/javascript.js" type="text/javascript"></script>
	</head>
	<body>

	<!-- Breadcrumbs -->
	<div id="Breadcrumbs"><?php echo breadcrumbs_view(); ?></div>

	<!-- Text -->
	<table cellspacing="0" cellpadding="3" style="width: 100%; border: 0px;">
		<tr>
			<td class="Header" style="text-align: center;" title="<?php echo $file_name; ?>"><?php echo $file_name; ?></td>
		</tr>
		<tr>
			<td>
				<?php if($file_ext == 'htm' || $file_ext == 'html'): ?>
					<!-- Links To Toggle Between HTML Source/View -->
					<div style="text-align: center;"><a href="#" onclick="show_htmlcode(); return false;" title="Show HTML Code">Show HTML Code</a> | <a href="#" onclick="show_htmlview(); return false;" title="Show HTML View">Show HTML View</a></div>
				<?php endif; ?>

				<!-- Display Source -->
				<div id="DisplaySource"><?php echo display_text($root_directory.'/'.$file); ?></div>

				<?php if($file_ext == 'htm' || $file_ext == 'html'): ?>
					<!-- Display HTML View -->
					<object id="DisplayHTML" data="<?php echo $root_url.'/'.$file; ?>" style="display: none; width: 100%; height: <?php echo ($lines*10); ?>px; border: 0px;" type="text/html"></object>
				<?php endif; ?>
			</td>
		</tr>	
	</table>

	<!-- Text Statistics -->
	<?php if($lines <= 1) { $lines_text = 'Line'; } ?>
	<table cellspacing="0" cellpadding="3" style="width: 100%; border: 0px;">
		<tr>
			<td class="Footer" style="width: 20%;" title="<?php echo $lines.' '.$lines_text; ?>"><?php echo $lines.' '.$lines_text; ?></td>
			<td class="Footer" style="width: 60%; text-align: center;" title="Download '<?php echo $file_name; ?>'"><b><a href="<?php echo url($file,'download'); ?>">Download '<?php echo $file_name; ?>'</a></b></td>
			<td class="Footer" style="width: 20%; text-align: center;" title="Size: <?php echo $text_size; ?>"><?php echo $text_size; ?></td>
		</tr>
	</table>

	<!-- Current File Directory Path -->
	<div id="BottomBreadcrumbs"><?php echo $root_url.'/'.$file; ?></div>

	<?php 
		if($can_search) {
	?>
	<!-- Search Engine -->
	<form id="search" method="get" action="<?php echo $gfe_url; ?>/search.php">
	<table cellspacing="0" cellpadding="3" style="width: 100%; border: 0px;">
		<tr>
			<td title="Search For Files">		
				<br />Search For Files:&nbsp;<input type="text" class="TextField" size="30" maxlength="30" name="search" />&nbsp;&nbsp;
				<input type="submit" value="Search" class="Button" /><br />
				<b>&raquo;</b>&nbsp;<a href="<?php echo $gfe_url; ?>/search.php">Advance Search</a>
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
<?php
### Dispay Image
} elseif(in_array($file_ext, $image_ext)) {
	$temp_getimagesize = getimagesize($root_directory.'/'.$file);
	if(!$temp_getimagesize) {
		display_error('File Is Not A Valid Image');
	}
	list($image_width, $image_height, $image_type, $image_attr) = $temp_getimagesize;
	$image_size = format_size(filesize($root_directory.'/'.$file));
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title><?php echo $site_name; ?> - Viewing Image - <?php echo $file_name; ?></title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="<?php echo $gfe_url; ?>/resources/favicon.ico" type="image/ico" />
		<style type="text/css" media="screen, print">
			@import url( <?php echo $gfe_url; ?>/resources/style.css );
		</style>
		<script src="<?php echo $gfe_url; ?>/resources/javascript.js" type="text/javascript"></script>
	</head>
	<body>

	<!-- Breadcrumbs -->
	<div id="Breadcrumbs"><?php echo breadcrumbs_view(); ?></div>

	<!-- Image -->
	<table cellspacing="0" cellpadding="3" style="width: 100%; border: 0px;">
		<tr>
			<td class="Header" style="text-align: center;" title="<?php echo $file_name; ?>"><?php echo $file_name; ?></td>
		</tr>
		<tr>
			<td style="text-align: center;"><img src="<?php echo $root_url.'/'.$file; ?>" <?php echo $image_attr; ?> alt="GaMerZ.File.Viewer - Viewing Image - <?php echo $file_name; ?>" /></td>
		</tr>	
	</table>	

	<!-- Image Statistics -->
	<table cellspacing="0" cellpadding="3" style="width: 100%; border: 0px;">
		<tr>
			<td class="Footer" style="width: 20%; text-align: center;" title="Width: <?php echo $image_width; ?>px, Height:<?php echo $image_height; ?>px">Width: <?php echo $image_width; ?>px, Height:<?php echo $image_height; ?>px</td>
			<td class="Footer" style="width: 60%; text-align: center;" title="Download '<?php echo $file_name; ?>'"><b><a href="<?php echo url($file,'download'); ?>">Download '<?php echo $file_name; ?>'</a></b></td>
			<td class="Footer" style="width: 20%; text-align: center;" title="Size: <?php echo $image_size;?>"><?php echo $image_size;?></td>
		</tr>
	</table>

	<!-- Current File Directory Path -->
	<div id="BottomBreadcrumbs"><?php echo $root_url.'/'.$file; ?></div>

	<?php 
		if($can_search) {
	?>
	<!-- Search Engine -->
	<form id="search" method="get" action="<?php echo $gfe_url; ?>/search.php">
	<table cellspacing="0" cellpadding="3" style="width: 100%; border: 0px;">
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
<?php
### Display Download
} else {
	$download_filename = $file_name;
	$download_filename = preg_replace("/\s/e" , "_" , $download_filename);
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");
	header("Content-Disposition: attachment; filename=".basename($root_directory.'/'.$download_filename).";");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".filesize($root_directory.'/'.$file));
	@readfile($root_directory.'/'.$file);
	exit();
}
?>