<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   tool_wincache
 * @copyright 2013 Ryan Panning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/wincache/constants.php');
require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/wincache/locallib.php');




// Setup admin page; authorizing, etc.
admin_externalpage_setup('toolwincache');




// Cannot show any info without WinCache!
if (!extension_loaded('wincache') || version_compare(phpversion('wincache'), '1.1.0', '<')) {
    echo $OUTPUT->header();
    echo '<p class="notice">' . print_string('not_loaded', 'tool_wincache') . '</p>';
    echo $OUTPUT->footer();
    return;
}




// WinCache settings that are used for debugging purposes
$settings_to_hide = array( 'wincache.localheap', 'wincache.debuglevel', 'wincache.olocaltest' );

// Input parameters check
$PHP_SELF = isset( $_SERVER['PHP_SELF'] ) ? htmlentities( strip_tags( $_SERVER['PHP_SELF'],'' ), ENT_QUOTES, 'UTF-8' ) : '';
$page = isset( $_GET['page'] ) ? $_GET['page'] : WINCACHE_SUMMARY_DATA;
if ( !is_numeric( $page ) || $page < WINCACHE_SUMMARY_DATA || $page > WINCACHE_RCACHE_DATA )
    $page = WINCACHE_SUMMARY_DATA;

$img = 0;
if ( isset( $_GET['img'] ) && is_numeric( $_GET['img'] ) ) {
    $img = $_GET['img'];
    if ( $img < WINCACHE_OCACHE_DATA || $img > WINCACHE_SCACHE_DATA)
        $img = 0;
}
$chart_type = WINCACHE_BAR_CHART;
if ( isset( $_GET['type'] ) && is_numeric( $_GET['type'] ) ) {
    $chart_type = $_GET['type'];
    if ( $chart_type < WINCACHE_BAR_CHART || $chart_type > WINCACHE_PIE_CHART)
        $chart_type = WINCACHE_BAR_CHART;
}
$chart_param1 = 0;
if ( isset( $_GET['p1'] ) && is_numeric( $_GET['p1'] ) ) {
    $chart_param1 = $_GET['p1'];
    if ( $chart_param1 < 0 )
        $chart_param1 = 0;
    else if ( $chart_param1 > PHP_INT_MAX )
        $chart_param1 = PHP_INT_MAX;
}
$chart_param2 = 0;
if ( isset( $_GET['p2'] ) && is_numeric( $_GET['p2'] ) ) {
    $chart_param2 = $_GET['p2'];
    if ( $chart_param2 < 0 )
        $chart_param2 = 0;
    else if ( $chart_param2 > PHP_INT_MAX )
        $chart_param2 = PHP_INT_MAX;
}

$show_all_ucache_entries = 0;
if ( isset( $_GET['all'] ) && is_numeric( $_GET['all'] ) ) {
    $show_all_ucache_entries = $_GET['all'];
    if ( $show_all_ucache_entries < 0 || $show_all_ucache_entries > 1)
        $show_all_ucache_entries = 0;
}

$clear_user_cache = 0;
if ( isset( $_GET['clc'] ) && is_numeric( $_GET['clc'] ) ) {
    $clear_user_cache = $_GET['clc'];
    if ( $clear_user_cache < 0 || $clear_user_cache > 1)
        $clear_user_cache = 0;
}

$ucache_key = null;
if ( isset( $_GET['key'] ) )
    $ucache_key = $_GET['key'];
// End of input parameters check




// Initialize global variables
$user_cache_available = function_exists('wincache_ucache_info') && !strcmp( ini_get( 'wincache.ucenabled' ), "1" );
$session_cache_available = function_exists('wincache_scache_info') && !strcasecmp( ini_get( 'session.save_handler' ), "wincache" );
$ocache_mem_info = null;
$ocache_file_info = null;
$ocache_summary_info = null;
$fcache_mem_info = null;
$fcache_file_info = null;
$fcache_summary_info = null;
$rpcache_mem_info = null;
$rpcache_file_info = null;
$ucache_mem_info = null;
$ucache_info = null;
$scache_mem_info = null;
$scache_info = null;
$sort_key = null;




// Charts and graphs processing
if ( $img > 0 ) {
    if ( !wincache_gd_loaded() )
        exit( 0 );

    $png_image = null;
    $chart_title = wincache_get_chart_title($img);

    if ( $chart_type == WINCACHE_PIE_CHART ){
        $png_image = wincache_create_used_free_chart( WINCACHE_IMG_WIDTH, WINCACHE_IMG_HEIGHT, $chart_param1, $chart_param2, get_string('memory_chart_title', 'tool_wincache', $chart_title) );
    }
    else{
        $png_image = wincache_create_hit_miss_chart( WINCACHE_IMG_WIDTH, WINCACHE_IMG_HEIGHT,  $chart_param1, $chart_param2, get_string('hitmiss_chart_title', 'tool_wincache', $chart_title) );
    }

    if ( $png_image !== null ) {
        ob_clean(); // Clear our any potential Moodle output
        // flush image
        header('Content-type: image/png');
        imagepng($png_image);
        imagedestroy($png_image);
    }
    exit;
}




// Output Moodle header..
echo $OUTPUT->header();




// WinCache info content..
?>
<div id="wincache_content">
<div id="wincache_header">
    <h1><?php print_string('page_title', 'tool_wincache') ?></h1>
</div>
<div id="wincache_menu">
    <ul>
        <li <?php echo ($page == WINCACHE_SUMMARY_DATA)? 'class="selected"' : ''; ?>><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_SUMMARY_DATA; ?>"><?php print_string('summery_title', 'tool_wincache') ?></a></li>
        <li <?php echo ($page == WINCACHE_OCACHE_DATA)? 'class="selected"' : ''; ?>><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_OCACHE_DATA; ?>"><?php print_string('ocache_title', 'tool_wincache') ?></a></li>
        <li <?php echo ($page == WINCACHE_FCACHE_DATA)? 'class="selected"' : ''; ?>><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_FCACHE_DATA; ?>"><?php print_string('fcache_title', 'tool_wincache') ?></a></li>
        <li <?php echo ($page == WINCACHE_UCACHE_DATA)? 'class="selected"' : ''; ?>><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_UCACHE_DATA; ?>"><?php print_string('ucache_title', 'tool_wincache') ?></a></li>
        <li <?php echo ($page == WINCACHE_SCACHE_DATA)? 'class="selected"' : ''; ?>><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_SCACHE_DATA; ?>"><?php print_string('scache_title', 'tool_wincache') ?></a></li>
        <li <?php echo ($page == WINCACHE_RCACHE_DATA)? 'class="selected"' : ''; ?>><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_RCACHE_DATA; ?>"><?php print_string('rcache_title', 'tool_wincache') ?></a></li>
    </ul>
</div>
<?php if ( $page == WINCACHE_SUMMARY_DATA ) {
    wincache_init_cache_info( WINCACHE_SUMMARY_DATA );
    ?>
    <div class="overview">
        <div class="wideleftpanel">
            <table style="width: 100%">
                <tr>
                    <th colspan="2"><?php print_string('general_info', 'tool_wincache'); ?></th>
                </tr>
                <tr>
                    <td class="e"><?php print_string('wincache_version', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo phpversion('wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('php_version', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo phpversion(); ?></td>
                </tr>
                <tr title="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>">
                    <td class="e"><?php print_string('doc_root', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_get_trimmed_string( $_SERVER['DOCUMENT_ROOT'], WINCACHE_PATH_MAX_LENGTH ); ?></td>
                </tr>
                <tr title="<?php echo isset( $_SERVER['PHPRC'] ) ? $_SERVER['PHPRC'] : get_string('not_defined', 'tool_wincache'); ?>">
                    <td class="e"><?php print_string('phprc', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo isset( $_SERVER['PHPRC'] ) ? wincache_get_trimmed_string( $_SERVER['PHPRC'], WINCACHE_PATH_MAX_LENGTH ) : get_string('not_defined', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('server_software', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE']: get_string('not_dset', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('operating_sys', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo php_uname( 's' ), ' ', php_uname( 'r' ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('processor_info', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo isset( $_SERVER['PROCESSOR_IDENTIFIER'] ) ? $_SERVER['PROCESSOR_IDENTIFIER']: get_string('not_set', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('processor_num', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo isset( $_SERVER['NUMBER_OF_PROCESSORS'] ) ? $_SERVER['NUMBER_OF_PROCESSORS']: get_string('not_set', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('machine_name', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo (getenv( 'COMPUTERNAME' ) != false) ? getenv( 'COMPUTERNAME' ) : get_string('not_set', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('host_name', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : get_string('not_set', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('session_handler', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo ini_get( 'session.save_handler' ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('app_pool_id', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo (getenv( 'APP_POOL_ID' ) != false) ? getenv( 'APP_POOL_ID') : get_string('not_available', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('site_id', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo isset( $_SERVER['INSTANCE_ID'] ) ? $_SERVER['INSTANCE_ID'] : get_string('not_available', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('fcgi_impersonation', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo (ini_get( 'fastcgi.impersonate' ) === '1') ? get_string('enabled', 'tool_wincache') : get_string('disabled', 'tool_wincache'); ?></td>
                </tr>
            </table>
        </div>
        <div class="widerightpanel">
            <table style="width:100%">
                <tr>
                    <th colspan="2"><?php print_string('cache_settings', 'tool_wincache'); ?></th>
                </tr>
                <?php
                foreach ( ini_get_all( 'wincache' ) as $ini_name => $ini_value) {
                    // Do not show the settings used for debugging
                    if ( in_array( $ini_name, $settings_to_hide ) )
                        continue;
                    echo '<tr title="', $ini_value['local_value'], '"><td class="e">', $ini_name, '</td><td class="v">';
                    if ( !is_numeric( $ini_value['local_value'] ) )
                        echo wincache_get_trimmed_ini_value( $ini_value['local_value'], WINCACHE_INI_MAX_LENGTH );
                    else
                        echo $ini_value['local_value'];
                    echo '</td></tr>', "\n";
                }
                ?>
            </table>
        </div>
    </div>
    <div class="overview">
        <div class="leftpanel extra_margin">
            <table style="width:100%">
                <tr>
                    <th colspan="2"><?php print_string('ocache_overview', 'tool_wincache'); ?></th>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cache_scope', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo ( isset( $ocache_file_info['is_local_cache'] ) ) ? wincache_cache_scope_text( $ocache_file_info['is_local_cache'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cache_uptime', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo ( isset( $ocache_file_info['total_cache_uptime'] ) ) ? wincache_seconds_to_words( $ocache_file_info['total_cache_uptime'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cached_files', 'tool_wincache'); ?></td>
                    <td class="v"><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_OCACHE_DATA, '#filelist'; ?>"><?php echo $ocache_file_info['total_file_count']; ?></a></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('hits', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ocache_file_info['total_hit_count']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('misses', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ocache_file_info['total_miss_count']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('total_memory', 'tool_wincache'); ?></td>
                    <?php echo wincache_get_ocache_size_markup( $ocache_mem_info['memory_total'] ); ?>
                </tr>
                <tr>
                    <td class="e"><?php print_string('available_memory', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $ocache_mem_info['memory_free'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('memory_overhead', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $ocache_mem_info['memory_overhead'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('num_of_functions', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ocache_summary_info['total_functions']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('num_of_classes', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ocache_summary_info['total_classes']; ?></td>
                </tr>
            </table>
        </div>
        <div class="rightpanel">
            <?php echo wincache_get_chart_markup( WINCACHE_OCACHE_DATA, WINCACHE_BAR_CHART, $ocache_file_info['total_hit_count'], $ocache_file_info['total_miss_count'] ); ?>
        </div>
        <div class="rightpanel">
            <?php echo wincache_get_chart_markup( WINCACHE_OCACHE_DATA, WINCACHE_PIE_CHART, $ocache_mem_info['memory_total'] - $ocache_mem_info['memory_free'], $ocache_mem_info['memory_free'] ); ?>
        </div>
    </div>
    <div class="overview">
        <div class="leftpanel extra_margin">
            <table style="width: 100%">
                <tr>
                    <th colspan="2"><?php print_string('fcache_overview', 'tool_wincache'); ?></th>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cache_uptime', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo ( isset( $fcache_file_info['total_cache_uptime'] ) ) ? wincache_seconds_to_words( $fcache_file_info['total_cache_uptime'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cached_files', 'tool_wincache'); ?></td>
                    <td class="v"><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_FCACHE_DATA, '#filelist'; ?>"><?php echo $fcache_file_info['total_file_count']; ?></a></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('total_file_size', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $fcache_summary_info['total_size'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('hits', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $fcache_file_info['total_hit_count']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('misses', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $fcache_file_info['total_miss_count']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('total_memory', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $fcache_mem_info['memory_total'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('available_memory', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $fcache_mem_info['memory_free'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('memory_overhead', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $fcache_mem_info['memory_overhead'] ); ?></td>
                </tr>
            </table>
        </div>
        <div class="rightpanel">
            <?php echo wincache_get_chart_markup( WINCACHE_FCACHE_DATA, WINCACHE_BAR_CHART, $fcache_file_info['total_hit_count'], $fcache_file_info['total_miss_count'] ); ?>
        </div>
        <div class="rightpanel">
            <?php echo wincache_get_chart_markup( WINCACHE_FCACHE_DATA, WINCACHE_PIE_CHART, $fcache_mem_info['memory_total'] - $fcache_mem_info['memory_free'], $fcache_mem_info['memory_free'] ); ?>
        </div>
    </div>
    <div class="overview">
        <?php if ( $user_cache_available ) {?>
            <div class="leftpanel extra_margin">
                <table style="width: 100%">
                    <tr>
                        <th colspan="2"><?php print_string('ucache_overview', 'tool_wincache'); ?></th>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cache_scope', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo ( isset( $ucache_info['is_local_cache'] ) ) ? wincache_cache_scope_text( $ucache_info['is_local_cache'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cache_uptime', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo ( isset( $ucache_info['total_cache_uptime'] ) ) ? wincache_seconds_to_words( $ucache_info['total_cache_uptime'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cached_entries', 'tool_wincache'); ?></td>
                        <td class="v"><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_UCACHE_DATA, '#filelist'; ?>"><?php echo $ucache_info['total_item_count']; ?></a></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('hits', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo $ucache_info['total_hit_count']; ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('misses', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo $ucache_info['total_miss_count']; ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('total_memory', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $ucache_mem_info['memory_total'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('available_memory', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $ucache_mem_info['memory_free'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('memory_overhead', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $ucache_mem_info['memory_overhead'] ); ?></td>
                    </tr>
                </table>
            </div>
            <div class="rightpanel">
                <?php echo wincache_get_chart_markup( WINCACHE_UCACHE_DATA, WINCACHE_BAR_CHART, $ucache_info['total_hit_count'], $ucache_info['total_miss_count'] ); ?>
            </div>
            <div class="rightpanel">
                <?php echo wincache_get_chart_markup( WINCACHE_UCACHE_DATA, WINCACHE_PIE_CHART, $ucache_mem_info['memory_total'] - $ucache_mem_info['memory_free'], $ucache_mem_info['memory_free'] ); ?>
            </div>
        <?php } ?>
    </div>
    <div class="overview">
        <?php if ( $session_cache_available ) {?>
            <div class="leftpanel extra_margin">
                <table style="width: 100%">
                    <tr>
                        <th colspan="2"><?php print_string('scache_overview', 'tool_wincache'); ?></th>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cache_scope', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo ( isset( $scache_info['is_local_cache'] ) ) ? wincache_cache_scope_text( $scache_info['is_local_cache'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cache_uptime', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo ( isset( $scache_info['total_cache_uptime'] ) ) ? wincache_seconds_to_words( $scache_info['total_cache_uptime'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cached_entries', 'tool_wincache'); ?></td>
                        <td class="v"><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_SCACHE_DATA, '#filelist'; ?>"><?php echo $scache_info['total_item_count']; ?></a></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('hits', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo $scache_info['total_hit_count']; ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('misses', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo $scache_info['total_miss_count']; ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('total_memory', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $scache_mem_info['memory_total'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('available_memory', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $scache_mem_info['memory_free'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('memory_overhead', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $scache_mem_info['memory_overhead'] ); ?></td>
                    </tr>
                </table>
            </div>
            <div class="rightpanel">
                <?php echo wincache_get_chart_markup( WINCACHE_SCACHE_DATA, WINCACHE_BAR_CHART, $scache_info['total_hit_count'], $scache_info['total_miss_count'] ); ?>
            </div>
            <div class="rightpanel">
                <?php echo wincache_get_chart_markup( WINCACHE_SCACHE_DATA, WINCACHE_PIE_CHART, $scache_mem_info['memory_total'] - $scache_mem_info['memory_free'], $scache_mem_info['memory_free'] ); ?>
            </div>
        <?php } ?>
    </div>
    <div class="overview">
        <div class="leftpanel">
            <table style="width: 100%">
                <tr>
                    <th colspan="2"><?php print_string('rcache_overview', 'tool_wincache'); ?></th>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cached_entries', 'tool_wincache'); ?></td>
                    <td class="v"><a href="<?php echo $PHP_SELF, '?page=', WINCACHE_RCACHE_DATA, '#filelist'; ?>"><?php echo $rpcache_file_info['total_file_count']; ?></a></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('total_memory', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $rpcache_mem_info['memory_total'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('available_memory', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $rpcache_mem_info['memory_free'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('memory_overhead', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $rpcache_mem_info['memory_overhead'] ); ?></td>
                </tr>
            </table>
        </div>
    </div>
<?php } else if ( $page == WINCACHE_OCACHE_DATA )  {
    wincache_init_cache_info( WINCACHE_OCACHE_DATA );
    ?>
    <div class="overview">
        <div class="leftpanel extra_margin">
            <table style="width:100%">
                <tr>
                    <th colspan="2"><?php print_string('ocache_overview', 'tool_wincache'); ?></th>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cache_scope', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo ( isset( $ocache_file_info['is_local_cache'] ) ) ? wincache_cache_scope_text( $ocache_file_info['is_local_cache'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cache_uptime', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo ( isset( $ocache_file_info['total_cache_uptime'] ) ) ? wincache_seconds_to_words( $ocache_file_info['total_cache_uptime'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cached_files', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ocache_file_info['total_file_count']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('hits', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ocache_file_info['total_hit_count']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('misses', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ocache_file_info['total_miss_count']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('total_memory', 'tool_wincache'); ?></td>
                    <?php echo wincache_get_ocache_size_markup( $ocache_mem_info['memory_total'] ); ?>
                </tr>
                <tr>
                    <td class="e"><?php print_string('available_memory', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $ocache_mem_info['memory_free'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('memory_overhead', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $ocache_mem_info['memory_overhead'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('num_of_functions', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ocache_summary_info['total_functions']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('num_of_classes', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ocache_summary_info['total_classes']; ?></td>
                </tr>
            </table>
        </div>
        <div class="rightpanel">
            <?php echo wincache_get_chart_markup( WINCACHE_OCACHE_DATA, WINCACHE_BAR_CHART, $ocache_file_info['total_hit_count'], $ocache_file_info['total_miss_count'] ); ?>
        </div>
        <div class="rightpanel">
            <?php echo wincache_get_chart_markup( WINCACHE_OCACHE_DATA, WINCACHE_PIE_CHART, $ocache_mem_info['memory_total'] - $ocache_mem_info['memory_free'], $ocache_mem_info['memory_free'] ); ?>
        </div>
    </div>
    <div class="list" id="wincache_filelist">
        <table style="width:100%">
            <tr>
                <th colspan="7"><?php print_string('ocache_entries', 'tool_wincache'); ?></th>
            </tr>
            <tr>
                <th title="<?php print_string('file_name_desc', 'tool_wincache'); ?>"><?php print_string('file_name', 'tool_wincache'); ?></th>
                <th title="<?php print_string('function_count_desc', 'tool_wincache'); ?>"><?php print_string('function_count', 'tool_wincache'); ?></th>
                <th title="<?php print_string('class_count_desc', 'tool_wincache'); ?>"><?php print_string('class_count', 'tool_wincache'); ?></th>
                <th title="<?php print_string('add_time_desc', 'tool_wincache'); ?>"><?php print_string('add_time', 'tool_wincache'); ?></th>
                <th title="<?php print_string('use_time_desc', 'tool_wincache'); ?>"><?php print_string('use_time', 'tool_wincache'); ?></th>
                <th title="<?php print_string('last_check_desc', 'tool_wincache'); ?>"><?php print_string('last_check', 'tool_wincache'); ?></th>
                <th title="<?php print_string('hit_count_desc', 'tool_wincache'); ?>"><?php print_string('hit_count', 'tool_wincache'); ?></th>
            </tr>
            <?php
            $sort_key = 'file_name';
            usort( $ocache_file_info['file_entries'], 'wincache_cmp');
            foreach ( $ocache_file_info['file_entries'] as $entry ) {
                echo '<tr title="', $entry['file_name'] ,'">', "\n";
                echo '<td class="e">', wincache_get_trimmed_filename( $entry['file_name'], WINCACHE_PATH_MAX_LENGTH ),'</td>', "\n";
                echo '<td class="v">', $entry['function_count'],'</td>', "\n";
                echo '<td class="v">', $entry['class_count'],'</td>', "\n";
                echo '<td class="v">', $entry['add_time'],'</td>', "\n";
                echo '<td class="v">', $entry['use_time'],'</td>', "\n";
                echo '<td class="v">', $entry['last_check'],'</td>', "\n";
                echo '<td class="v">', $entry['hit_count'],'</td>', "\n";
                echo "</tr>\n";
            }
            ?>
        </table>
    </div>
<?php } else if ( $page == WINCACHE_FCACHE_DATA ) {
    wincache_init_cache_info( WINCACHE_FCACHE_DATA );
    ?>
    <div class="overview">
        <div class="leftpanel extra_margin">
            <table style="width: 100%">
                <tr>
                    <th colspan="2"><?php print_string('fcache_overview', 'tool_wincache'); ?></th>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cache_uptime', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo ( isset( $fcache_file_info['total_cache_uptime'] ) ) ? wincache_seconds_to_words( $fcache_file_info['total_cache_uptime'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cached_files', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $fcache_file_info['total_file_count']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('total_file_size', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $fcache_summary_info['total_size'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('hits', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $fcache_file_info['total_hit_count']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('misses', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $fcache_file_info['total_miss_count']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('total_memory', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $fcache_mem_info['memory_total'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('available_memory', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $fcache_mem_info['memory_free'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('memory_overhead', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $fcache_mem_info['memory_overhead'] ); ?></td>
                </tr>
            </table>
        </div>
        <div class="rightpanel">
            <?php echo wincache_get_chart_markup( WINCACHE_FCACHE_DATA, WINCACHE_BAR_CHART, $fcache_file_info['total_hit_count'], $fcache_file_info['total_miss_count'] ); ?>
        </div>
        <div class="rightpanel">
            <?php echo wincache_get_chart_markup( WINCACHE_FCACHE_DATA, WINCACHE_PIE_CHART, $fcache_mem_info['memory_total'] - $fcache_mem_info['memory_free'], $fcache_mem_info['memory_free'] ); ?>
        </div>
    </div>
    <div class="list" id="wincache_filelist">
        <table style="width:100%">
            <tr>
                <th colspan="6"><?php print_string('fcache_entries', 'tool_wincache'); ?></th>
            </tr>
            <tr>
                <th title="<?php print_string('file_name_desc', 'tool_wincache'); ?>"><?php print_string('file_name', 'tool_wincache'); ?></th>
                <th title="<?php print_string('file_size_desc', 'tool_wincache'); ?>"><?php print_string('file_size', 'tool_wincache'); ?></th>
                <th title="<?php print_string('add_time_desc', 'tool_wincache'); ?>"><?php print_string('add_time', 'tool_wincache'); ?></th>
                <th title="<?php print_string('use_time_desc', 'tool_wincache'); ?>"><?php print_string('use_time', 'tool_wincache'); ?></th>
                <th title="<?php print_string('last_check_desc', 'tool_wincache'); ?>"><?php print_string('last_check', 'tool_wincache'); ?></th>
                <th title="<?php print_string('hit_count_desc', 'tool_wincache'); ?>"><?php print_string('hit_count', 'tool_wincache'); ?></th>
            </tr>
            <?php
            $sort_key = 'file_name';
            usort( $fcache_file_info['file_entries'], 'wincache_cmp');
            foreach ( $fcache_file_info['file_entries'] as $entry ) {
                echo '<tr title="', $entry['file_name'] ,'">', "\n";
                echo '<td class="e">', wincache_get_trimmed_filename( $entry['file_name'], WINCACHE_PATH_MAX_LENGTH ),'</td>', "\n";
                echo '<td class="v">', wincache_convert_bytes_to_string( $entry['file_size'] ),'</td>', "\n";
                echo '<td class="v">', $entry['add_time'],'</td>', "\n";
                echo '<td class="v">', $entry['use_time'],'</td>', "\n";
                echo '<td class="v">', $entry['last_check'],'</td>', "\n";
                echo '<td class="v">', $entry['hit_count'],'</td>', "\n";
                echo "</tr>\n";
            }
            ?>
        </table>
    </div>
<?php } else if ( $page == WINCACHE_UCACHE_DATA && $ucache_key == null ) {
    if ( $user_cache_available ) {
        wincache_init_cache_info( WINCACHE_UCACHE_DATA );
        ?>
        <div class="overview">
            <div class="leftpanel extra_margin">
                <table style="width: 100%">
                    <tr>
                        <th colspan="2"><?php print_string('ucache_overview', 'tool_wincache'); ?></th>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cache_scope', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo ( isset( $ucache_info['is_local_cache'] ) ) ? wincache_cache_scope_text( $ucache_info['is_local_cache'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cache_uptime', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo ( isset( $ucache_info['total_cache_uptime'] ) ) ? wincache_seconds_to_words( $ucache_info['total_cache_uptime'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cached_entries', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo $ucache_info['total_item_count']; ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('hits', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo $ucache_info['total_hit_count']; ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('misses', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo $ucache_info['total_miss_count']; ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('total_memory', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $ucache_mem_info['memory_total'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('available_memory', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $ucache_mem_info['memory_free'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('memory_overhead', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $ucache_mem_info['memory_overhead'] ); ?></td>
                    </tr>
                </table>
            </div>
            <div class="rightpanel">
                <?php echo wincache_get_chart_markup( WINCACHE_UCACHE_DATA, WINCACHE_BAR_CHART, $ucache_info['total_hit_count'], $ucache_info['total_miss_count'] ); ?>
            </div>
            <div class="rightpanel">
                <?php echo wincache_get_chart_markup( WINCACHE_UCACHE_DATA, WINCACHE_PIE_CHART, $ucache_mem_info['memory_total'] - $ucache_mem_info['memory_free'], $ucache_mem_info['memory_free'] ); ?>
            </div>
        </div>
        <div class="list" id="wincache_filelist">
            <table style="width:100%">
                <tr>
                    <th colspan="6"><?php print_string('ucache_entries', 'tool_wincache'); ?></th>
                </tr>
                <tr>
                    <th title="<?php print_string('key_name_desc', 'tool_wincache'); ?>"><?php print_string('key_name', 'tool_wincache'); ?></th>
                    <th title="<?php print_string('value_type_desc', 'tool_wincache'); ?>"><?php print_string('value_type', 'tool_wincache'); ?></th>
                    <th title="<?php print_string('value_size_desc', 'tool_wincache'); ?>"><?php print_string('value_size', 'tool_wincache'); ?></th>
                    <th title="<?php print_string('total_ttl_desc', 'tool_wincache'); ?>"><?php print_string('total_ttl', 'tool_wincache'); ?></th>
                    <th title="<?php print_string('total_age_desc', 'tool_wincache'); ?>"><?php print_string('total_age', 'tool_wincache'); ?></th>
                    <th title="<?php print_string('hit_count_desc', 'tool_wincache'); ?>"><?php print_string('hit_count', 'tool_wincache'); ?></th>
                </tr>
                <?php
                $count = 0;
                foreach ( $ucache_info['ucache_entries'] as $entry ) {
                    echo '<tr title="', $entry['key_name'] ,'">', "\n";
                    echo '<td class="e"><a href="', $PHP_SELF, '?page=', WINCACHE_UCACHE_DATA, '&key=', urlencode( $entry['key_name'] ), '">', wincache_get_trimmed_string( $entry['key_name'], WINCACHE_PATH_MAX_LENGTH ),'</a></td>', "\n";
                    echo '<td class="v">', $entry['value_type'], '</td>', "\n";
                    echo '<td class="v">', wincache_convert_bytes_to_string( $entry['value_size']), '</td>', "\n";
                    echo '<td class="v">', $entry['ttl_seconds'],'</td>', "\n";
                    echo '<td class="v">', $entry['age_seconds'],'</td>', "\n";
                    echo '<td class="v">', $entry['hitcount'],'</td>', "\n";
                    echo "</tr>\n";
                    if ($count++ > WINCACHE_CACHE_MAX_ENTRY && !$show_all_ucache_entries){
                        echo '<tr><td colspan="6"><a href="', $PHP_SELF, '?page=', WINCACHE_UCACHE_DATA, '&amp;all=1">', get_string('show_all_entries', 'tool_wincache'), '</td></tr>';
                        break;
                    }
                }
                ?>
            </table>
        </div>
    <?php } else { ?>
        <div class="overview">
            <p class="notice"><?php print_string('ucache_unavailable', 'tool_wincache'); ?></p>
        </div>
    <?php }?>
<?php } else if ( $page == WINCACHE_UCACHE_DATA && $ucache_key != null ) {
    if ( !wincache_ucache_exists( $ucache_key ) ){
        ?>
        <div class="overview">
            <p class="notice"><?php print_string('key_nonexistent', 'tool_wincache'); ?></p>
        </div>
    <?php       }
    else{
        $ucache_entry_info = wincache_ucache_info( true, $ucache_key );
        ?>
        <div class="list">
            <table style="width:60%">
                <tr>
                    <th colspan="2"><?php print_string('ucache_entry_info', 'tool_wincache'); ?></th>
                </tr>
                <tr>
                    <td class="e"><?php print_string('key', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ucache_entry_info['ucache_entries'][1]['key_name']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('value_type', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ucache_entry_info['ucache_entries'][1]['value_type']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('size', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $ucache_entry_info['ucache_entries'][1]['value_size'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('total_ttl_sec', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ucache_entry_info['ucache_entries'][1]['ttl_seconds']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('total_age_sec', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ucache_entry_info['ucache_entries'][1]['age_seconds']; ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('hit_count', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $ucache_entry_info['ucache_entries'][1]['hitcount']; ?></td>
                </tr>
            </table>
        </div>
        <div id="wincache_panel">
            <div id="wincache_panel_header">
                <?php print_string('ucache_content', 'tool_wincache'); ?>
            </div>
            <div id="wincache_panel_body">
                <pre><?php echo htmlentities(print_r( wincache_ucache_get( $ucache_key ), true )) ?></pre>
            </div>
        </div>
    <?php }?>
<?php } else if ( $page == WINCACHE_SCACHE_DATA ) {
    if ( $session_cache_available ) {
        wincache_init_cache_info( WINCACHE_SCACHE_DATA );
        ?>
        <div class="overview">
            <div class="leftpanel extra_margin">
                <table style="width: 100%">
                    <tr>
                        <th colspan="2"><?php print_string('scache_overview', 'tool_wincache'); ?></th>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cache_scope', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo ( isset( $scache_info['is_local_cache'] ) ) ? wincache_cache_scope_text( $scache_info['is_local_cache'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cache_uptime', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo ( isset( $scache_info['total_cache_uptime'] ) ) ? wincache_seconds_to_words( $scache_info['total_cache_uptime'] ) : get_string('unknown', 'tool_wincache'); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('cached_entries', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo $scache_info['total_item_count']; ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('hits', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo $scache_info['total_hit_count']; ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('misses', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo $scache_info['total_miss_count']; ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('total_memory', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $scache_mem_info['memory_total'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('available_memory', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $scache_mem_info['memory_free'] ); ?></td>
                    </tr>
                    <tr>
                        <td class="e"><?php print_string('memory_overhead', 'tool_wincache'); ?></td>
                        <td class="v"><?php echo wincache_convert_bytes_to_string( $scache_mem_info['memory_overhead'] ); ?></td>
                    </tr>
                </table>
            </div>
            <div class="rightpanel">
                <?php echo wincache_get_chart_markup( WINCACHE_SCACHE_DATA, WINCACHE_BAR_CHART, $scache_info['total_hit_count'], $scache_info['total_miss_count'] ); ?>
            </div>
            <div class="rightpanel">
                <?php echo wincache_get_chart_markup( WINCACHE_SCACHE_DATA, WINCACHE_PIE_CHART, $scache_mem_info['memory_total'] - $scache_mem_info['memory_free'], $scache_mem_info['memory_free'] ); ?>
            </div>
        </div>
        <div class="list" id="wincache_sessionlist">
            <table style="width:100%">
                <tr>
                    <th colspan="6"><?php print_string('scache_entries', 'tool_wincache'); ?></th>
                </tr>
                <tr>
                    <th title="<?php print_string('key_name_desc', 'tool_wincache'); ?>"><?php print_string('key_name', 'tool_wincache'); ?></th>
                    <th title="<?php print_string('value_type_desc', 'tool_wincache'); ?>"><?php print_string('value_type', 'tool_wincache'); ?></th>
                    <th title="<?php print_string('value_size_desc', 'tool_wincache'); ?>"><?php print_string('value_size', 'tool_wincache'); ?></th>
                    <th title="<?php print_string('total_ttl_desc', 'tool_wincache'); ?>"><?php print_string('total_ttl', 'tool_wincache'); ?></th>
                    <th title="<?php print_string('total_age_desc', 'tool_wincache'); ?>"><?php print_string('total_age', 'tool_wincache'); ?></th>
                    <th title="<?php print_string('hit_count_desc', 'tool_wincache'); ?>"><?php print_string('hit_count', 'tool_wincache'); ?></th>
                </tr>
                <?php
                $count = 0;
                foreach ( $scache_info['scache_entries'] as $entry ) {
                    echo '<tr title="', $entry['key_name'] ,'">', "\n";
                    echo '<td class="e">', wincache_get_trimmed_string( $entry['key_name'], WINCACHE_PATH_MAX_LENGTH ),'</td>', "\n";
                    echo '<td class="v">', $entry['value_type'], '</td>', "\n";
                    echo '<td class="v">', wincache_convert_bytes_to_string( $entry['value_size'] ), '</td>', "\n";
                    echo '<td class="v">', $entry['ttl_seconds'],'</td>', "\n";
                    echo '<td class="v">', $entry['age_seconds'],'</td>', "\n";
                    echo '<td class="v">', $entry['hitcount'],'</td>', "\n";
                    echo "</tr>\n";
                    if ($count++ > WINCACHE_CACHE_MAX_ENTRY && !$show_all_ucache_entries){
                        echo '<tr><td colspan="6"><a href="', $PHP_SELF, '?page=', WINCACHE_SCACHE_DATA, '&amp;all=1">', get_string('show_all_entries', 'tool_wincache'), '</td></tr>';
                        break;
                    }
                }
                ?>
            </table>
        </div>
    <?php } else { ?>
        <div class="overview">
            <p class="notice"><?php print_string('scache_unavailable', 'tool_wincache'); ?></p>
        </div>
    <?php }?>
<?php } else if ( $page == WINCACHE_RCACHE_DATA ) {
    wincache_init_cache_info( WINCACHE_RCACHE_DATA );
    ?>
    <div class="overview">
        <div class="wideleftpanel">
            <table style="width: 100%">
                <tr>
                    <th colspan="2"><?php print_string('rcache_overview', 'tool_wincache'); ?></th>
                </tr>
                <tr>
                    <td class="e"><?php print_string('cached_entries', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo $rpcache_file_info['total_file_count'] ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('total_memory', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $rpcache_mem_info['memory_total'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('available_memory', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $rpcache_mem_info['memory_free'] ); ?></td>
                </tr>
                <tr>
                    <td class="e"><?php print_string('memory_overhead', 'tool_wincache'); ?></td>
                    <td class="v"><?php echo wincache_convert_bytes_to_string( $rpcache_mem_info['memory_overhead'] ); ?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="list" id="wincache_filelist">
        <table style="width:100%">
            <tr>
                <th colspan="2"><?php print_string('rcache_entries', 'tool_wincache'); ?></th>
            </tr>
            <tr>
                <th><?php print_string('resolve_path', 'tool_wincache'); ?></th>
                <th><?php print_string('subkey_data', 'tool_wincache'); ?></th>
            </tr>
            <?php
            $sort_key = 'resolve_path';
            usort( $rpcache_file_info['rplist_entries'], 'wincache_cmp');
            foreach ( $rpcache_file_info['rplist_entries'] as $entry ) {
                echo '<tr title="',$entry['subkey_data'], '">', "\n";
                echo '<td class="e">', wincache_get_trimmed_string( $entry['resolve_path'], WINCACHE_PATH_MAX_LENGTH ),'</td>', "\n";
                echo '<td class="v">', wincache_get_trimmed_string( $entry['subkey_data'], WINCACHE_SUBKEY_MAX_LENGTH ), '</td>', "\n";
                echo "</tr>\n";
            }
            ?>
        </table>
    </div>
<?php } ?>
<div class="clear"></div>
</div>
<?php




// Output Moodle footer..
echo $OUTPUT->footer();
