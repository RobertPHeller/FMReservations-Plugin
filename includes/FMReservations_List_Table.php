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
 *  Created       : 2026-08-29 03:10:02
 *  Last Modified : <260829.1736>
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

/* Load our constants */
require_once(dirname(__FILE__) . "/FMReservations_Constants.php");

/* Load Database code */
require_once(FMRESERVATIONS_INCLUDES_DIR. "/FMReservations_Database.php");

/* Load Per_Page_Screen_Opt class */
require_once(FMRESERVATIONS_INCLUDES_DIR . "/Per_Page_Screen_Opt.php");

/*************************** LOAD THE BASE CLASS *******************************
  *******************************************************************************
  * The WP_List_Table class isn't automatically available to plugins, so we need
  * to check if it's available and load it if necessary.
  */
if(!class_exists('WP_List_Table')){
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
  * Class to display and manage the Wendell Full Moon show seat reservations.
  */

class FMReservations_List_Table extends WP_List_Table {
  public $currentDate;
  var $viewmode = 'add';
  var $viewid   = 0;
  var $viewitem;
  protected $per_page_screen_option;
  
  function __construct($screen_id) {
    /* Add screen option: <mumble>s per page. */
    $this->per_page_screen_option =
    new FMReservations_Per_Page_Screen_Option($screen_id,
                                              'fmr_reservatons-per-page',
                                              'Reservations',20);
    //Set parent defaults
    parent::__construct( array ('singular' => 'Reservation',   // One thing
                                'plural'   => 'Reservations',  // Multiple things
                                'ajax'     => false     // AJAX?
                                ) );
  }
  /* Default column (nothing really here, since every displayed column gets 
   * its own function).
   */
  function column_default($item, $column_name) {
    return apply_filters( 'manage_items_custom_column','',
                                $column_name,$item->id);
  }
  function column_cb ($item) {
    return '<input type="checkbox" name="checked_item[]" value="'.$item->id.'" />';
  }
  function column_thedate ($item) {
    return mysql2date('F j, Y',$item->thedate);
  }
  function column_name ($item) {
    // Build row actions 
        $actions = array(
      'edit' => '<a href="'.add_query_arg(array('page' => 'fm-add-reservation',
						'mode' => 'edit',
                                                'current_date' => $this->currentDate,
						'id' => $item->id,),
					  admin_url('admin.php') ).'">'.
			'Edit'."</a>",
      'view' => '<a href="'.add_query_arg(array('page' => 'fm-add-reservation',
						'mode' => 'view',
                                                'current_date' => $this->currentDate,
						'id' => $item->id),
					  admin_url('admin.php') ).'">'.
			'View'."</a>",
      'delete' => '<a href="'.add_query_arg(array('page' => $_REQUEST['page'],
						  'action' => 'delete',
                                                  'current_date' => $this->currentDate,
						  'id' => $item->id),
					    admin_url('admin.php') ).'">'.
			'Delete'."</a>"
        );
    return stripslashes($item->name).$this->row_actions($actions);
  }
  function column_seatcount ($item) {
    return $item->seatcount;
  }
  function get_columns() { 
    return array ('cb' => '<input type="checkbox" />',
                  'name' => 'Name',
                  'seatcount' => 'Seats'
                  );
  }
  function get_sortable_columns() {return array('name');} 
  function get_bulk_actions() {
    return array('delete' => 'Delete');
  }
  function current_action() {
    if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
        return $_REQUEST['action'];

    if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
        return $_REQUEST['action2'];
  }
  function process_bulk_action() {
    $action = $this->current_action();
    switch ($action) {
      case 'delete':
	if ( isset($_REQUEST['checked_item']) && !empty($_REQUEST['checked_item'])) {
	  foreach ($_REQUEST['checked_item'] as $theitem) {
            file_put_contents("php://stderr","*** FMReservations_List_Table::process_bulk_action() theitem is $theitem\n");
	    FMReservations_Database::DeleteReservation($theitem);
	  }
	} else if ( isset($_REQUEST['id']) ) {
          file_put_contents("php://stderr","*** FMReservations_List_Table::process_bulk_action() _REQUEST['id'] is ".$_REQUEST['id']."\n");
	  FMReservations_Database::DeleteReservation($_REQUEST['id']);
	}
	break;
    }
  }
  function get_column_info() {
    file_put_contents("php://stderr","*** FMReservations_List_Table::get_column_info() isset(\$this->_column_headers) returns ".isset($this->_column_headers)."\n");
    if ( isset($this->_column_headers) ) {
      return $this->_column_headers;
    }
    $columns = $this->get_columns( );
    $hidden = array();
    $sortable = $this->get_sortable_columns();

    $this->_column_headers = array( $columns, $hidden, $sortable, 'name');
    file_put_contents("php://stderr","*** FMReservations_List_Table::get_column_info() returns: ".print_r($this->_column_headers,true)."\n");
    return $this->_column_headers;
  }
  function process_filters_and_bulk_action() {
    if ( isset ($_REQUEST['filter_top'] ) && isset( $_REQUEST['current_date_top'] ) ) {
      $this->currentDate = $_REQUEST['current_date_top'];
    } else if ( isset ($_REQUEST['filter_bottom'] ) && isset( $_REQUEST['current_date_bottom'] ) ) {
      $this->currentDate = $_REQUEST['current_date_bottom'];
    } else {
      $this->currentDate = isset( $_REQUEST['current_date'] ) ? $_REQUEST['current_date'] : FMReservations_Database::NextDate();
    }
    $this->process_bulk_action();
  }
  function prepare_items() {
    // Check permissions
    $this->check_permissions();
    // Get per page (screen option)
    $per_page = $this->per_page_screen_option->get();
    // Deal with columns
    $columns = $this->get_columns();    // All of our columns
    $hidden  = array();         // Hidden columns [none]
    $sortable = $this->get_sortable_columns(); // Sortable columns
    $this->_column_headers = array($columns,$hidden,$sortable,'name'); // Set up columns
    // Process filters and bulk action, if any
    $this->process_filters_and_bulk_action();
    $this->items = FMReservations_Database::Get_ReservationsForDate($this->currentDate);
    $total_items = count($this->items);
    $this->set_pagination_args( array (
                'total_items' => $total_items,
                'per_page'    => $total_items,
                'total_pages' => ceil($total_items/$per_page)  ));
  }
  function extra_tablenav( $which ) {
    if ($which == 'top') {
     ?><input type="hidden" name="current_date" value="<?php echo $this->currentDate; ?>" /><?php 
    }
    ?><div class="alignleft actions"><?php
    FMReservations_Database::make_dates_dropdown($this->currentDate,'current_date_'.$which);
    submit_button( __( 'Filter' ), 'secondary', 'filter_'.$which, false, 
                  array( 'id' => 'post-query-submit' ) );
    echo '</div>';
  }
  function add_item_icon() {
    switch ($this->viewmode) {
      case 'add': return 'icon-fmr-add-db';
      case 'edit': return 'icon-fmr-edit-db';
      case 'view': return 'icon-fmr-view-db';
    }
  }
  function add_item_h2() {
    switch ($this->viewmode) {
      case 'add': return 'Add Reservation';
      case 'edit': return 'Edit Reservation';
      case 'view': return 'View Reservation';
    }
  }
  function display_one_item_form($returnURL) {
    if ($this->viewmode != 'add') {
      ?><input type="hidden" name="id" value="<?php echo $this->viewid; ?>" /><?php
    }
    file_put_contents("php://stderr","*** FMReservations_List_Table::display_one_item_form(): \$this->viewitem is ".print_r($this->viewitem,true)."\n");
    ?><table class="form-table">
    <tr valign="top">
      <th scope="row"><label for="fm-thedate" style="width:20%;">Date:</label></th>
      <td><input type="hidden" name="thedate" value="<?php echo $this->viewitem->thedate; ?>" />
	  <span id="fm-thedate" style="width:75%;"><?php echo mysql2date('F j, Y',$this->viewitem->thedate); ?></span>
      </td></tr>
	  <tr valign="top">
	    <th scope="row"><label for="fm-name" style="width:20%;">Name:</label></th>
	    <td><input id="fm-name" 
			value="<?php echo esc_attr($this->viewitem->name); ?>"
			name="name"
			style="width:75%;" <?php if ($this->viewmode == 'view') echo 'readonly="readonly"'; ?> /></td></tr>
	  <tr valign="top">
	    <th scope="row"><label for="fm-seatcount" style="width:20%;">Seat Count:</label></th>
	    <td><input id="fm-seatcount" type="number"
			value="<?php echo esc_attr($this->viewitem->seatcount); ?>"
			name="seatcount"
			style="width:75%;" <?php if ($this->viewmode == 'view') echo 'readonly="readonly"'; ?> /></td></tr>
	  </table>
	  <p>
		<?php switch ($this->viewmode) {
			case 'add':
				?><input type="submit" name="addres" class="button-primary" value="Add Reservation"><?php
				break;
			case 'edit':
				?><input type="submit" name="updateres" class="button-primary" value="Update Reservation"><?php
				break;
		      } ?>
	        <a href="<?php echo $returnURL; ?>" class="button-primary">Return</a>
	  </p><?php
  }
  function check_permissions() {
    if (!current_user_can('manage_reservations'))
    {
      wp_die( __('You do not have sufficient permissions to access this page. (FMSchedule_List_Table)') );
    }
  }
  function prepare_one_item() {
    $this->check_permissions();
    if ( isset ($_REQUEST['filter_top'] ) && isset( $_REQUEST['current_date_top'] ) ) {
      $this->currentDate = $_REQUEST['current_date_top'];
    } else if ( isset ($_REQUEST['filter_bottom'] ) && isset( $_REQUEST['current_date_bottom'] ) ) {
      $this->currentDate = $_REQUEST['current_date_bottom'];
    } else {
      $this->currentDate = isset( $_REQUEST['current_date'] ) ? $_REQUEST['current_date'] : FMReservations_Database::NextDate();
    }
    $message = '';
    if ( isset ($_REQUEST['addres']) ) {
      $message = $this->checkiteminform();
      $item = $this->getitemfromform();
      if ($message == '') {
	$newid = FMReservations_Database::InsertNewReservation($item);
	$message = '<p>'.$item->name.' with '.$item->seatcount.
                         ' seats  on '.
			 $item->thedate.' Inserted with id '.
			 $newid.'.</p>';
	$this->viewmode = 'edit';
	$this->viewid   = $newid;
	$this->viewitem = $item;
      } else {
	$this->viewmode = 'add';
	$this->viewid   = 0;
	$this->viewitem = $item;
      }
    } else if ( isset ($_REQUEST['updateres']) && isset ($_REQUEST['id']) ) {
      $message = $this->checkiteminform();
      $item = $this->getitemfromform();
      $item->id = $_REQUEST['id'];
      if ($message == '') {
	FMReservations_Database::UpdateReservation($item);
	$message = '<p>'.$item->name.' with '.$item->seatcount.
                        ' seats on '.
			$item->thedate.' updated.</p>';
      }
      $this->viewmode = 'edit';
      $this->viewid   = $item->id;
      $this->viewitem = $item;
    } else {
      $this->viewmode = isset ($_REQUEST['mode']) ? $_REQUEST['mode'] : 'add';
      file_put_contents("php://stderr","*** FMReservations_List_Table::prepare_one_item(): \$this->viewmode is $this->viewmode\n");
      $this->viewid   = isset ($_REQUEST['id']) ? $_REQUEST['id'] : 0;
      file_put_contents("php://stderr","*** FMReservations_List_Table::prepare_one_item(): \$this->viewid is $this->viewid\n");
      if ($this->viewmode == 'add') {$this->viewid = 0;}
      if ($this->viewid == 0) {$this->viewmode = 'add';}
      if ($this->viewid != 0) {
	$this->viewitem = FMReservations_Database::Get_OneReservation($this->viewid);
      } else {
        $this->viewitem = FMReservations_Database::Get_BlankReservation($this->currentDate);
      }
      file_put_contents("php://stderr","*** FMReservations_List_Table::prepare_one_item(): \$this->viewitem is ".print_r($this->viewitem,true)."\n");
    }
    return $message;		
  }
  function checkiteminform() {
    $result = '';
    if ( empty($_REQUEST['name']) ) {
      $result .= '<p>Name missing.</p>';
    }
    if ( $_REQUEST['seatcount'] < 1 || $_REQUEST['seatcount'] > 6 ) {
      $result .= '<p>Seat count too small or too large. Should be at least 1 and not more than 6.</p>';
    }
    return $result;
  }
  function getitemfromform() {
    $itemary = array();
    $itemary['thedate'] = FMReservations_Database::NormalizeDate($_REQUEST['thedate']);
    $itemary['name'] = sanitize_text_field($_REQUEST['name']);
    $itemary['seatcount'] = sanitize_text_field($_REQUEST['seatcount']);
    return (object) $itemary;
  }
}
  
