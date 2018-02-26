<?php
### Require Config, Setting And Function Files
require 'config.php';
require 'settings.php';
require 'functions.php';

### Start Timer
start_timer();

### Get And Check Current Directory Path
$url_path = !empty($_GET['dir']) ? urldecode(trim(stripslashes($_GET['dir']))) : '';
if (strpos($url_path, '../') !== false || strpos($url_path, './') !== false || strpos($url_path, '//') !== false) {
    display_error('Invalid Directory');
}

### Check Whether Directory Is In The Ignore Folders
if (in_array($url_path, $ignore_folders, true)) {
    display_error('Invalid Directory');
}

### Variables Variables Variables
$get_sort_order = !empty($_GET['order']) ? trim($_GET['order']) : '';
$get_sort_by = !empty($_GET['by']) ? trim($_GET['by']) : '';
$full_directory_path = '';
$directories_before_current = '';
$directories_before_current_path = '';
$current_directory_name = '';
$current_directory_path = '';
$sort_order = '';
$sort_order_text = '';
$sort_by = '';
$gmz_files = [];
$gmz_directories = [];
$directory_names = explode('/', $url_path);

### Current Directory Name
$current_directory_name = $directory_names[count($directory_names) - 1];

### Unset Current Directory Name
unset($directory_names[count($directory_names) - 1]);

### Directory Path Up To Current Directory
if (!empty($directory_names)) {
    foreach ($directory_names as $directory_name) {
        $directories_before_current .= $directory_name . '/';
    }
    $directories_before_current = substr($directories_before_current, 0, -1);
}

### If No Directory Is Specified
if (empty($url_path)) {
    $full_directory_path = GFE_ROOT_DIR;
} else {
    $full_directory_path = GFE_ROOT_DIR . '/' . $url_path;
}

### If Current Directory Is Not Empty, Add A Trailing Slash
if (!empty($current_directory_name)) {
    $current_directory_path = $current_directory_name . '/';
}

### If There Is Directory Before The Current Directory, Add A Trailing Slash
if (!empty($directories_before_current)) {
    $directories_before_current_path = $directories_before_current . '/';
}

### Full URL
$full_url = GFE_ROOT_URL . '/' . $directories_before_current_path . $current_directory_path;

### Determine Sort Order
if (empty($get_sort_order)) {
    $get_sort_order = GFE_DEFAULT_SORT_ORDER;
}
switch ($get_sort_order) {
    case 'asc':
        $sort_order = SORT_ASC;
        $sort_order_text = 'Ascending';
        break;
    case 'desc':
    default:
        $sort_order = SORT_DESC;
        $sort_order_text = 'Descending';
}

### Determine Sort By
if (empty($get_sort_by)) {
    $get_sort_by = GFE_DEFAULT_SORT_BY;
}
switch ($get_sort_by) {
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
list_directories_files($full_directory_path);

### Sort The Array
if ($sort_by === 'name') {
    $gmz_files = array_alphabetsort($gmz_files, $sort_by, $sort_order);
    $gmz_directories = array_alphabetsort($gmz_directories, $sort_by, $sort_order);
} elseif ($sort_by === 'type') {
    $gmz_files = array_alphabetsort($gmz_files, $sort_by, $sort_order);
} else {
    usort($gmz_files, 'array_numbersort');
    usort($gmz_directories, 'array_numbersort');
    if ($sort_order === SORT_DESC) {
        $gmz_files = array_reverse($gmz_files);
        $gmz_directories = array_reverse($gmz_directories);
    }
}
?>
<?php template_header(!empty($current_directory_name) ? ' - Viewing Directory - ' . $current_directory_name : ''); ?>

    <!-- List Directories/Files -->
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead class="thead-default">
            <tr>
                <th style="width: 50%;" onclick="parent.location.href='<?php echo create_sort_url('name'); ?>';"
                    onmouseover="this.style.cursor = 'pointer';" title="Sort By Name">
                    Name&nbsp;<?php echo create_sort_image('name'); ?></th>
                <th style="width: 10%;" onclick="parent.location.href='<?php echo create_sort_url('size'); ?>';"
                    onmouseover="this.style.cursor = 'pointer';" title="Sort By Size">
                    Size&nbsp;<?php echo create_sort_image('size'); ?></th>
                <th style="width: 20%;" onclick="parent.location.href='<?php echo create_sort_url('type'); ?>';"
                    onmouseover="this.style.cursor = 'pointer';" title="Sort By Type">
                    Type&nbsp;<?php echo create_sort_image('type'); ?></th>
                <th style="width: 20%;" onclick="parent.location.href='<?php echo create_sort_url('date'); ?>';"
                    onmouseover="this.style.cursor = 'pointer';" title="Sort By Date">
                    Date&nbsp;<?php echo create_sort_image('date'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            // If It Is Down One Level, Provide "Up One Level"
            if (!empty($url_path)) {
                if (!empty($directory_names)) {
                    $parent_directory = $directories_before_current;
                } else {
                    $parent_directory = 'home';
                }
                echo '<tr class="table-warning">';
                echo '<td colspan="4"><a href="' . url($parent_directory, 'dir') . '" title="Parent Directory"><i class="fa fa-chevron-left"></i>&nbsp;Parent Directory</a></td>';
                echo '</tr>';
            }
            // If There Is Directory
            if (!empty($gmz_directories)) {
                foreach ($gmz_directories as $key => $value) {
                    $directory_name = $value['name'];
                    $directory_size = format_size($value['size']);
                    $directory_date = date('jS F Y', $value['date']);
                    echo '<tr>';
                    echo '<td><a href="' . url($directories_before_current_path . $current_directory_path . $directory_name, 'dir') . '" title="Folder: ' . $directory_name . ' (' . $directory_size . ')"><i class="fa fa-fw fa-folder"></i>&nbsp;' . $directory_name . '</a></td>';
                    echo '<td>' . $directory_size . '</td>';
                    echo '<td>File Folder</td>';
                    echo '<td>' . $directory_date . '</td>';
                    echo '</tr>';
                }
            }
            // If There Is Files
            if (!empty($gmz_files)) {
                foreach ($gmz_files as $key => $value) {
                    $file_name = $value['name'];
                    $file_size = format_size($value['size']);
                    $file_date = date('jS F Y', $value['date']);
                    $file_extension = $value['type'];
                    echo '<tr>';
                    echo '<td><a href="' . url($directories_before_current_path . $current_directory_path . $file_name, 'file') . '" title="File: ' . $file_name . ' (' . $file_size . ')"><i class="fa fa-fw ' . file_icon($value['ext']) . '"></i>&nbsp;' . $file_name . '</a></td>';
                    echo '<td>' . $file_size . '</td>';
                    echo '<td>' . $file_extension . '</td>';
                    echo '<td>' . $file_date . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr class="table-info"><td class="text-center" colspan="4">No files found.</td></tr>';
            }
            // Folder And File Stats Variables
            $total_folders = count($gmz_directories);
            $total_files = count($gmz_files);
            $total_size = format_size(dir_size($full_directory_path));
            $total_folders_name = ($total_folders > 1 ? 'folders' : 'folder');
            $total_files_name = ($total_files > 1 ? 'files' : 'file');
            $total_folders_files = $total_folders . ' ' . $total_folders_name . ', ' . $total_files . ' ' . $total_files_name;
            ?>
            </tbody>
            <tfoot>
            <tr>
                <td><strong><?php echo $total_folders_files; ?></strong></td>
                <td><strong><?php echo $total_size; ?></strong></td>
                <td>-</td>
                <td>-</td>
            </tr>
            </tfoot>
        </table>
    </div>
<?php template_footer(); ?>