<?php

declare(strict_types=1);

define('GFE_START', microtime(true));

require 'config.php';
$settings = require 'settings.php';
require 'functions.php';

if (! GFE_CAN_SEARCH) {
    display_error('The administrator has disabled the searching of files');
}

$search_keyword = trim(strip_tags($_GET['search'] ?? ''));
$search_in = trim(strip_tags($_GET['in'] ?? ''));
$search_match = strip_tags($_GET['match'] ?? '') === 'path' ? 'path' : 'name';
$get_sort_order = trim($_GET['order'] ?? '');
$get_sort_by = trim($_GET['by'] ?? '');

$search_results = [];
$gmz_directories = [];
$sort_by = 'date';
$sort_order = SORT_DESC;
$sort_order_text = 'Descending';

if ($search_keyword !== '') {
    $get_sort_order = $get_sort_order ?: GFE_DEFAULT_SORT_ORDER;
    $sort_order = sort_direction($get_sort_order);
    $sort_order_text = $get_sort_order === 'asc' ? 'Ascending' : 'Descending';

    $get_sort_by = $get_sort_by ?: GFE_DEFAULT_SORT_BY;
    $sort_by = sort_field($get_sort_by);

    if ($search_in === '') {
        $search_in = 'all';
    }

    // Filtering happens during the walk so the whole tree is never materialised.
    $search_results = list_files(
        GFE_ROOT_DIR,
        $settings,
        static function (array $gmz_file) use ($search_keyword, $search_match, $search_in): bool {
            $haystack = $search_match === 'path' ? ($gmz_file['path'] ?? '') : $gmz_file['name'];
            if (stripos($haystack, $search_keyword) === false) {
                return false;
            }
            return $search_in === 'all' || str_starts_with($gmz_file['path'] ?? '', $search_in . '/');
        }
    );

    $search_results = sort_entries($search_results, $sort_by, $sort_order);
} else {
    $gmz_directories = list_directories(GFE_ROOT_DIR, $settings);
    sort($gmz_directories);
}

$breadcrumbs = breadcrumbs(['search_keyword' => $search_keyword]);
?>
<?php template_header($search_keyword !== '' ? ' - Search - ' . $search_keyword : ' - Search', $breadcrumbs, '', '', $search_keyword); ?>

            <form class="gfe-panel mb-4" method="get" action="<?php echo GFE_URL; ?>/search.php">
                <div class="row mb-3">
                    <label for="search-term" class="col-sm-2 col-form-label">Search term</label>
                    <div class="col-sm-10">
                        <input type="text" name="search" class="form-control" id="search-term" placeholder="Files &hellip;"
                               value="<?php echo esc($search_keyword); ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="search-in" class="col-sm-2 col-form-label">Search in</label>
                    <div class="col-sm-10">
                        <select id="search-in" name="in" class="form-select" size="1">
                            <option value="all">All folders</option>
                            <?php
                            foreach ($gmz_directories as $gmz_directory) {
                                $gmz_directory_escaped = esc($gmz_directory);
                                $selected = $gmz_directory === $search_in ? ' selected' : '';
                                echo '<option value="' . $gmz_directory_escaped . '"' . $selected . '>' . $gmz_directory_escaped . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="search-match" class="col-sm-2 col-form-label">Match</label>
                    <div class="col-sm-10">
                        <select id="search-match" name="match" class="form-select" size="1">
                            <option value="name"<?php echo $search_match === 'name' ? ' selected' : ''; ?>>File name</option>
                            <option value="path"<?php echo $search_match === 'path' ? ' selected' : ''; ?>>Name &amp; folder path</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="sort-by" class="col-sm-2 col-form-label">Sort by</label>
                    <div class="col-sm-10">
                        <select id="sort-by" name="by" class="form-select" size="1">
                            <option value="name"<?php echo $sort_by === 'name' ? ' selected' : ''; ?>>File name</option>
                            <option value="size"<?php echo $sort_by === 'size' ? ' selected' : ''; ?>>File size</option>
                            <option value="type"<?php echo $sort_by === 'type' ? ' selected' : ''; ?>>File type</option>
                            <option value="date"<?php echo $sort_by === 'date' ? ' selected' : ''; ?>>File date</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="sort-order" class="col-sm-2 col-form-label">Sort order</label>
                    <div class="col-sm-10">
                        <select id="sort-order" name="order" class="form-select" size="1">
                            <option value="asc"<?php echo $sort_order_text === 'Ascending' ? ' selected' : ''; ?>>Ascending</option>
                            <option value="desc"<?php echo $sort_order_text === 'Descending' ? ' selected' : ''; ?>>Descending</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-10 offset-sm-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </div>
            </form>
<?php
if ($search_keyword !== '') {
    $total_size = 0;
    ?>
            <div class="table-responsive gfe-surface">
                <table class="table gfe-table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Name</th>
                            <th style="width: 10%;">Size</th>
                            <th style="width: 20%;">Type</th>
                            <th style="width: 20%;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($search_results !== []) {
                            foreach ($search_results as $value) {
                                $folder = dirname((string) ($value['path'] ?? ''));
                                $folder_label = $search_match === 'path'
                                    ? highlight_match($folder, $search_keyword)
                                    : esc($folder);
                                $folder_html = $folder !== '' && $folder !== '.'
                                    ? '<div class="small text-body-secondary">' . $folder_label . '</div>'
                                    : '';
                                $total_size += $value['size'];
                                echo file_row($value, $value['path'] ?? '', $settings['extensions'], $settings['date_format'], $folder_html, $get_sort_by, $get_sort_order, $search_keyword);
                            }
                        } else {
                            echo '<tr class="gfe-row-empty"><td class="text-center" colspan="4">No files match &lsquo;' . esc($search_keyword) . '&rsquo;. Try a different term.</td></tr>';
                        }

                        $total_files = count($search_results);
                        $total_size = format_size($total_size);
                        $total_files_name = $total_files === 1 ? 'file' : 'files';
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
