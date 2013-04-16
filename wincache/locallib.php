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
 * WinCache statistics functions
 *
 * @package   tool_wincache
 * @copyright 2013 Ryan Panning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function wincache_cmp($a, $b) {
    global $sort_key;
    if ( $sort_key == 'file_name' )
        return strcmp( wincache_get_trimmed_filename( $a[$sort_key], WINCACHE_PATH_MAX_LENGTH ), wincache_get_trimmed_filename( $b[$sort_key], WINCACHE_PATH_MAX_LENGTH ) );
    else if ( $sort_key == 'resolve_path' )
        return strcmp( wincache_get_trimmed_string( $a[$sort_key], WINCACHE_PATH_MAX_LENGTH ), wincache_get_trimmed_string( $b[$sort_key], WINCACHE_PATH_MAX_LENGTH ) );
    else
        return 0;
}

function wincache_convert_bytes_to_string( $bytes ) {
    $units = array( 0 => 'B', 1 => 'kB', 2 => 'MB', 3 => 'GB' );
    $log = log( $bytes, 1024 );
    $power = (int) $log;
    $size = pow(1024, $log - $power);
    return round($size, 2) . ' ' . $units[$power];
}

function wincache_seconds_to_words( $seconds ) {
    /*** return value ***/
    $ret = "";

    /*** get the hours ***/
    $hours = intval(intval( $seconds ) / 3600);
    if ( $hours > 0 ) {
        $ret .= get_string('hours', 'tool_wincache', $hours);
    }
    /*** get the minutes ***/
    $minutes = bcmod( ( intval( $seconds ) / 60 ), 60 );
    if( $hours > 0 || $minutes > 0 ) {
        $ret .= get_string('minutes', 'tool_wincache', $minutes);
    }

    /*** get the seconds ***/
    $seconds = bcmod( intval( $seconds ), 60 );
    $ret .= get_string('seconds', 'tool_wincache', $seconds);

    return $ret;
}

function wincache_get_trimmed_filename( $filepath, $max_len ) {
    if ($max_len <= 0) die ('The maximum allowed length must be bigger than 0');

    $result = basename( $filepath );
    if ( strlen( $result ) > $max_len )
        $result = substr( $result, -1 * $max_len );

    return $result;
}

function wincache_get_trimmed_string( $input, $max_len ) {
    if ($max_len <= 3) die ('The maximum allowed length must be bigger than 3');

    $result = $input;
    if ( strlen( $result ) > $max_len )
        $result = substr( $result, 0, $max_len - 3 ). '...';

    return $result;
}

function wincache_get_trimmed_ini_value( $input, $max_len, $separators = array('|', ',') ) {
    if ($max_len <= 3) die ('The maximum allowed length must be bigger than 3');

    $result = $input;
    $lastindex = 0;
    if ( strlen( $result ) > $max_len ) {
        $result = substr( $result, 0, $max_len - 3 ).'...';
        if ( !is_array( $separators ) ) die( 'The separators must be in an array' );
        foreach ( $separators as $separator ) {
            $index = strripos( $result, $separator );
            if ( $index !== false  && $index > $lastindex )
                $lastindex = $index;
        }
        if ( 0 < $lastindex && $lastindex < ( $max_len - 3 ) )
            $result = substr( $result, 0, $lastindex + 1 ).'...';
    }
    return $result;
}

function wincache_get_ocache_summary( $entries ) {
    $result = array();
    $result['total_classes'] = 0;
    $result['total_functions'] = 0;
    $result['oldest_entry'] = '';
    $result['recent_entry'] = '';

    if ( isset( $entries ) && count( $entries ) > 0 && isset( $entries[1]['file_name'] ) ) {
        foreach ( (array)$entries as $entry ) {
            $result['total_classes'] += $entry['class_count'];
            $result['total_functions'] += $entry['function_count'];
        }
    }
    return $result;
}

function wincache_get_fcache_summary( $entries ) {
    $result = array();
    $result['total_size'] = 0;
    $result['oldest_entry'] = '';
    $result['recent_entry'] = '';

    if ( isset( $entries ) && count( $entries ) > 0 && isset( $entries[1]['file_name'] ) ) {
        foreach ( (array)$entries as $entry ) {
            $result['total_size'] += $entry['file_size'];
        }
    }
    return $result;
}

function wincache_get_ocache_size_markup( $size ) {
    $size_string = wincache_convert_bytes_to_string( $size );

    if ( $size > ( ini_get( 'wincache.ocachesize' ) * pow( 1024, 2 ) ) ) {
        return '<td class="n" title="' . get_string('ocache_size_increased', 'tool_wincache') . '">'.$size_string.'</td>';
    }

    return '<td class="v">'.$size_string.'</td>';
}

function wincache_get_chart_title( $chart_data ) {
    $chart_title = '';
    switch( $chart_data ) {
        case WINCACHE_OCACHE_DATA: {
            $chart_title = get_string('ocache_chart', 'tool_wincache');
            break;
        }
        case WINCACHE_FCACHE_DATA: {
            $chart_title = get_string('fcache_chart', 'tool_wincache');
            break;
        }
        case WINCACHE_UCACHE_DATA: {
            $chart_title = get_string('ucache_chart', 'tool_wincache');
            break;
        }
        case WINCACHE_SCACHE_DATA: {
            $chart_title = get_string('scache_chart', 'tool_wincache');
        }
    }
    return $chart_title;
}

function wincache_gd_loaded() {
    return extension_loaded( 'gd' );
}

function wincache_create_hit_miss_chart( $width, $height, $hits, $misses, $title = null ) {

    if ($title == null) {
        $title = get_string('hitmiss_chart_default', 'tool_wincache');
    }

    $hit_percent = 0;
    $miss_percent = 0;
    if ( $hits < 0 ) $hits = 0;
    if ( $misses < 0 ) $misses = 0;
    if ( $hits > 0 || $misses > 0 ) {
        $hit_percent = round( $hits / ( $hits + $misses ) * 100, 2 );
        $miss_percent = round( $misses / ( $hits + $misses ) * 100, 2 );
    }
    $data = array( get_string('hits', 'tool_wincache') => $hit_percent, get_string('misses', 'tool_wincache') => $miss_percent );

    $image = imagecreate( $width, $height );

    // colors
    $white = imagecolorallocate( $image, 0xFF, 0xFF, 0xFF );
    $phpblue = imagecolorallocate( $image, 0x5C, 0x87, 0xB2 );
    $black = imagecolorallocate( $image, 0x00, 0x00, 0x00 );
    $gray = imagecolorallocate( $image, 0xC0, 0xC0, 0xC0 );

    $maxval = max( $data );
    $nval = sizeof( $data );

    // draw something here
    $hmargin = 38; // left horizontal margin for y-labels
    $vmargin = 20; // top (bottom) vertical margin for title (x-labels)

    $base = floor( ( $width - $hmargin ) / $nval );

    $xsize = $nval * $base - 1; // x-size of plot
    $ysize = $height - 2 * $vmargin; // y-size of plot

    // plot frame
    imagerectangle( $image, $hmargin, $vmargin, $hmargin + $xsize, $vmargin + $ysize, $black );

    // top label
    $titlefont = 3;
    $txtsize = imagefontwidth( $titlefont ) * strlen( $title );
    $xpos = (int)( $hmargin + ( $xsize - $txtsize ) / 2 );
    $xpos = max( 1, $xpos ); // force positive coordinates
    $ypos = 3; // distance from top
    imagestring( $image, $titlefont, $xpos, $ypos, $title , $black );

    // grid lines
    $labelfont = 2;
    $ngrid = 4;

    $dydat = 100 / $ngrid;
    $dypix = $ysize / $ngrid;

    for ( $i = 0; $i <= $ngrid; $i++ ) {
        $ydat = (int)( $i * $dydat );
        $ypos = $vmargin + $ysize - (int)( $i * $dypix );

        $txtsize = imagefontwidth( $labelfont ) * strlen( $ydat );
        $txtheight = imagefontheight( $labelfont );

        $xpos = (int)( ( $hmargin - $txtsize) / 2 );
        $xpos = max( 1, $xpos );

        imagestring( $image, $labelfont, $xpos, $ypos - (int)( $txtheight/2 ), $ydat, $black );

        if ( !( $i == 0 ) && !( $i >= $ngrid ) )
            imageline( $image, $hmargin - 3, $ypos, $hmargin + $xsize, $ypos, $gray );
        // don't draw at Y=0 and top
    }

    // graph bars
    // columns and x labels
    $padding = 30; // half of spacing between columns
    $yscale = $ysize / ( $ngrid * $dydat ); // pixels per data unit

    for ( $i = 0; list( $xval, $yval ) = each( $data ); $i++ ) {

        // vertical columns
        $ymax = $vmargin + $ysize;
        $ymin = $ymax - (int)( $yval * $yscale );
        $xmax = $hmargin + ( $i + 1 ) * $base - $padding;
        $xmin = $hmargin + $i * $base + $padding;

        imagefilledrectangle( $image, $xmin, $ymin, $xmax, $ymax, $phpblue );

        // x labels
        $xlabel = $xval.': '.$yval.'%';
        $txtsize = imagefontwidth( $labelfont ) * strlen( $xlabel );

        $xpos = ( $xmin + $xmax - $txtsize ) / 2;
        $xpos = max( $xmin, $xpos );
        $ypos = $ymax + 3; // distance from x axis

        imagestring( $image, $labelfont, $xpos, $ypos, $xlabel, $black );
    }
    return $image;
}

function wincache_create_used_free_chart( $width, $height, $used_memory, $free_memory, $title = null ) {

    if ($title == null) {
        $title = get_string('memory_chart_default', 'tool_wincache');
    }

    // Check the input parameters to avoid division by zero and weird cases
    if ( $free_memory <= 0 && $used_memory <= 0 ) {
        $free_memory = 1;
        $used_memory = 0;
    }

    $centerX = 120;
    $centerY = 120;
    $diameter = 120;

    $hmargin = 5; // left (right) horizontal margin
    $vmargin = 20; // top (bottom) vertical margin

    $image = imagecreate( $width, $height );

    // colors
    $white = imagecolorallocate( $image, 0xFF, 0xFF, 0xFF );
    $black = imagecolorallocate( $image, 0x00, 0x00, 0x00 );
    $pie_color[1] = imagecolorallocate($image, 0x5C, 0x87, 0xB2);
    $pie_color[2] = imagecolorallocate($image, 0xCB, 0xE1, 0xEF);
    $pie_color[3] = imagecolorallocate($image, 0xC0, 0xC0, 0xC0);

    // Label font size
    $labelfont = 2;
    $hfw = imagefontwidth( $labelfont );
    $vfw = imagefontheight( $labelfont );

    // Border
    imagerectangle( $image, $hmargin, $vmargin, $width - $hmargin, $height - $vmargin, $black );

    // Top label
    $titlefont = 3;
    $txtsize = imagefontwidth( $titlefont ) * strlen( $title );
    $hpos = (int)( ($width - $txtsize) / 2 );
    $vpos = 3; // distance from top
    imagestring( $image, $titlefont, $hpos, $vpos, $title , $black );

    $total = 0;
    $n = 0;
    $items = array(get_string('used_memory', 'tool_wincache') => $used_memory, get_string('free_memory', 'tool_wincache') => $free_memory);

    //read the arguments into different arrays:
    foreach( $items as $key => $val ) {
        $n++;
        $label[$n] = $key;
        $value[$n] = $val;
        $total += $val;
        $arc_dec[$n] = $total*360;
        $arc_rad[$n] = $total*2*pi();
    }

    //the base:
    $arc_rad[0] = 0;
    $arc_dec[0] = 0;

    //count the labels:
    for ( $i = 1; $i <= $n; $i++ ) {

        //calculate the percents:
        $perc[$i] = $value[$i] / $total;
        $percstr[$i] = (string) number_format( $perc[$i] * 100, 2 )."%";
        //label with percentage:
        $label[$i] = $percstr[$i];

        //calculate the arc and line positions:
        $arc_rad[$i] = $arc_rad[$i] / $total;
        $arc_dec[$i] = $arc_dec[$i] / $total;
        $hpos = round( $centerX + ( $diameter / 2 ) * sin( $arc_rad[$i] ) );
        $vpos = round( $centerY + ( $diameter / 2 ) * cos( $arc_rad[$i] ) );
        imageline( $image, $centerX, $centerY, $hpos, $vpos, $black );
        imagearc( $image, $centerX, $centerY, $diameter, $diameter, $arc_dec[$i-1], $arc_dec[$i], $black );

        //calculate the positions for the labels:
        $arc_rad_label = $arc_rad[$i-1] + 0.5 * $perc[$i] * 2 * pi();
        $hpos = $centerX + 1.1 * ( $diameter / 2 ) * sin( $arc_rad_label );
        $vpos = $centerY + 1.1 * ( $diameter / 2 ) * cos( $arc_rad_label );
        if ( ( $arc_rad_label > 0.5 * pi() ) && ( $arc_rad_label < 1.5 * pi() ) ) {
            $vpos = $vpos - $vfw;
        }
        if ( $arc_rad_label > pi() ) {
            $hpos = $hpos - $hfw * strlen( $label[$i] );
        }
        //display the labels:
        imagestring($image, $labelfont, $hpos, $vpos, $label[$i], $black);
    }

    //fill the parts with their colors:
    for ( $i = 1; $i <= $n; $i++ ) {
        if ( round($arc_dec[$i] - $arc_dec[$i-1]) != 0 ) {
            $arc_rad_label = $arc_rad[$i - 1] + 0.5 * $perc[$i] * 2 * pi();
            $hpos = $centerX + 0.8 * ( $diameter / 2 ) * sin( $arc_rad_label );
            $vpos = $centerY + 0.8 * ( $diameter / 2 ) * cos( $arc_rad_label );
            imagefilltoborder( $image, $hpos, $vpos, $black, $pie_color[$i] );
        }
    }

    // legend
    $hpos = $centerX + 1.1 * ($diameter / 2) + $hfw * strlen( '50.00%' );
    $vpos = $centerY - ($diameter / 2);
    $i = 1;
    $thumb_size = 5;
    foreach ($items as $key => $value){
        imagefilledrectangle( $image, $hpos, $vpos, $hpos + $thumb_size, $vpos + $thumb_size, $pie_color[$i++] );
        imagestring( $image, $labelfont, $hpos + $thumb_size + 5, $vpos, $key, $black );
        $vpos += $vfw + 2;
    }
    return $image;
}

function wincache_get_chart_markup( $data_type, $chart_type, $chart_param1, $chart_param2 ) {
    global	$PHP_SELF;

    $result = '';
    $alt_title = '';

    if ( wincache_gd_loaded() ){
        $alt_title = wincache_get_chart_title( $data_type );
        if ( $alt_title == '' )
            return '';

        if ( $chart_type == WINCACHE_BAR_CHART )
            $alt_title .= get_string('hitmiss_chart_desc', 'tool_wincache');
        elseif ( $chart_type == WINCACHE_PIE_CHART )
            $alt_title .= get_string('memory_chart_desc', 'tool_wincache');
        else
            return '';

        $result = '<img src="'.$PHP_SELF;
        $result .= '?img='.$data_type.'&amp;type='.$chart_type;
        $result .= '&amp;p1='.$chart_param1.'&amp;p2='.$chart_param2.'" ';
        $result .= 'alt="'.$alt_title.'" width="'.WINCACHE_IMG_WIDTH.'" height="'.WINCACHE_IMG_HEIGHT.'" />';
    }
    else {
        $result = '<p class="notice">' . get_string('enable_gd_lib', 'tool_wincache') . '</p>';
    }

    return $result;
}

function wincache_cache_scope_text( $is_local ) {
    return ( $is_local == true ) ? get_string('local', 'tool_wincache') : get_string('global', 'tool_wincache');
}

function wincache_init_cache_info( $cache_data = WINCACHE_SUMMARY_DATA ) {
    global  $ocache_mem_info,
            $ocache_file_info,
            $ocache_summary_info,
            $fcache_mem_info,
            $fcache_file_info,
            $fcache_summary_info,
            $rpcache_mem_info,
            $rpcache_file_info,
            $ucache_mem_info,
            $ucache_info,
            $scache_mem_info,
            $scache_info,
            $user_cache_available,
            $session_cache_available;

    if ( $cache_data == WINCACHE_SUMMARY_DATA || $cache_data == WINCACHE_OCACHE_DATA ) {
        $ocache_mem_info = wincache_ocache_meminfo();
        $ocache_file_info = wincache_ocache_fileinfo();
        $ocache_summary_info = wincache_get_ocache_summary( $ocache_file_info['file_entries'] );
    }
    if ( $cache_data == WINCACHE_SUMMARY_DATA || $cache_data == WINCACHE_FCACHE_DATA ) {
        $fcache_mem_info = wincache_fcache_meminfo();
        $fcache_file_info = wincache_fcache_fileinfo();
        $fcache_summary_info = wincache_get_fcache_summary( $fcache_file_info['file_entries'] );
    }
    if ( $cache_data == WINCACHE_SUMMARY_DATA || $cache_data == WINCACHE_RCACHE_DATA ){
        $rpcache_mem_info = wincache_rplist_meminfo();
        $rpcache_file_info = wincache_rplist_fileinfo();
    }
    if ( $user_cache_available && ( $cache_data == WINCACHE_SUMMARY_DATA || $cache_data == WINCACHE_UCACHE_DATA ) ){
        $ucache_mem_info = wincache_ucache_meminfo();
        $ucache_info = wincache_ucache_info();
    }
    if ( $session_cache_available && ( $cache_data == WINCACHE_SUMMARY_DATA || $cache_data == WINCACHE_SCACHE_DATA ) ){
        $scache_mem_info = wincache_scache_meminfo();
        $scache_info = wincache_scache_info();
    }
}
