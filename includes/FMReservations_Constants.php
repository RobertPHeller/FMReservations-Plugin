<?php
/* -*- php -*- ****************************************************************
 *
 *  System        : 
 *  Module        : 
 *  Object Name   : $RCSfile$
 *  Revision      : $Revision$
 *  Date          : $Date$
 *  Author        : $Author$
 *  Created By    : Robert Heller
 *  Created       : 2026-08-28 15:57:39
 *  Last Modified : <260828.1600>
 *
 *  Description	
 *
 *  Notes
 *
 *  History
 *	
 ****************************************************************************
 *
 *    Copyright (C) 2026  Robert Heller D/B/A Deepwoods Software
 *			51 Locke Hill Road
 *			Wendell, MA 01379-9728
 *
 *    This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program; if not, write to the Free Software
 *    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * 
 *
 ****************************************************************************/

define('FMRESERVATIONS_INCLUDES_DIR', plugin_dir_path(__FILE__));      /* The Plugin includes directory */
define('FMRESERVATIONS_DIR', dirname(FMRESERVATIONS_INCLUDES_DIR));       /* The Plugin directory */
define('FMRESERVATIONS_MAINFILE', FMRESERVATIONS_DIR . '/FMReservations-Plugin.php');       /* Root file of the plugin */
define('FMRESERVATIONS_PLUGIN_NAME', 'FMReservations'); /* Name of the plugin */
define('FMRESERVATIONS_DISPLAY_NAME', _fms_get_header_value(FMRESERVATIONS_MAINFILE,'Plugin Name'));
define('FMRESERVATIONS_VERSION', _fms_get_header_value(FMRESERVATIONS_MAINFILE,'Version'));
define('FMRESERVATIONS_PLUGIN_URL',plugin_dir_url(FMRESERVATIONS_MAINFILE));
define('FMRESERVATIONS_PLUGIN_CSS_URL', FMRESERVATIONS_PLUGIN_URL . '/css');
define('FMRESERVATIONS_PLUGIN_IMAGE_URL', FMRESERVATIONS_PLUGIN_URL . '/images');


?>
