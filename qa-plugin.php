<?php
/*
	Plugin Name: Statistics Page
	Plugin URI: 
	Plugin Description: Displays forum post statistics in a diagram.
	Plugin Version: 0.1
	Plugin Date: 2013-04-18
	Plugin Author: q2apro.com
	Plugin Author URI: http://www.q2apro.com/
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: 

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html
	
*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
}

// page
qa_register_plugin_module('page', 'qa-statistics-page.php', 'qa_statistics_page', 'Statistics Page');


/*
	Omit PHP closing tag to help avoid accidental output
*/