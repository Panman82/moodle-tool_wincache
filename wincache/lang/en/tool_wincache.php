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
 * Strings for the WinCache info tool
 *
 * @package   tool_wincache
 * @copyright 2013 Ryan Panning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'WinCache info';
$string['not_loaded'] = 'WinCache PHP extension not loaded or the version is less than 1.1.0!';
$string['page_title'] = 'Windows Cache Extension for PHP - Statistics';
$string['summery_title'] = 'Summary';
$string['ocache_title'] = 'Opcode Cache';
$string['fcache_title'] = 'File System Cache';
$string['ucache_title'] = 'User Cache';
$string['scache_title'] = 'Session Cache';
$string['rcache_title'] = 'Resolve Path Cache';
$string['general_info'] = 'General Information';
$string['wincache_version'] = 'WinCache version';
$string['php_version'] = 'PHP version';
$string['doc_root'] = 'Document root';
$string['phprc'] = 'PHPRC';
$string['server_software'] = 'Server software';
$string['operating_sys'] = 'Operating System';
$string['processor_info'] = 'Processor information';
$string['processor_num'] = 'Number of processors';
$string['machine_name'] = 'Machine name';
$string['host_name'] = 'Host name';
$string['session_handler'] = 'PHP session handler';
$string['app_pool_id'] = 'Application Pool ID';
$string['site_id'] = 'Site ID';
$string['fcgi_impersonation'] = 'FastCGI impersonation';
$string['cache_settings'] = 'Cache Settings';
$string['ocache_overview'] = 'Opcode Cache Overview';
$string['fcache_overview'] = 'File Cache Overview';
$string['ucache_overview'] = 'User Cache Overview';
$string['scache_overview'] = 'Session Cache Overview';
$string['rcache_overview'] = 'Resolve Path Cache Overview';
$string['cache_scope'] = 'Cache scope';
$string['cache_uptime'] = 'Cache uptime';
$string['cached_files'] = 'Cached files';
$string['hits'] = 'Hits';
$string['misses'] = 'Misses';
$string['total_memory'] = 'Total memory';
$string['available_memory'] = 'Available memory';
$string['memory_overhead'] = 'Memory overhead';
$string['num_of_functions'] = 'Number of functions';
$string['num_of_classes'] = 'Number of classes';
$string['total_file_size'] = 'Total files size';
$string['cached_entries'] = 'Cached entries';
$string['ocache_entries'] = 'Opcode cache entries';
$string['fcache_entries'] = 'File cache entries';
$string['ucache_entries'] = 'User cache entries';
$string['scache_entries'] = 'Session cache entries';
$string['rcache_entries'] = 'Resolve path cache entries';
$string['file_name'] = 'File name';
$string['file_name_desc'] = 'Name of the file';
$string['function_count'] = 'Function count';
$string['function_count_desc'] = 'Number of PHP functions in the file';
$string['class_count'] = 'Class count';
$string['class_count_desc'] = 'Number of PHP classes in the file';
$string['add_time'] = 'Add time';
$string['add_time_desc'] = 'Indicates total amount of time in seconds for which the file has been in the cache';
$string['use_time'] = 'Use time';
$string['use_time_desc'] = 'Total amount of time in seconds which has elapsed since the file was last used';
$string['last_check'] = 'Last check';
$string['last_check_desc'] = 'Indicates total amount of time in seconds which has elapsed since the file was last checked for file change';
$string['hit_count'] = 'Hit count';
$string['hit_count_desc'] = 'Number of times cache has been hit';
$string['file_size'] = 'File size';
$string['file_size_desc'] = 'Size of the file in KB';
$string['ucache_content'] = 'User Cache Entry Content';
$string['key_name'] = 'Key name';
$string['key_name_desc'] = 'Object Key Name';
$string['value_type'] = 'Value type';
$string['value_type_desc'] = 'Type of the object stored';
$string['value_size'] = 'Value size';
$string['value_size_desc'] = 'Size of the object stored';
$string['total_ttl'] = 'Total TTL';
$string['total_ttl_desc'] = 'Total amount of time in seconds which remains until the object is removed from the cache';
$string['total_age'] = 'Total age';
$string['total_age_desc'] = 'Total amount of time in seconds which has elapsed since the object was added to the cache';
$string['show_all_entries'] = 'Show all entries';
$string['ucache_unavailable'] = 'The user cache is not available. Enable the user cache by using <strong>wincache.ucenabled</strong> directive in <strong>php.ini</strong> file.';
$string['key_nonexistent'] = 'The variable with this key does not exist in the user cache.';
$string['ucache_entry_info'] = 'User Cache Entry Information';
$string['key'] = 'Key';
$string['size'] = 'Size';
$string['total_ttl_sec'] = 'Total Time To Live (in seconds)';
$string['total_age_sec'] = 'Total Age (in seconds)';
$string['scache_unavailable'] = 'The session cache is not enabled. To enable session cache set the session handler in <strong>php.ini</strong> to <strong>wincache</strong>, for example: <strong>session.save_handler=wincache</strong>.';
$string['resolve_path'] = 'Resolve path';
$string['subkey_data'] = 'Subkey data';
$string['memory_chart_title'] = 'Memory Usage by {$a} (in %)';
$string['hitmiss_chart_title'] = '{$a} Hits & Misses (in %)';
$string['not_defined'] = 'Not defined';
$string['not_set'] = 'Not set';
$string['not_available'] = 'Not available';
$string['enabled'] = 'enabled';
$string['disabled'] = 'disabled';
$string['unknown'] = 'Unknown';
$string['hours'] = '{$a} hours ';
$string['minutes'] = '{$a} minutes ';
$string['seconds'] = '{$a} seconds';
$string['ocache_size_increased'] = 'The opcode cache size has been automatically increased to be at least 3 times bigger than file cache size.';
$string['ocache_chart'] = 'Opcode Cache';
$string['fcache_chart'] = 'File Cache';
$string['ucache_chart'] = 'User Cache';
$string['scache_chart'] = 'Session Cache';
$string['memory_chart_default'] = 'Free & Used Memory (in %)';
$string['hitmiss_chart_default'] = '';
$string['used_memory'] = 'Used memory';
$string['free_memory'] = 'Free memory';
$string['hitmiss_chart_desc'] = ' hit and miss percentage chart';
$string['memory_chart_desc'] = ' memory usage percentage chart';
$string['enable_gd_lib'] = 'Enable GD library (<em>php_gd2.dll</em>) in order to see the charts.';
$string['local'] = 'local';
$string['global'] = 'global';
$string['hitmiss_chart_default'] = 'Hits & Misses (in %)';
$string['memory_chart_default'] = 'Free & Used Memory (in %)';