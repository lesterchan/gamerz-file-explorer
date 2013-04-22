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
|	- Settings																			|
|	- settings.php																		|
|																							|
+----------------------------------------------------------------+
*/


### What Files Not To Show In The List
$ignore_files = array('.htaccess', 'index.php', 'view.php', 'config.php', 'functions.php', 'search.php', 'settings.php');

### What Extentsions Not To Show In The List
// Example $ignore_ext = array('gif', 'jpg', 'txt');
$ignore_ext = array();

### What Folders Not To Show In The List
// Example $ignore_folders = array('desktop', 'test', 'test2');
$ignore_folders = array('resources', 'cgi-bin');

### File Extension To Be Parsed As Text
$text_ext = array('htm', 'html', 'php', 'txt', 'css', 'js');

### File Extension To Be Parsed As Image
$image_ext = 	array('jpg', 'jpeg', 'gif', 'png', 'bmp');

### File Extensions Description
$extensions = array(
						'ai'			=> array('Adobe Illustrator Artwork', 'adobe_ai.gif'),
						'avi'		=> array('AVI Movie', 'movie.gif'),
						'bmp'		=> array('Bitmap Image', 'image_bmp.gif'),
						'css'		=> array('Cascading Style Sheet Document', 'text.gif'),
						'doc'		=> array('Microsoft Word Document', 'ms_doc.gif'),
						'exe'		=> array('Application', 'application.gif'),
						'fla' 		=> array('Flash Document', 'macromedia_fla.gif'),
						'gif'		=> array('GIF Image', 'image_gif.gif'),
						'htm'		=> array('HTML Document', 'text_html.gif'),
						'html'		=> array('HTML Document', 'text_html.gif'),
						'ico'		=> array('Icon', 'image_ico.gif'),
						'jpg'		=> array('JPEG Image', 'image_jpg.gif'),
						'js'			=> array('JScript Script File', 'text.gif'),
						'mdb'		=> array('Microsoft Access Database', 'ms_mdb.gif'),
						'mid'		=> array('MIDI Music', 'sound.gif'),
						'mov'		=> array('QuickTime Video Clip','movie_mov.gif'),
						'mp3'		=> array('MPEG Audio Layer 3','sound.gif'),
						'mpeg'	=> array('MPEG Movie','movie.gif'),
						'mpg'		=> array('MPEG Movie','movie.gif'),
						'msi'		=> array('Windows Installer Package','ms_msi.gif'),
						'pdf'		=> array('Adobe Acrobat Document','adobe_pdf.gif'),
						'php'		=> array('PHP File','text.gif'),
						'png'		=> array('PNG Image','image_png.gif'),
						'ppt'		=> array('Microsoft PowerPoint Presentation','ms_ppt.gif'),
						'psd' 		=> array('Adobe Photoshop Image', 'adobe_psd.gif'),
						'swf' 		=> array('Flash Movie', 'macromedia_swf.gif'),
						'tif'		=> array('Tagged Image Format File','image_tif.gif'),
						'txt'		=> array('Text Document','text.gif'),
						'ra'			=> array('Real Media Audio', 'sound_ra.gif'),
						'rar'		=> array('RAR Compressed Archive', 'zip.gif'),
						'rm' 		=> array('Real Media Video', 'movie_rm.gif'),
						'w3x'		=> array('Warcraft III Expansion Scenario File', 'warcraft_w3x.gif'),
						'wav'		=> array('Waveform Sound', 'sound.gif'),
						'wma'		=> array('Windows Media Audio File', 'sound_wma.gif'),
						'wmv'		=> array('Windows Media Video File', 'movie_wmv.gif'),
						'xls'		=> array('Microsoft Excel Worksheet', 'ms_xls.gif'),
						'zip'		=> array('Zip Compressed Archive', 'zip.gif'),
						);

### GaMerZ File Explorer Version (Please Do Not Edit This)
$gfe_version = '1.20';
?>