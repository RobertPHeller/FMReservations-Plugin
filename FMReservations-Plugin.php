<?php
/** -*- php -*- ****************************************************************
  * Plugin Name: Wendell Full Moon Reservations WP Plugin
  * Description: A plugin that implements a seat reservation system for Wendell Full Moon shows.
  * Version 0.0.1
  * Author: Robert Heller
  * Author URI: http://www.deepsoft.com/
 *
 *  System        : 
 *  Module        : 
 *  Object Name   : $RCSfile$
 *  Revision      : $Revision$
 *  Date          : $Date$
 *  Author        : $Author$
 *  Created By    : Robert Heller
 *  Created       : 2026-08-28 15:44:49
 *  Last Modified : <260828.1554>
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

/* Load constants */
require_once(dirname(__FILE__) . "/includes/FMReservations_Constants.php");

/* Additional file-specific constants */
define('FMRESERVATIONS_FILE', basename(__FILE__));
define('FMRESERVATIONS_PATH', FMRESERVATIONS_DIR . '/' . FMRESERVATIONS_FILE);

/* Load Database code */
require_once(FMRESERVATIONS_INCLUDES_DIR. "/FMReservations_Database.php");





?>
