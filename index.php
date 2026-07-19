<?php

declare(strict_types=1);

define('GFE_START', microtime(true));

require 'config.php';
$settings = require 'settings.php';
require 'functions.php';

$url_path = urldecode(trim($_GET['dir'] ?? ''));
if (! is_safe_path($url_path)) {
    display_error('Invalid Directory');
}

foreach ($settings['ignore_folders'] as $ignored_folder) {
    if ($url_path === $ignored_folder || str_starts_with($url_path, $ignored_folder . '/')) {
        display_error('Invalid Directory');
    }
}

$get_sort_order = trim($_GET['order'] ?? '') ?: GFE_DEFAULT_SORT_ORDER;
$sort_order = sort_direction($get_sort_order);

$get_sort_by = trim($_GET['by'] ?? '') ?: GFE_DEFAULT_SORT_BY;
$sort_by = sort_field($get_sort_by);

$directory_names = $url_path === '' ? [] : explode('/', $url_path);
$current_directory_name = $directory_names === [] ? '' : (string) array_pop($directory_names);
$directories_before_current = implode('/', $directory_names);

$current_directory_path = $current_directory_name !== '' ? $current_directory_name . '/' : '';
$directories_before_current_path = $directories_before_current !== '' ? $directories_before_current . '/' : '';
$prefix = $directories_before_current_path . $current_directory_path;

$full_url = url($url_path === '' ? 'home' : $url_path, 'dir');

$full_directory_path = $url_path === '' ? GFE_ROOT_DIR : GFE_ROOT_DIR . '/' . $url_path;

$listing = list_directory($full_directory_path, $settings, $prefix);
$gmz_files = sort_entries($listing['files'], $sort_by, $sort_order);
$gmz_directories = sort_entries($listing['directories'], $sort_by === 'type' ? 'name' : $sort_by, $sort_order);

$sort_header = static function (string $column, string $label, string $width) use ($directories_before_current_path, $current_directory_name, $sort_order, $sort_by, $get_sort_order): string {
    $link = esc(create_sort_url($column, $directories_before_current_path, $current_directory_name, $sort_order));
    $icon = create_sort_image($column, $sort_by, $get_sort_order);
    $active = $column === $sort_by ? ' class="gfe-sort-active"' : '';
    $ariaSort = $column === $sort_by
        ? ' aria-sort="' . ($get_sort_order === 'asc' ? 'ascending' : 'descending') . '"'
        : '';
    return '<th' . $active . $ariaSort . ' style="width: ' . $width . ';"><a class="text-decoration-none text-reset d-block" href="' . $link . '" title="Sort By ' . $label . '">' . $label . '&nbsp;' . $icon . '</a></th>';
};

$breadcrumbs = breadcrumbs([
    'directory_names' => $directory_names,
    'current_directory_name' => $current_directory_name,
    'sort_by' => $get_sort_by,
    'sort_order' => $get_sort_order,
]);
?>
<?php template_header($current_directory_name !== '' ? ' - Viewing Directory - ' . $current_directory_name : '', $breadcrumbs, $full_url); ?>

            <div class="table-responsive gfe-surface">
                <table class="table gfe-table align-middle" id="gfe-listing">
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
                        if ($url_path !== '') {
                            $parent_directory = $directory_names !== [] ? $directories_before_current : 'home';
                            echo '<tr class="gfe-row-parent">';
                            echo '<td colspan="4"><a href="' . esc(url($parent_directory, 'dir', $get_sort_by, $get_sort_order)) . '" title="Parent Directory"><i class="fa-solid fa-fw fa-arrow-turn-up fa-rotate-270" aria-hidden="true"></i>&nbsp;Parent Directory</a></td>';
                            echo '</tr>';
                        }
                        foreach ($gmz_directories as $value) {
                            $directory_name = $value['name'];
                            $directory_name_escaped = esc($directory_name);
                            $directory_size = format_size($value['size']);
                            $directory_date = date('jS F Y', $value['date']);
                            echo '<tr>';
                            echo '<td><a href="' . esc(url($prefix . $directory_name, 'dir', $get_sort_by, $get_sort_order)) . '" title="Folder: ' . $directory_name_escaped . ' (' . $directory_size . ')"><i class="fa-solid fa-fw fa-folder" aria-hidden="true"></i>&nbsp;' . $directory_name_escaped . '</a></td>';
                            echo '<td>' . $directory_size . '</td>';
                            echo '<td>File Folder</td>';
                            echo '<td>' . $directory_date . '</td>';
                            echo '</tr>';
                        }
                        if ($gmz_files !== []) {
                            foreach ($gmz_files as $value) {
                                echo file_row($value, $prefix . $value['name'], $settings['extensions'], '', $get_sort_by, $get_sort_order);
                            }
                        } elseif ($gmz_directories === []) {
                            echo '<tr class="gfe-row-empty"><td class="text-center" colspan="4">This folder is empty.</td></tr>';
                        }
                        // Totalled from the sizes gathered above, so the tree is walked once, not twice.
                        $total_folders = count($gmz_directories);
                        $total_files = count($gmz_files);
                        $total_size = format_size(
                            array_sum(array_column($gmz_files, 'size'))
                            + array_sum(array_column($gmz_directories, 'size'))
                        );
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
