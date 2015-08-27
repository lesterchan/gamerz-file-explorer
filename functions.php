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
|	- Functions Needed																|
|	- function.php																		|
|																							|
+----------------------------------------------------------------+
*/


### Function: Start Timer
function StartTimer() {
	global $timestart;
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$timestart = $mtime;
	return true;
}

### Function: Stop Timer
function StopTimer($precision=5) {
	global $timestart;
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$timeend = $mtime;
	$timetotal = $timeend-$timestart;
    $scripttime = number_format($timetotal,$precision);
	return $scripttime;
}

### Function: Format Size
function format_size($rawSize) {
	if($rawSize / 1073741824 > 1) {
		return round($rawSize/1073741824, 1).'GB';
	} elseif ($rawSize / 1048576 > 1) {
		return round($rawSize/1048576, 1).'MB';
	} elseif ($rawSize / 1024 > 1) {
		return round($rawSize/1024, 1).'KB';
	} else {
		return round($rawSize, 1).'b';
	}
}


### Function: List All Directory
function list_directories($path) {
	global $root_directory, $gmz_directories, $ignore_folders;
    if ($handle = @opendir($path)) {     
        while (false !== ($filename = readdir($handle))) {  
            if ($filename != '.' && $filename != '..') {
				$file_path = substr($path.'/'.$filename, strlen($root_directory)+1, strlen($path.'/'.$filename));
				$file_folder = substr($file_path, 0, -(strlen($filename)+1));
				if(is_dir($path.'/'.$filename)) {
					if(!in_array($file_path, $ignore_folders)) {
						$gmz_directories[] = $file_path;
					}
					list_directories($path.'/'.$filename);
				}
            } 
        } 
        closedir($handle);  
    }  else {
		display_error('Invalid Directory');
	}
} 

### Function: List All Files
function list_files($path) {
	global $root_directory, $gmz_files, $gmz_directories, $extensions, $ignore_files, $ignore_ext, $ignore_folders;
    if ($handle = @opendir($path)) {     
        while (false !== ($filename = readdir($handle))) {  
            if ($filename != '.' && $filename != '..') {
				$file_path = substr($path.'/'.$filename, strlen($root_directory)+1, strlen($path.'/'.$filename));
				$file_folder = substr($file_path, 0, -(strlen($filename)+1));
				if(is_dir($path.'/'.$filename)) {
					if(!in_array($file_path, $ignore_folders)) {
						$gmz_directories[] = $file_path;
					}
					list_files($path.'/'.$filename);
				} else {
					if (is_file($path.'/'.$filename)) {
						$file_ext = explode('.', $filename);
						$file_ext = $file_ext[sizeof($file_ext)-1];
						$file_ext = strtolower($file_ext);
						if(!in_array($file_ext, $ignore_ext) && !in_array($file_path, $ignore_files) && !in_array($file_folder, $ignore_folders)) {
							$gmz_files[] = array('name' => $filename, 'ext' => $file_ext, 'path' => $file_path, 'type' => $extensions[$file_ext][0], 'size' => sprintf("%u", filesize($path.'/'.$filename)), 'date' => filemtime($path.'/'.$filename));
						}
					} 
				}
            } 
        } 
        closedir($handle);  
    }  else {
		display_error('Invalid Directory');
	}
} 

### Function: List Directory Files
function list_dir($path) {
	global $gmz_files, $gmz_directories, $extensions, $ignore_files, $ignore_ext, $ignore_folders, $directories_before_current_path, $current_directory_path;
    if ($handle = @opendir($path)) {     
        while (false !== ($filename = readdir($handle))) {  
            if ($filename != '.' && $filename != '..') {
                if (is_file($path.'/'.$filename) && !in_array($directories_before_current_path.$current_directory_path.$filename, $ignore_files)) {
					$file_ext = explode('.', $filename);
					$file_ext = $file_ext[sizeof($file_ext)-1];
					$file_ext = strtolower($file_ext);
					if(!in_array($file_ext, $ignore_ext)) {
						$gmz_files[] = array('name' => $filename, 'ext' => $file_ext, 'type' => $extensions[$file_ext][0], 'size' => sprintf("%u", filesize($path.'/'.$filename)), 'date' => filemtime($path.'/'.$filename));
					}
                } 
                if (is_dir($path.'/'.$filename) && !in_array($directories_before_current_path.$current_directory_path.$filename, $ignore_folders)) {
					$gmz_directories[] = array('name' => $filename, 'size' => dir_size($path.'/'.$filename), 'date' => filemtime($path.'/'.$filename));
                } 
            } 
        } 
        closedir($handle);  
    }  else {
		display_error('Invalid Directory');
	}
} 

### Function: Find Directory Size
function dir_size($dir) { 
    $totalsize = 0; 
    if ($dirstream = @opendir($dir))  { 
        while (false !== ($filename = readdir($dirstream))) { 
            if ($filename != '.' && $filename != '..') { 
                if (is_file($dir.'/'.$filename)) { 
                    $totalsize += sprintf("%u", filesize($dir.'/'.$filename)); 
                } 
                if (is_dir($dir.'/'.$filename)) { 
                    $totalsize += dir_size($dir.'/'.$filename); 
                } 
            } 
        } 
        closedir($dirstream); 
    } 
    return $totalsize; 
}

### Function: Sort Array By Alphabets
function array_alphabetsort() {
   $arguments = func_get_args();
   $arrays    = $arguments[0];  
   for ($c = (count($arguments)-1); $c > 0; $c--) {
       if (in_array($arguments[$c], array(SORT_ASC , SORT_DESC))) {
           continue;
       }
       $compare = create_function('$a,$b','return strcasecmp($a["'.$arguments[$c].'"], $b["'.$arguments[$c].'"]);');
       usort($arrays, $compare);
       if ($arguments[$c+1] == SORT_DESC) {
           $arrays = array_reverse($arrays);
       }
   }
   return $arrays;
}

### Function: Sort Array By Numbers
function array_numbersort($a, $b) {
	global $sort_by;
	if ($a[$sort_by] == $b[$sort_by]) {
		return 0;
	}
	return ($a[$sort_by] < $b[$sort_by]) ? -1 : 1;
}

### Function: Check Key In Multiple Arrays
function in_multi_array($needle, $haystack) {
	$in_multi_array = false;
	if(in_array($needle, $haystack)) {
		$in_multi_array = true;
	} else {
		foreach ($haystack as $key => $val) {
			if(is_array($val)) {
				if(in_multi_array($needle, $val)) {
					$in_multi_array = true;
					break;
				}
			}
		}
	}
	return $in_multi_array;
}

### Function: Form Sorting URL
function url($url, $mode) {
	global $gfe_url, $nice_url, $root_filename, $sort_by, $sort_order;
	$temp_url = '';
	$temp_url_nice = '';
	$GET_sortby = ! empty( $_GET['by'] ) ? trim( $_GET['by'] ) : '';
	$GET_sortorder = ! empty( $_GET['order'] ) ? trim( $_GET['order'] ) : '';
	$url = urldecode($url);
	$url = urlencode($url);
	$url = str_replace('%2F', '/', $url);
	switch($mode) {
		case 'dir':
			if($url == 'home') {
				$temp_url = $gfe_url.'/'.$root_filename;
				$temp_url_nice = $gfe_url.'/';
			} else {
				$temp_url = "$gfe_url/$root_filename?dir=$url";
				$temp_url_nice = "$gfe_url/browse/$url/";
			}
			if(!empty($GET_sortby)) {
				if(strpos($temp_url, '?') === false) {
					$temp_url .= "?by=$sort_by&amp;order=$GET_sortorder";
				} else {
					$temp_url .= "&amp;by=$sort_by&amp;order=$GET_sortorder";
				}
				$temp_url_nice .= "sortby/$sort_by/sortorder/$GET_sortorder/";
			}
			break;
		case 'file':
			$temp_url = "$gfe_url/view.php?file=$url";
			$temp_url_nice = "$gfe_url/viewing/$url/";
			break;
		case 'download';
			$temp_url = "$gfe_url/view.php?file=$url&amp;dl=1";
			$temp_url_nice = "$gfe_url/download/$url/";
			break;
	}
	if($nice_url) {
		return $temp_url_nice;
	} else {
		return $temp_url;
	}
}

### Function: Create Sorting URL
function create_sort_url($sortby) {
	global $gfe_url, $nice_url, $directories_before_current_path, $current_directory_name, $sort_order;
	$temp_url = '';
	$temp_url_nice = '';
	$sortorder = '';
	$directories_before_current_path = urldecode($directories_before_current_path);
	$directories_before_current_path = urlencode($directories_before_current_path);
	$directories_before_current_path = str_replace('%2F', '/', $directories_before_current_path);
	$current_directory_name = urldecode($current_directory_name);
	$current_directory_name = urlencode($current_directory_name);
	$current_directory_name = str_replace('%2F', '/', $current_directory_name);
	if($sort_order == SORT_DESC) {
		$sortorder = 'asc';
	} else {
		$sortorder = 'desc';
	}
	if(empty($current_directory_name)) {
		$temp_url = "?by=$sortby&amp;order=$sortorder";
		$temp_url_nice = "$gfe_url/sortby/$sortby/sortorder/$sortorder/";
	} else {
		$temp_url = "?dir=$directories_before_current_path$current_directory_name&amp;by=$sortby&amp;order=$sortorder";
		$temp_url_nice = "$gfe_url/browse/$directories_before_current_path$current_directory_name/sortby/$sortby/sortorder/$sortorder/";
	}
	if($nice_url) {
		return $temp_url_nice;
	} else {
		return $temp_url;
	}
}

### Function: Create Sorting Image
function create_sort_image($sortby) {
	global $sort_order_image, $sort_order_text;
	if( ! empty( $_GET['by'] ) && trim( $_GET['by'] ) === $sortby ) {
		return "<img src=\"$sort_order_image\" alt=\"Sorted By ".ucfirst($sortby)." In $sort_order_text Order\" />";
	}
}


### Function: Show Source Of Text File
function display_text($file) {
	global $lines;
	ob_start();
	show_source($file);
	$filecontents = ob_get_contents();
	ob_end_clean();
	$filecontents = str_replace('<code>', '', $filecontents);
	$filecontents = str_replace('</code>', '', $filecontents);
	$filecontents = str_replace("\n", '', $filecontents);
	$filecontents = explode('<br />', $filecontents);
	$lines = count($filecontents);
	$iLen = strlen($lines);
	for($i = 0; $i < $lines; $i++) {
		$sGap = ($iLen - strlen($i+1));
		$filecontents[$i] = '<span style="color: #999999">'.str_repeat(' ', $sGap).($i+1).' </span>'.$filecontents[$i]."<br />\n";
	}
	$filecontents = implode('', $filecontents);
	return "\n$filecontents\n";
}

### Function: Breadcrumbs
function breadcrumbs($delim = '<b>&raquo;</b>') {
	global $root_filename, $directory_names, $current_directory_name;
	$temp_breadcrumb_path = '';
	$temp_breadcrumb_url = '';
	$temp_breadcrumb = '<a href="'.url('home', 'dir').'">Home</a> ';
	foreach($directory_names as $directory_name) {
		$temp_breadcrumb_path .= $directory_name.'/';
		$temp_breadcrumb_url = substr($temp_breadcrumb_path, 0, -1);
		$temp_breadcrumb .= $delim.' <a href="'.url($temp_breadcrumb_url,'dir').'">'.$directory_name.'</a> ';
	}
	if(!empty($current_directory_name)) {
		$temp_breadcrumb .= " $delim <b>".$current_directory_name.'</b>';
	}
	return $temp_breadcrumb;
}

### Function: Breadcrumbs For view.php
function breadcrumbs_view($delim = '<b>&raquo;</b>') {
	global $file_name, $file, $root_filename;
	$temp_breadcrumb_path = '';
	$temp_breadcrumb_url = '';
	$directory_names = explode('/', $file);
	unset($directory_names[sizeof($directory_names)-1]);
	foreach($directory_names as $directory_name) {
		$temp_breadcrumb_path .= $directory_name.'/';
		$temp_breadcrumb_url = substr($temp_breadcrumb_path, 0, -1);
		$temp_breadcrumb .= $delim.' <a href="'.url($temp_breadcrumb_url,'dir').'">'.$directory_name.'</a> ';
	}
	return "<a href=\"".url('home', 'dir')."\">Home</a> $temp_breadcrumb $delim <b>$file_name</b>";
}

### Function: Display Error Message
function display_error($msg) {
	global $site_name, $gfe_url, $root_url;
	echo '<html>'."\n";
	echo '<head>'."\n";
	echo "<title>$site_name - Error - $msg</title>\n";
	echo '<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />'."\n";
	echo "<link rel=\"shortcut icon\" href=\"$gfe_url/resources/favicon.ico\" type=\"image/ico\">\n";
	echo '<style type="text/css" media="screen">'."\n";
	echo "@import url( $gfe_url/resources/style.css );\n";
	echo '</style>'."\n";
	echo '</head>'."\n";
	echo '<body>'."\n";
	echo "<p align=\"center\"><b>$msg</b></p>\n";
	echo "<p align=\"center\"><a href=\"$root_url\">Go To $site_name</a> | <a href=\"#\" onclick=\"javascript: history.go(-1)\">Go To Previous Page</a></p>\n";
	echo '</body>'."\n";
	echo '</html>'."\n";
	exit();
}
?>