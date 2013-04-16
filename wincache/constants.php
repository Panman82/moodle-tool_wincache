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
 * WinCache statistics constants
 *
 * @package   tool_wincache
 * @copyright 2013 Ryan Panning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('WINCACHE_IMG_WIDTH', 320);
define('WINCACHE_IMG_HEIGHT', 220);
define('WINCACHE_SUMMARY_DATA', 1);
define('WINCACHE_OCACHE_DATA', 2); // Opcode cache
define('WINCACHE_FCACHE_DATA', 3); // File cache
define('WINCACHE_UCACHE_DATA', 4); // User cache
define('WINCACHE_SCACHE_DATA', 5); // Session cache
define('WINCACHE_RCACHE_DATA', 6); // Resolve file cache
define('WINCACHE_BAR_CHART', 1);
define('WINCACHE_PIE_CHART', 2);
define('WINCACHE_PATH_MAX_LENGTH', 45);
define('WINCACHE_INI_MAX_LENGTH', 45);
define('WINCACHE_SUBKEY_MAX_LENGTH', 90);
define('WINCACHE_CACHE_MAX_ENTRY', 250);
