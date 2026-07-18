<?php

declare(strict_types=1);

### Start Timer
define('GFE_START', microtime(true));

### Require Config, Setting And Function Files
require 'config.php';
$settings = require 'settings.php';
require 'functions.php';

### Get And Check Current Directory Path
$url_path = urldecode(trim($_GET['dir'] ?? ''));
if (! is_safe_path($url_path)) {
    display_error('Invalid Directory');
}

### Check Whether Directory Is In The Ignore Folders
if (in_array($url_path, $settings['ignore_folders'], true)) {
    display_error('Invalid Directory');
}

### Determine Sort Order
$get_sort_order = trim($_GET['order'] ?? '') ?: GFE_DEFAULT_SORT_ORDER;
$sort_order = $get_sort_order === 'asc' ? SORT_ASC : SORT_DESC;

### Determine Sort By
$get_sort_by = trim($_GET['by'] ?? '') ?: GFE_DEFAULT_SORT_BY;
$sort_by = match ($get_sort_by) {
    'name', 'size', 'type', 'date' => $get_sort_by,
    default => 'date',
};

### Break The Path Into The Current Directory And Everything Before It
$directory_names = $url_path === '' ? [] : explode('/', $url_path);
$current_directory_name = $directory_names === [] ? '' : (string) array_pop($directory_names);
$directories_before_current = implode('/', $directory_names);

### Build The Trailing-Slash Prefixes Used For Ignore-List Matching And Links
$current_directory_path = $current_directory_name !== '' ? $current_directory_name . '/' : '';
$directories_before_current_path = $directories_before_current !== '' ? $directories_before_current . '/' : '';
$prefix = $directories_before_current_path . $current_directory_path;

### Canonical (Clickable) Permalink For This Listing
$full_url = url($url_path === '' ? 'home' : $url_path, 'dir');

### Full Filesystem Path Of The Directory To List
$full_directory_path = $url_path === '' ? GFE_ROOT_DIR : GFE_ROOT_DIR . '/' . $url_path;

### List The Files/Directories In This Level
$listing = list_directory($full_directory_path, $settings, $prefix);
$gmz_files = sort_entries($listing['files'], $sort_by, $sort_order);
$gmz_directories = sort_entries($listing['directories'], $sort_by === 'type' ? 'name' : $sort_by, $sort_order);

### Column Header Helper
$sort_header = static function (string $column, string $label, string $width) use ($directories_before_current_path, $current_directory_name, $sort_order, $sort_by, $get_sort_order): string {
    $link = htmlspecialchars(create_sort_url($column, $directories_before_current_path, $current_directory_name, $sort_order), ENT_QUOTES, 'UTF-8');
    $icon = create_sort_image($column, $sort_by, $get_sort_order);
    $active = $column === $sort_by ? ' class="gfe-sort-active"' : '';
    return '<th' . $active . ' style="width: ' . $width . ';"><a class="text-decoration-none text-reset d-block" href="' . $link . '" title="Sort By ' . $label . '">' . $label . '&nbsp;' . $icon . '</a></th>';
};

$breadcrumbs = breadcrumbs([
    'directory_names' => $directory_names,
    'current_directory_name' => $current_directory_name,
    'sort_by' => $get_sort_by,
    'sort_order' => $get_sort_order,
]);
?>
<?php template_header($current_directory_name !== '' ? ' - Viewing Directory - ' . $current_directory_name : '', $breadcrumbs); ?>

            <!-- List Directories/Files -->
            <div class="table-responsive gfe-surface">
                <table class="table gfe-table align-middle">
                    <thead>
                        <tr>
                            <?php
                            echo $sort_header('name', 'Name', '50%');
                            echo $sort_header('size', 'Size', '10%');
                            echo $sort_header('type', 'Type', '20%');
                            echo $sort_header('date', 'Date', '20%');
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // If It Is Down One Level, Provide "Parent Directory"
                        if ($url_path !== '') {
                            $parent_directory = $directory_names !== [] ? $directories_before_current : 'home';
                            echo '<tr class="gfe-row-parent">';
                            echo '<td colspan="4"><a href="' . url($parent_directory, 'dir', $get_sort_by, $get_sort_order) . '" title="Parent Directory"><i class="fa-solid fa-fw fa-arrow-turn-up fa-rotate-270"></i>&nbsp;Parent Directory</a></td>';
                            echo '</tr>';
                        }
                        // Directories
                        foreach ($gmz_directories as $value) {
                            $directory_name = $value['name'];
                            $directory_name_escaped = htmlspecialchars($directory_name, ENT_QUOTES, 'UTF-8');
                            $directory_size = format_size($value['size']);
                            $directory_date = date('jS F Y', $value['date']);
                            echo '<tr>';
                            echo '<td><a href="' . url($prefix . $directory_name, 'dir', $get_sort_by, $get_sort_order) . '" title="Folder: ' . $directory_name_escaped . ' (' . $directory_size . ')"><i class="fa-solid fa-fw fa-folder"></i>&nbsp;' . $directory_name_escaped . '</a></td>';
                            echo '<td>' . $directory_size . '</td>';
                            echo '<td>File Folder</td>';
                            echo '<td>' . $directory_date . '</td>';
                            echo '</tr>';
                        }
                        // Files
                        if ($gmz_files !== []) {
                            foreach ($gmz_files as $value) {
                                echo file_row($value, $prefix . $value['name'], $settings['extensions']);
                            }
                        } elseif ($gmz_directories === []) {
                            echo '<tr class="gfe-row-empty"><td class="text-center" colspan="4">This folder is empty.</td></tr>';
                        }
                        // Folder And File Stats
                        $total_folders = count($gmz_directories);
                        $total_files = count($gmz_files);
                        $total_size = format_size(dir_size($full_directory_path));
                        $total_folders_name = $total_folders === 1 ? 'folder' : 'folders';
                        $total_files_name = $total_files === 1 ? 'file' : 'files';
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
<?php template_footer($full_url, $full_url); ?>
