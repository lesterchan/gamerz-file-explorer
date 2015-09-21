<?php
### Function: Start Timer
function start_timer() {
    global $timestart;
    $mtime = microtime();
    $mtime = explode( ' ',$mtime );
    $mtime = $mtime[1] + $mtime[0];
    $timestart = $mtime;
    return true;
}

### Function: Stop Timer
function stop_timer( $precision = 5 ) {
    global $timestart;
    $mtime = microtime();
    $mtime = explode( ' ',$mtime );
    $mtime = $mtime[1] + $mtime[0];
    $timeend = $mtime;
    $timetotal = $timeend - $timestart;
    $scripttime = number_format( $timetotal, $precision );
    return $scripttime;
}

### Function: Format Size
function format_size( $size ) {
    if( ( $size / 1073741824 ) > 1 ) {
        return round( $size / 1073741824, 1 ) . 'GB';
    } elseif( ( $size / 1048576 ) > 1 ) {
        return round( $size / 1048576, 1 ) . 'MB';
    } elseif( ( $size / 1024 ) > 1) {
        return round( $size / 1024, 1 ) . 'KB';
    } else {
        return round( $size, 1 ) . 'b';
    }
}


### Function: List All Directory
function list_directories( $path ) {
    global $gmz_directories, $ignore_folders;
    if( $handle = @opendir( $path ) ) {
        while( false !== ( $filename = readdir( $handle ) ) ) {
            if ( ! in_array( $filename, [ '.', '..', '.git', '.svn' ] ) ) {
                $file_path = substr( $path . '/' . $filename, strlen( GFE_ROOT_DIR ) + 1, strlen( $path . '/' . $filename ) );
                if( is_dir( $path . '/' . $filename ) ) {
                    if( ! in_array( $file_path, $ignore_folders ) ) {
                        $gmz_directories[] = $file_path;
                    }
                    list_directories( $path . '/' . $filename );
                }
            }
        }
        closedir( $handle );
    }  else {
        display_error( 'Invalid Directory' );
    }
}

### Function: List All Files
function list_files( $path ) {
    global $gmz_files, $gmz_directories, $extensions, $ignore_files, $ignore_ext, $ignore_folders;
    if( $handle = @opendir( $path ) ) {
        while( false !== ( $filename = readdir( $handle ) ) ) {
           if( ! in_array( $filename, [ '.', '..', '.git', '.svn' ] ) ) {
                $file_path = substr( $path . '/' . $filename, strlen( GFE_ROOT_DIR ) + 1, strlen( $path . '/'. $filename ) );
                $file_folder = substr( $file_path, 0, - ( strlen( $filename ) + 1 ) );
                if( is_dir( $path . '/' . $filename ) ) {
                    if( ! in_array( $file_path, $ignore_folders ) ) {
                        $gmz_directories[] = $file_path;
                    }
                    list_files( $path . '/' . $filename );
                } else {
                    if( is_file( $path . '/' . $filename ) ) {
                        $file_ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
                        if( ! in_array( $file_ext, $ignore_ext ) && ! in_array( $file_path, $ignore_files ) && ! in_array( $file_folder, $ignore_folders ) ) {
                            if ( ! empty ( $extensions[$file_ext][0] ) ) {
                                $gmz_files[] = [ 'name' => $filename, 'ext' => $file_ext, 'path' => $file_path, 'type' => ( ! empty( $extensions[$file_ext][0] ) ? $extensions[$file_ext][0] : 'Unknown' ), 'size' => filesize( $path . '/' . $filename ), 'date' => filemtime( $path . '/' . $filename ) ];
                            }
                        }
                    }
                }
            }
        }
        closedir( $handle );
    }  else {
        display_error( 'Invalid Directory' );
    }
}

### Function: List Directory Files
function list_directories_files( $path ) {
    global $gmz_files, $gmz_directories, $extensions, $ignore_files, $ignore_ext, $ignore_folders, $directories_before_current_path, $current_directory_path;
    if( $handle = @opendir( $path ) ) {
        while( false !== ( $filename = readdir( $handle ) ) ) {
            if( ! in_array( $filename, [ '.', '..', '.git', '.svn' ] ) ) {
                if( is_file( $path . '/' . $filename ) && ! in_array( $directories_before_current_path . $current_directory_path . $filename, $ignore_files ) ) {
                    $file_ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
                    if( ! in_array( $file_ext, $ignore_ext ) ) {
                        $gmz_files[] = [ 'name' => $filename, 'ext' => $file_ext, 'type' => ( ! empty( $extensions[$file_ext][0] ) ? $extensions[$file_ext][0] : 'Unknown' ), 'size' => filesize( $path . '/' . $filename ), 'date' => filemtime( $path . '/' . $filename ) ];
                    }
                }
                if( is_dir( $path . '/' . $filename ) && ! in_array( $directories_before_current_path . $current_directory_path . $filename, $ignore_folders ) ) {
                    $gmz_directories[] = [ 'name' => $filename, 'size' => dir_size( $path . '/' . $filename ), 'date' => filemtime( $path . '/' . $filename ) ];
                }
            }
        }
        closedir( $handle );
    }  else {
        display_error( 'Invalid Directory' );
    }
}

### Function: Find Directory Size
function dir_size( $dir ) {
    $totalsize = 0;
    if( $dirstream = @opendir( $dir ) )  {
        while(false !== ( $filename = readdir( $dirstream) ) ) {
            if( ! in_array( $filename, [ '.', '..', '.git', '.svn' ] ) ) {
                if( is_file( $dir . '/' . $filename ) ) {
                    $totalsize += filesize( $dir . '/' . $filename );
                }
                if( is_dir( $dir . '/' . $filename ) ) {
                    $totalsize += dir_size( $dir . '/' . $filename );
                }
            }
        }
        closedir( $dirstream );
    }
    return $totalsize;
}

### Function: Determine File Extension Icon
function file_icon( $ext ) {
    global $extensions;
    if( in_array( $ext, array_keys( $extensions ) ) ) {
        return $extensions[$ext][1];
    }

    return 'fa-question';
}

### Function: Sort Array By Alphabets
function array_alphabetsort() {
   $arguments = func_get_args();
   $arrays = $arguments[0];
   for( $c = ( count( $arguments ) - 1 ); $c > 0; $c-- ) {
       if( in_array( $arguments[$c], [ SORT_ASC , SORT_DESC ] ) ) {
           continue;
       }
       $compare = create_function( '$a,$b','return strcasecmp($a["' . $arguments[$c] . '"], $b["' . $arguments[$c] . '"]);' );
       usort( $arrays, $compare );
       if( $arguments[$c+1] === SORT_DESC ) {
           $arrays = array_reverse( $arrays );
       }
   }
   return $arrays;
}

### Function: Sort Array By Numbers
function array_numbersort( $a, $b ) {
    global $sort_by;
    if( $a[$sort_by] === $b[$sort_by] ) {
        return 0;
    }
    return ( $a[$sort_by] < $b[$sort_by] ) ? -1 : 1;
}

### Function: Check Key In Multiple Arrays
function in_multi_array( $needle, $haystack ) {
    $in_multi_array = false;
    if( in_array( $needle, $haystack ) ) {
        $in_multi_array = true;
    } else {
        foreach( $haystack as $key => $val ) {
            if( is_array( $val ) ) {
                if( in_multi_array( $needle, $val ) ) {
                    $in_multi_array = true;
                    break;
                }
            }
        }
    }
    return $in_multi_array;
}

### Function: Form Sorting URL
function url( $url, $mode ) {
    global $sort_by;
    $temp_url = '';
    $temp_url_nice = '';
    $GET_sortby = ! empty( $_GET['by'] ) ? trim( $_GET['by'] ) : '';
    $GET_sortorder = ! empty( $_GET['order'] ) ? trim( $_GET['order'] ) : '';
    $url = urldecode( $url );
    $url = urlencode( $url );
    $url = str_replace( '%2F', '/', $url );
    switch( $mode ) {
        case 'dir':
            if( $url === 'home' ) {
                $temp_url = GFE_URL . '/' . GFE_ROOT_FILENAME;
                $temp_url_nice = GFE_URL . '/';
            } else {
                $temp_url = GFE_URL . '/' . GFE_ROOT_FILENAME . '?' . http_build_query( [ 'dir' => $url ] );
                $temp_url_nice = GFE_URL . '/browse/' . $url . '/';
            }
            if( ! empty( $GET_sortby ) ) {
                if( strpos( $temp_url, '?' ) === false ) {
                    $temp_url .= '?' . http_build_query( [ 'by' => $sort_by, 'order' => $GET_sortorder ] );
                } else {
                    $temp_url .= http_build_query( [ 'by' => $sort_by, 'order' => $GET_sortorder ] );
                }
                $temp_url_nice .= 'sortby/' . $sort_by . '/sortorder/' . $GET_sortorder . '/';
            }
            break;
        case 'file':
            $temp_url = GFE_URL . '/view.php?' . http_build_query( [ 'dir' => $url ] );
            $temp_url_nice = GFE_URL . '/viewing/' . $url . '/';
            break;
        case 'download';
            $temp_url = GFE_URL . '/view.php?' . http_build_query( [ 'file' => $url, 'dl' => 1 ] );
            $temp_url_nice = GFE_URL . '/download/' . $url . '/';
            break;
    }
    if( GFE_NICE_URL ) {
        return $temp_url_nice;
    } else {
        return $temp_url;
    }
}

### Function: Create Sorting URL
function create_sort_url( $sortby ) {
    global $directories_before_current_path, $current_directory_name, $sort_order;
    $directories_before_current_path = urldecode( $directories_before_current_path );
    $directories_before_current_path = urlencode( $directories_before_current_path );
    $directories_before_current_path = str_replace( '%2F', '/', $directories_before_current_path );
    $current_directory_name = urldecode( $current_directory_name );
    $current_directory_name = urlencode( $current_directory_name );
    $current_directory_name = str_replace( '%2F', '/', $current_directory_name );
    if( $sort_order === SORT_DESC ) {
        $sortorder = 'asc';
    } else {
        $sortorder = 'desc';
    }
    if( empty( $current_directory_name ) ) {
        $temp_url = '?' . http_build_query( [ 'by' => $sortby, 'order' => $sortorder ] );
        $temp_url_nice = GFE_URL . '/sortby/' . $sortby . '/sortorder/' . $sortorder . '/';
    } else {
        $temp_url = '?' . http_build_query( [ 'dir' => $directories_before_current_path . $current_directory_name, 'by' => $sortby, 'order' => $sortorder ] );
        $temp_url_nice = GFE_URL . '/browse/' . $directories_before_current_path . $current_directory_name . '/sortby/' . $sortby . '/sortorder/' . $sortorder . '/';
    }
    if( GFE_NICE_URL ) {
        return $temp_url_nice;
    } else {
        return $temp_url;
    }
}

### Function: Create Sorting Image
function create_sort_image( $sortby ) {
    if( ! empty( $_GET['by'] ) && trim( $_GET['by'] ) === $sortby ) {
        $get_sort_order = ! empty( $_GET['order'] ) ? trim( $_GET['order'] ) : '';
        if( $get_sort_order === 'asc' ) {
            return '<i class="fa fa-fw fa-sort-asc"></i>';
        } else {
            return '<i class="fa fa-fw fa-sort-desc"></i>';
        }
    }

    return '<i class="fa fa-fw fa-sort"></i>';
}

### Function: Determine the number of lines in a text file
function get_line_count( $file ) {
    $lines = 0;
    $handle = fopen( $file, 'r' );
    while( ! feof( $handle ) ) {
        $line = fgets( $handle );
        $lines++;
    }
    fclose( $handle );
    return $lines - 1;
}

### Function: Breadcrumbs
function breadcrumbs() {
    global $directory_names, $current_directory_name, $file_name, $file, $search_keyword;
    $temp_breadcrumb_path = '';
    $temp_breadcrumb = '<li><a href="' . url( 'home', 'dir' ) . '">Home</a></li>';
    if( ! empty( $file ) ) {
        $directory_names = explode( '/', $file );
        unset( $directory_names[sizeof( $directory_names ) - 1] );
    }
    if( ! empty( $directory_names ) ) {
        foreach( $directory_names as $directory_name ) {
            $temp_breadcrumb_path .= $directory_name.'/';
            $temp_breadcrumb_url = substr( $temp_breadcrumb_path, 0, -1 );
            $temp_breadcrumb .= '<li><a href="' . url( $temp_breadcrumb_url, 'dir' ) . '">' . $directory_name . '</a></li>';
        }
    }
    if( ! empty( $current_directory_name ) ) {
        $temp_breadcrumb .= '<li>' . $current_directory_name . '</li>';
    }
    if( ! empty( $file_name ) ) {
        $temp_breadcrumb .= '<li>' . $file_name . '</li>';
    }
    if( ! empty( $search_keyword ) ) {
        $temp_breadcrumb .= '<li><a href="' . GFE_URL . '/search.php">Search</a></li>';
        $temp_breadcrumb .= '<li>' . $search_keyword . '</li>';
    }
    return $temp_breadcrumb;
}

### Function: Display Error Message
function display_error( $msg ) {
    template_header( ' - Error - ' . $msg );
    echo '<div class="alert alert-danger" role="alert"><strong>' . $msg . '</strong>. You can <a href="' . GFE_URL . '">go back to the main site</a> or <a href="' . GFE_URL . '" onclick="return false; javascript: history.go(-1);">go back to the previous page</a>.</div>';
    template_footer();
    exit();
}


function template_header( $title = '' ) {
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo GFE_SITE_NAME . $title; ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta http-equiv="x-dns-prefetch-control" content="on">
        <meta name="copyright" content="Copyright &copy; <?php echo date( 'Y' ); ?> Lester Chan, All Rights Reserved.">
        <meta name="author" content="Lester Chan">
        <meta name="description" content="<?php echo GFE_SITE_DESCRIPTION; ?>">
        <meta property="og:site_name" content="<?php echo GFE_SITE_NAME; ?>">
        <meta property="og:title" content="<?php echo GFE_SITE_NAME . $title; ?>">
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo GFE_URL . $_SERVER['REQUEST_URI']; ?>">
        <meta property="og:image" content="<?php echo GFE_URL; ?>/resources/icon.png">
        <meta property="og:description" content="<?php echo GFE_SITE_DESCRIPTION; ?>">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="<?php echo GFE_SITE_NAME . $title; ?>">
        <meta name="twitter:url" content="<?php echo GFE_URL . $_SERVER['REQUEST_URI']; ?>">
        <meta name="twitter:image" content="<?php echo GFE_URL; ?>/resources/icon.png">
        <meta name="twitter:description" content="<?php echo GFE_SITE_DESCRIPTION; ?>">
        <link rel="dns-prefetch" href="//www.google-analytics.com">
        <link rel="shortcut icon" href="<?php echo GFE_URL; ?>/resources/favicon.ico" type="image/x-icon">
        <link rel="icon" href="<?php echo GFE_URL; ?>/resources/favicon.ico" type="image/x-icon">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha/css/bootstrap.min.css">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.8.0/styles/default.min.css">
    </head>
    <body>
        <div class="container">
            <!-- Title -->
            <h1><?php echo GFE_SITE_NAME; ?></h1>
            <hr>

            <!-- Breadcrumbs -->
            <ol class="breadcrumb">
                <?php echo breadcrumbs(); ?>
            </ol>
<?php
}

function template_footer() {
    global $full_url;
?>
    <?php if( ! empty( $full_url ) ): ?>
        <!-- Current File Directory Path -->
        <ol class="breadcrumb">
          <li><?php echo $full_url; ?></li>
        </ol>
    <?php endif; ?>
    <?php if( GFE_CAN_SEARCH ): ?>
        <?php if( basename( $_SERVER['SCRIPT_FILENAME'] ) !== 'search.php' ): ?>
            <!-- Search Engine -->
            <form class="form-inline" method="get" action="<?php echo GFE_URL; ?>/search.php">
                <div class="form-group">
                    <label class="sr-only" for="search-bottom-keyword">Search for files</label>
                    <input type="text" class="form-control" id="search-bottom-keyword" name="search" placeholder="Search for files ...">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <p>
                    <small class="text-muted">
                        <a href="<?php echo GFE_URL; ?>/search.php">Advanced Search</a>
                    </small>
                </p>
            </form>
        <?php endif; ?>
    <?php endif; ?>
        <!-- Copyright -->
        <div class="row">
            <div class="col-sm-12">
                <hr>
                <p class="text-center">
                    <small class="text-muted">
                        Powered By <a href="https://lesterchan.net/">GaMerZ File Explorer Version <?php echo GFE_VERSION; ?></a>. Page Generated In <?php echo stop_timer(); ?>s.</a>
                    </small>
                    <br />
                    <small class="text-muted">
                        Copyright &copy; <?php echo date( 'Y' ); ?> Lester Chan, All Rights Reserved.
                    </small>
                </p>
            </div>
        </div>
        <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
        <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.8.0/highlight.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                hljs.initHighlightingOnLoad();
            });
        </script>
    </body>
</html>
<?php
}