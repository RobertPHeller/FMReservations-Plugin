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
 *  Last Modified : <260830.1256>
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
    // Actions: widgets, admin menu, headings (CSS), and dashboard.
    add_action('widgets_init', array($this,'widgets_init'));
    add_action( 'admin_init', array($this,'admin_init') );
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
      add_action( 'admin_post_nopriv_print_reservation_page',
                 array($this,'print_reservation') );
      add_action( 'admin_post_print_reservation_page',
                 array($this,'print_reservation') );
    }
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
    FMReservations_Database::make_reservations_table();
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
  function admin_init() {
    // Register a new setting for "FMReservations" page.
    register_setting( 'FMReservations', 'FMReservations_options' );
    // Register a new section in the "FMReservations" page.
    add_settings_section('FMReservations_section_ticket',
                         __( 'Fullmoon Reservation Ticket Settings', 
                            'FMReservations'),
                         array($this, 'TicketSettingsSection'),
                         'fm-reservations-options');
                        
    // Register a new field in the "FMReservations_section_ticket" section, inside the "fm-reservations-options" page.
    add_settings_field('fm-reservations-ticket-text',
                       __('Ticket Text', 'FMReservations'),
                       array($this,'TicketTextField'),
                       'fm-reservations-options',
                       'FMReservations_section_ticket',
                       array('label_for'         => 'ticket-text',
                             'class'             => 'fmr-option-row'));
    add_settings_field('fm-reservations-ticket-maxseats',
                       __('Maximum Seats Per Reservation', 'FMReservations'),
                       array($this,'TicketMaxSeatsField'),
                       'fm-reservations-options', 
                       'FMReservations_section_ticket',
                       array('label_for'         => 'ticket-maxseats',
                             'class'             => 'fmr-option-row',
                             'default-value'     => 6));
                       
  }
  function TicketSettingsSection($args) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>">
    <?php esc_html_e( 'Fullmoon Reservation Ticket Settings', 'FMReservations' ); ?></p>
    <?php
  }
  function TicketMaxSeatsField($args) {
    $options = get_option( 'FMReservations_options' );
    $value = isset($options[$args['label_for'] ])?$options[$args['label_for'] ]:$args['default-value'];
    ?><input
         id="<?php echo esc_attr( $args['label_for'] ); ?>"
         name="FMReservations_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
         type="number" min="1" max="20"
         value="<?php echo esc_attr($value); ?>" />
      <p class="description">
      <?php esc_html_e( 'This is the maximum number of seats that can be reserved with a single reservation.', 'FMReservations' ); ?></p><?php
  }
    
  function TicketTextField($args) {
    $options = get_option( 'FMReservations_options' );
  ?><textarea
   id="<?php echo esc_attr( $args['label_for'] ); ?>"
     name="FMReservations_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
     rows="10" cols="60"><?php echo esc_html($options[$args['label_for'] ]); ?></textarea>
    <p class="description">
    <?php esc_html_e( 'This is the text to be printed on the reservation ticket.', 'FMReservations' ); ?></p><?php
  }
  function admin_menu() {
    $screen_id1 = add_menu_page( 'FM Reservations', 'FM Reservations',
                                'manage_reservations', 'fm-reservations-list',
                                array( $this, 'Show_Reservations_Page' ),
                                FMRESERVATIONS_PLUGIN_IMAGE_URL .
                                '/FMReservations_menu.png' );
    file_put_contents("php://stderr","*** FMRESERVATIONS_Plugin::admin_menu: screen_id1 = $screen_id1\n");
    require_once (FMRESERVATIONS_INCLUDES_DIR . '/FMReservations_List_Table.php');
    $this->reservations_list_table = new FMReservations_List_Table($screen_id1);
    $screen_id2 = add_submenu_page ('fm-reservations-list', 'Add New Reservation',
                                    'Add New', 'manage_reservations', 
                                    'fm-add-reservation',
                                    array( $this, 'Add_FM_Reservation' ));
    $screen_id3 = add_options_page('FM Reservations Options','FMReservations',
                                   'manage_reservations',
                                   'fm-reservations-options',
                                   array( $this, 'OptionsPage') );
  }
  function Show_Reservations_Page() {
    $this->reservations_list_table->prepare_items();
  ?><div class="wrap"><div id="icon-fmr-db" class="icon32"><br />
    </div><h2>FM Reservations List <a href="<?php 
       echo add_query_arg(array('page' => 'fm-add-reservation',
                                'current_date' => $this->reservations_list_table->currentDate,
                                'mode' => 'add'),
                             admin_url('admin.php') ); 
       ?>" class="button add-new-h2">Add New</a> <a href="<?php
        echo add_query_arg(array('current_date' => $this->reservations_list_table->currentDate,
                                 'action' => 'print_reservations'),
			   admin_url('admin-post.php')); 
	?>" class="button add-new-h2" title="Print" target="_blank"
	    onclick="window.open(this.href,'win2','status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no'); return false;" 
	    rel="nofollow">Print Reservations</a></h2>
           
    <form action="" method="get">
    <input type="hidden" name="page" value="fm-reservations-list" />
    <?php $this->reservations_list_table->display(); ?></form><br class="clear" /></div><div class="clear"></div><?php
 }
 function print_reservations() {
   if (isset($_REQUEST['current_date'])) {
     $thedate = sanitize_text_field($_REQUEST['current_date']);
   } else {
     $thedate = FMReservations_Database::NextDate();
   }
   $data = FMReservations_Database::Get_ReservationsForDate($thedate);
   $print_html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
   $print_html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-gb" lang="en-gb" dir="ltr">'."\n";
   $print_html .= '<head><title>Wendell Full Moon Coffeehouse Reservation List '.$thedate.'.';
   $print_html .= '</title>';
   $print_html .= '<style type="text/css" media="print">'."\n";
   $print_html .= 'a.navigate {display: none;}</style></head>';
   $print_html .= '<body><h1>Wendell Full Moon Coffeehouse Reservation List ';
   $print_html .=  mysql2date('F j, Y',$thedate).'.</h1>';
   $print_html .= '<div style="float: right;"><a class="navigate" href="#" onclick="window.print();return false;">Print</a>&nbsp;<a class="navigate" href="#" onclick="window.close();return false;">Close</a></div>'."\n";
   $print_html .= '<br clear="all" />'."\n";


   $print_html .= "<ol>\n";

   foreach ((array)$data as $item) {
     $print_html .= '<li> Reservation ID: '.$item->id;
     $print_html .= '&nbsp;<b>'.esc_html($item->name).'</b>';
     $print_html .= '&nbsp;'.esc_html($item->seatcount).' seat';
     if ($item->seatcount > 1) { $print_html .= 's'; }
     $print_html .= "</li>\n";
   }
   
   $print_html .= "</ol>\n</body></html>\n";
   
   header("Content-type: text/html");
   header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
   header("Content-Length: " . strlen($print_html));
   
   echo $print_html;
   wp_die();
 }
 function Add_FM_Reservation() {
   $message = $this->reservations_list_table->prepare_one_item();
   ?><div class="wrap"><div id="<?php echo $this->reservations_list_table->add_item_icon(); ?>" class="icon32"><br />
     </div><h2><?php echo $this->reservations_list_table->add_item_h2(); ?></h2>
   <?php if ($message != '') {
       ?><div id="message"class="updated fade"><p><?php echo $message; ?></div><?php
	  } ?>
    <form action="" method="get">
    <input type="hidden" name="page" value="fm-add-reservation" />
    <?php $this->reservations_list_table->display_one_item_form(add_query_arg(array('page' => 'fm-reservations-list'),
								 admin_url('admin.php')) ); ?></form><br class="clear" /></div><div class="clear"></div><?php
  }
  function reservation_shortcode($atts, $content=null, $code="") {
    extract( shortcode_atts ( array(), $atts ) );
    $options = get_option( 'FMReservations_options' );
    $messages = array();
    if ( isset($_REQUEST['EnterReservation']) ) {
      do_action('register_reservation_verify', true);
      $dataok = true;
      if (class_exists('WPPlugin') && class_exists('ReCAPTCHAPlugin') &&
          class_exists('ReCaptcha') ) {
        $this->recaptcha_options = WPPlugin::retrieve_options('recaptcha_options');
        if ($this->recaptchalib == null) {
          $this->recaptchalib = new ReCaptcha($this->recaptcha_options['secret']);
        }
        $response = $this->recaptchalib->verifyResponse(
                                                        $_SERVER['REMOTE_ADDR'],
                                                        $_POST['g-recaptcha-response']);
        if (!$response->success) {
          $messages[] = '<span id="error"><strong>ReCAPTCHA error:</strong> your captcha response was incorrect -- please try again</span>';
          $dataok = false;
          file_put_contents("php://stderr","*** reservation_shortcode: ReCAPTCHA failed\n");
        }
      }
      if ( empty($_REQUEST['res_name']) ) {
        $messages[] = '<div id="error"><p>Name missing.</p></div>';
        $dataok = false; 
      }
      $name =  sanitize_text_field($_REQUEST['res_name']);
      $seatcount =  sanitize_text_field($_REQUEST['seatcount']);
      $maxseats = $options['ticket-maxseats'];
      if ($seatcount < 1 || $seatcount > $maxseats) {
        $messages[] = '<div id="error"><p>Number of seats is too small or too large: the number of seats must be from 1 to '.$maxseats.'.</p></div>';
        $dataok = false; 
      }
      $thedate = sanitize_text_field($_REQUEST['thedate']);
      if ($dataok) { 
        $item = (object)
        array('thedate' => FMReservations_Database::NormalizeDate($thedate),
              'name' => $name,
              'seatcount' => $seatcount,
              'id' => 0);
        file_put_contents("php://stderr","*** FMRESERVATIONS_Plugin::reservation_shortcode(): item is ".print_r($item,true)."\n");
        $reservationID = FMReservations_Database::InsertNewReservation($item);
        file_put_contents("php://stderr","*** FMRESERVATIONS_Plugin::reservation_shortcode(): reservationID is $reservationID\n");
        $temp = '<p style="font-size: 200%;color:green">';
        $temp .= 'Your reservation number is '.$reservationID;
        $temp .= ' for '.$seatcount.' seat';
        if ($seatcount> 1) { $temp .= 's'; }
        $temp .= ' under the name '.esc_html($name).'</p>';
        $messages[] = $temp;
        $messages[] = $options['ticket-text'];
        $print_url = add_query_arg(array('id' => $reservationID,
                                      'action' => 'print_reservation_page'),
                                      admin_url('admin-post.php') );
        $temp = '<p><h2><a href="'.$print_url.'" class="button add-new-h2"';
        $temp .= 'title="Print" target="_blank" onclick="';
        $temp .= "window.open(this.href,'win2','status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no'); return false;";
        $temp .= '" rel="nofollow">Print Reservation Slip</a></h2></p>';
        $messages[] = $temp;
        //$print_url_nonce = wp_nonce_url(  ); 
      }
    }
    $result = '';
    foreach ($messages as $m) {
      $result .= $m;
    }
    $result .= '<form name="reservation" method="POST" action="">';
    $result .= '<table class="form-table">';
    $dates = FMReservations_Database::make_dates_dropdown_r();
    $result .= $dates;
    $result .= '<tr valign="top"><th  scope="row"><label for="name">Name: </label></th>';
    $result .= '<td><input id="name", name="res_name" /></td></tr>';
    $result .= '<tr valign="top"><th scope="row"><label for="seats">Seats: </label></th>';
    $result .= '<td><input id="seats" name="seatcount" type="number" value="1" min="1" max="'.$maxseats.'" /><td>';
    $result .= '</tr>';
    $result .= '</table>';
    $result .= '<p><input type="submit" name="EnterReservation" value="Make Reservation" /></tp></form>';
    return $result;
  }
  function print_reservation() {
    file_put_contents("php://stderr","*** FMRESERVATIONS_Plugin::print_reservation()\n");
    $options = get_option( 'FMReservations_options' );
    $theid = sanitize_text_field($_REQUEST['id']);
    $item = FMReservations_Database::Get_OneReservation($theid);
    $print_html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
    $print_html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-gb" lang="en-gb" dir="ltr">'."\n";
    $print_html .= '<head><title>Wendell Full Moon Coffeehouse Reservation';
    $print_html .= '</title>';
    $print_html .= '<style type="text/css" media="print">'."\n";
    $print_html .= 'a.navigate {display: none;}</style></head>';
    $print_html .= '<body><h1>Wendell Full Moon Coffeehouse Reservation</h1>';
    $print_html .= '<div style="float: right;"><a class="navigate" href="#" onclick="window.print();return false;">Print</a>&nbsp;<a class="navigate" href="#" onclick="window.close();return false;">Close</a></div>'."\n";
    $print_html .= '<br clear="all" />'."\n";
    $print_html .= '<div style="float:left"><img src="'.FMRESERVATIONS_PLUGIN_IMAGE_URL.'/small_logo_flat.png" />';
    $print_html .= '<table><tr><th>Date: </th><td>'.mysql2date('F j, Y',$item->thedate).'</td></tr>';
    $print_html .= '<tr><th>Reservation Id:</th><td>'.$item->id.'</td></tr>';
    $print_html .= '<tr><th>Name:</th><td>'.esc_html($item->name).'</td></tr>';
    $print_html .= '<tr><th>Seats:</th><td>'.$item->seatcount.'</td></tr>';
    $print_html .= "</table>\n";
    $print_html .= $options['ticket-text'];
    $print_html .= "</body></html>\n";
    
    header("Content-type: text/html");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Length: " . strlen($print_html));
   
    echo $print_html;
    //wp_die();

  }
  function OptionsPage() {
    // check user capabilities
    if ( ! current_user_can( 'manage_reservations' ) ) {
      return;
    }
    // add error/update messages
    
    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
      // add settings saved message with the class of "updated"
      add_settings_error( 'rm-reservations_messages', 'rm-reservations_message', __( 'Settings Saved', 'FMReservations' ), 'updated' );
    }
    
    // show error/update messages
    settings_errors( 'rm-reservations_messages' );
  ?>
  <div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="options.php" method="post">
    <?php
      // output security fields for the registered setting "rm-reservations"
      settings_fields( 'FMReservations' );
      // output setting sections and their fields
      // (sections are registered for "FMReservations", each field is registered to a specific section)
      do_settings_sections( 'fm-reservations-options' );
      // output save settings button
      submit_button( 'Save Settings' );
    ?>
    </form>
  </div>
  <?php
    
    
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
