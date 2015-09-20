<?php
### Require Config, Setting And Function Files
require( 'config.php' );
require( 'settings.php' );
require( 'functions.php' );

### Start Timer
start_timer();

### Check Whether Search Is Enabled
if( ! GFE_CAN_SEARCH ) {
    display_error( 'The Administrator Has Disabled The Searching Of Files' );
}

### Variables Variables Variables
$get_sort_order = ! empty( $_GET['order'] ) ? trim( $_GET['order'] ) : '';
$get_sort_by = ! empty( $_GET['by'] ) ? trim( $_GET['by'] ) : '';
$search_keyword = ! empty( $_GET['search'] ) ? trim( strip_tags( stripslashes( $_GET['search'] ) ) ) : '';
$search_in = ! empty( $_GET['in'] ) ? trim( strip_tags( stripslashes( $_GET['in'] ) ) )  : '';

// Variables Variables Variables
$sort_order = '';
$sort_order_image = '';
$search_results = [];
$sort_by = 'date';
$sort_order_text = 'Descending';

### Process Search
if( ! empty( $_GET['search'] ) ) {
    // Determine Sort Order
    if( empty( $get_sort_order ) ) {
        $get_sort_order = GFE_DEFAULT_SORT_ORDER;
    }
    switch( $get_sort_order ) {
        case 'asc':
            $sort_order = SORT_ASC;
            $sort_order_text = 'Ascending';
            break;
        case 'desc':
        default:
            $sort_order = SORT_DESC;
            $sort_order_text = 'Descending';
    }

    // Determine Sort By
    if( empty( $get_sort_by ) ) {
        $get_sort_by = GFE_DEFAULT_SORT_BY;
    }
    switch( $get_sort_by ) {
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
    if( empty( $search_in ) ) {
        $search_in = 'all';
    }

    // List All The files
    list_files( GFE_ROOT_DIR );

    // Check For Matches
    foreach( $gmz_files as $gmz_file ) {
        if( $search_in !== 'all' ) {
            if( strpos( strtolower( $gmz_file['name'] ), strtolower( $search_keyword ) ) !== false && strpos( $gmz_file['path'], $search_in ) !== false ) {
                $search_results[] = $gmz_file;
            }
        } else {
            if( strpos( strtolower( $gmz_file['name'] ), strtolower( $search_keyword ) ) !== false ) {
                $search_results[] = $gmz_file;
            }
        }
    }

    // We Do Not Need The File Listings Anymore
    unset( $gmz_files );

    // Sort The Array
    if( $sort_by === 'name' ) {
        $search_results = array_alphabetsort( $search_results, $sort_by, $sort_order );
    } elseif( $sort_by === 'type' ) {
        $search_results = array_alphabetsort( $search_results, $sort_by, $sort_order );
    } else {
        usort( $search_results, 'array_numbersort' );
        if( $sort_order === SORT_DESC ) {
            $search_results = array_reverse( $search_results );
        }
    }
} else {
    // List All Directories
    list_directories( GFE_ROOT_DIR );
}
?>

<?php template_header( ! empty( $search_keyword ) ? ' - Search - ' . $search_keyword : ' - Search' ); ?>

<!-- Search Files -->
<form class="form" method="get" action="<?php echo GFE_URL; ?>/search.php">
    <div class="form-group row">
        <label for="search-term" class="col-sm-2 form-control-label">Search Term</label>
        <div class="col-sm-10">
            <input type="text" name="search" class="form-control" id="search-term" placeholder="Files ..." value="<?php echo $search_keyword; ?>">
        </div>
    </div>
    <div class="form-group row">
        <label for="search-in" class="col-sm-2 form-control-label">Search In</label>
        <div class="col-sm-10">
            <select id="search-in" name="in" class="form-control" size="1">
                <option value="all">All Folders</option>
                <?php
                foreach( $gmz_directories as $gmz_directory ) {
                    if( $gmz_directory === $search_in ) {
                        echo '<option value="' . $gmz_directory . '" selected="selected">' . $gmz_directory . '</option>';
                    } else {
                        echo '<option value="' . $gmz_directory . '">' . $gmz_directory . '</option>';
                    }
                }
                ?>
            </select>
        </div>
    </div>
    <div class="form-group row">
        <label for="sort-by" class="col-sm-2 form-control-label">Sort By</label>
        <div class="col-sm-10">
            <select id="sort-by" name="by" class="form-control" size="1">
                <option value="name"<?php echo ( $sort_by === 'name' ? ' selected="selected"' : '' ); ?>>File Name</option>
                <option value="size"<?php echo ( $sort_by === 'size' ? ' selected="selected"' : '' ); ?>>File Size</option>
                <option value="type"<?php echo ( $sort_by === 'type' ? ' selected="selected"' : '' ); ?>>File Type</option>
                <option value="date"<?php echo ( $sort_by === 'date' ? ' selected="selected"' : '' ); ?>>File Date</option>
            </select>
        </div>
    </div>
    <div class="form-group row">
        <label for="sort-order" class="col-sm-2 form-control-label">Sort Order</label>
        <div class="col-sm-10">
            <select id="sort-order" name="order" class="form-control" size="1">
                <option value="asc"<?php echo ( $sort_order_text === 'Ascending' ?  ' selected="selected"' : '' ); ?>>Ascending</option>
                <option value="desc"<?php echo ( $sort_order_text === 'Descending' ? ' selected="selected"' : '' ); ?>>Descending</option>
            </select>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </div>
</form>
<?php
    ### If Not Searching, Don't Display Results Page
    if( ! empty( $search_keyword ) ) {
        $total_size = 0;
?>
    <!-- List Search Results Files -->
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead class="thead-default">
                <tr>
                    <th style="width: 50%;" title="Name">Name</th>
                    <th style="width: 10%;" title="Size">Size</th>
                    <th style="width: 20%;" title="Type">Type</th>
                    <th style="width: 20%;" title="Date">Date</th>
                </tr>
            </thead>
            <tbody>
        <?php
            if( ! empty( $search_results ) ) {
                foreach( $search_results as $key => $value ) {
                    $file_name = $value['name'];
                    $file_size = format_size( $value['size'] );
                    $file_date = date( 'jS F Y', $value['date'] );
                    $file_extension = $value['type'];
                    $total_size += $value['size'];
                    echo '<tr>';
                    echo '<td><a href="' . url( $value['path'], 'file' ) . '" title="File: ' . $file_name . ' ('. $file_size . ')"><i class="fa fa-fw ' . file_icon( $value['ext'] ) . '"></i>&nbsp;' . $file_name . '</a></td>';
                    echo '<td>' . $file_size . '</td>';
                    echo '<td>' . $file_extension . '</td>';
                    echo '<td>' . $file_date . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr class="table-info"><td class="text-center" colspan="4">No files found with the search term \'' . $search_keyword . '\'.</td></tr>';
            }

            // File Stats Variables
            $total_files = sizeof( $search_results );
            $total_size = format_size( $total_size );
            $total_files_name = ( $total_files > 1 ? 'files' : 'file' );
        ?>
            </tbody>
            <tfoot>
                <tr>
                    <td><strong><?php echo $total_files . ' ' . $total_files_name; ?></strong></td>
                    <td><strong><?php echo $total_size; ?></strong></td>
                    <td>-</td>
                    <td>-</td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php
    }
?>
<?php template_footer(); ?>