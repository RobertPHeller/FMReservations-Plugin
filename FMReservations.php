<?php
/** -*- php -*- ****************************************************************
  * Plugin Name: Wendell Full Moon Reservations WP Plugin
  * Description: A plugin that implements a seat reservation system for Wendell Full Moon shows.
  * Version 0.0.1
  * Author: Robert Heller
  * Author URI: http://www.deepsoft.com/
  * Requires Plugins: FMSchedule
 *
 *  System        : 
 *  Module        : 
 *  Object Name   : $RCSfile$
 *  Revision      : $Revision$
 *  Date          : $Date$
 *  Author        : $Author$
 *  Created By    : Robert Heller
 *  Created       : 2026-08-28 15:44:49
 *  Last Modified : <260829.0316>
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
 *    This program is fb iuhree software; you can redistribute it and/or modify
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


/* Main plugin class. Implements the basic admin functions of the plugin. */
class FMRESERVATIONS_Plugin {
  public $version = FMRESERVATIONS_VERSION;
  
  var $reservations_list_table = '';
  /* Constructor: register our activation and deactivation hooks and then
    * add in our actions.
    */
  function __construct() {
    // Add the installation and uninstallation hooks
    register_activation_hook(FMRESERVATIONS_PATH, array($this,'install'));
    register_deactivation_hook(FMRESERVATIONS_PATH, array($this,'deinstall'));
  }
  function customSgrRenderList(array $list): array //Where reCAPTCHA is rendered
  {
    $list[] = 'register_reservation_form';
    return $list;
  }
  function customSgrVerifyList(array $list): array //Where reCAPTCHA is verified
  {
    $list[] = 'register_reservation_verify';
  }
  /* Activation hook: create database tables, add in privs., */
  function install() {
    //file_put_contents("php://stderr","*** FMRESERVATIONS_Plugin::install()\n");
    //file_put_contents("php://stderr","*** -: about to check for FMSchedule_Database::Get_UpcomingShows\n");
    // Actions: widgets, admin menu, headings (CSS), and dashboard.
    add_action('widgets_init', array($this,'widgets_init'));
    add_action('admin_menu', array($this,'admin_menu'));
    add_action('wp_head', array($this,'wp_head'));
    add_action('admin_head', array($this,'admin_head'));
    add_action('wp_dashboard_setup', array($this,'wp_dashboard_setup'));
    add_shortcode('fmreservation', array($this,'reservation_shortcode'));
    wp_enqueue_style('fmr-css', 
                     FMRESERVATIONS_PLUGIN_CSS_URL  . '/FMReservations.css',
                     null,FMRESERVATIONS_VERSION);
    if (is_admin()) {
      wp_enqueue_style('fmr-admin-css', 
                       FMRESERVATIONS_PLUGIN_CSS_URL  . '/FMReservations_admin.css',
                       array('fmr-css'),FMRESERVATIONS_VERSION);
      add_action( 'admin_post_print_reservations', 
                 array($this,'print_reservations') );
    }
    FMReservations_Database::make_reservations_table();
    global $wp_roles;
    $wp_roles->add_cap ('administrator', 'manage_reservations');
    $wp_roles->add_cap ('editor', 'manage_reservations');
    $wp_roles->add_cap ('author', 'manage_reservations');
    $member = get_role('member');
    if ($member == null) {
      add_role('member','Member', array('read' => true, 
                                        'manage_reservations' => true ));
    } else {
      $member->add_cap('manage_reservations');
    }
    $assoc = get_role('associate_member' );
    if ($assoc == null) {
      add_role('associate_member', 'Associate Member',
               array('read' => true,
                     'manage_reservations' => true ));
    } else {
      $assoc->add_cap('manage_reservations'); 
    }
    add_action('sgr_render_list', array($this,'customSgrRenderList'));
    add_action('sgr_verify_list', array($this,'customSgrVerifyList'));
  }
  /* Deactivation hook: remove privs and cron job */
  function deinstall() {
    global $wp_roles;
    $wp_roles->remove_cap ('administrator', 'manage_reservations' );
    $wp_roles->remove_cap ('editor', 'manage_reservations' );
    $wp_roles->remove_cap ('author', 'manage_reservations' );  
    $member = get_role('member');
    if ($member != null) {
      $member->remove_cap('manage_reservations');
    }
    $assoc = get_role('associate_member' );
    if ($assoc != null) {
      $assoc->remove_cap('manage_reservations'); 
    }
  }
  /* Initialize our widgets */
  function widgets_init() {
  }
  function admin_menu() {
    $screen_id1 = add_menu_page( 'FM Reservations', 'FM Reservations',
                                'manage_reservations', 'fm-reservations-list',
                                array( $this, 'Show_Reservations_Page' ),
                                FMRESERVATIONS_PLUGIN_IMAGE_URL .
                                '/FMReservations_menu.png' );
    //file_put_contents("php://stderr","*** FMRESERVATIONS_Plugin::admin_menu: screen_id1 = $screen_id1\n");
    require_once (FMRESERVATIONS_INCLUDES_DIR . '/FMReservations_List_Table.php');
    $this->reservations_list_table = new FMReservations_List_Table();
    $screen_id2 = add_submenu_page ('fm-reservations-list', 
                                    'Add New Reservation',
                                    'fm-add-reservation',
                                    array( $this, 'Add_FM_Reservation' ));
  }
  function Show_Reservations_Page() {
    $this->reservations_list_table->prepare_items();
  ?><div class="wrap"><div id="icon-fmr-db" class="icon32"><br />
    </div><h2>FM Reservations List <a href="<?php 
       echo add_query_arg(array('page' => 'fm-add-reservation',
                                'mode' => 'add'),
                             admin_url('admin.php') ); 
       ?>" class="button add-new-h2">Add New</a> </h2>
    <form action="" method="get">
    <input type="hidden" name="page" value="fm-reservations-list" />
    <?php $this->reservations_list_table->display(); ?></form><br class="clear" /></div><div class="clear"></div><?php
 }
 function wp_head() {
 }
 function admin_head() {
 }
 function wp_dashboard_setup() {
 }
 
}      
      
new FMRESERVATIONS_Plugin();  

?>
