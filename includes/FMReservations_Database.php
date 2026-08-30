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
 *  Created       : 2026-08-28 16:01:04
 *  Last Modified : <260829.2023>
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

global $wpdb;
define('FMRESERVATIONS_TABLE',$wpdb->prefix . "FMReservations");
//file_put_contents("php://stderr","*** FMRESERVATIONS_TABLE ".FMRESERVATIONS_TABLE."\n\n\n\n"); 

class FMReservations_Database {
  public static function make_reservations_table() {
    global $wpdb;
    //file_put_contents("php://stderr","*** FMReservations_Database::make_reservations_table");
    $sql = "CREATE TABLE " . FMRESERVATIONS_TABLE . "(
        id int NOT NULL AUTO_INCREMENT,
        thedate date not NULL,
        seatcount int NOT NULL check (seatcount > 0),
        name text not NULL check (name <> ''),
        PRIMARY KEY (id)
    );";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);

    //file_put_contents("php://stderr","*** FMReservations_Database::make_reservations_table: result (of $sql) is $result\n");
  }
  public static function NextDate() {
    if (method_exists('FMSchedule_Database','Get_UpcomingShows')) {
      $shows = FMSchedule_Database::Get_UpcomingShows();
      if (count($shows) > 0) {
        $nextShow = $shows[0];
        return $nextShow['thedate'];
      }
    }
    return current_time(  'mysql' ); 
  }
  public static function make_dates_dropdown($thedate='',$name='current_date') {
    file_put_contents("php://stderr","*** FMReservations_Database::make_dates_dropdown()\n");
    ?><label for="FM_date">Date:</label>
    <select name="<?php echo $name;?>" id="FM_ate"><?php
       file_put_contents("php://stderr","*** :- method_exists('FMSchedule_Database','Get_UpcomingShows') returns |".method_exists('FMSchedule_Database','Get_UpcomingShows')."|\n");
       if (method_exists('FMSchedule_Database','Get_UpcomingShows')) {
         $shows = FMSchedule_Database::Get_UpcomingShows();
         file_put_contents("php://stderr","*** -: shows = ".print_r($shows,true)."\n");
         if ($thedate == '' && count($shows) > 0) {
           $thedate = $shows[0]['thedate'];
         }
         for ($i = 0; $i < count($shows); $i++) {
           $date = $shows[$i]['thedate'];
           ?><option value="<?php echo $date; ?>" <?php
           if ($date == $thedate) { echo 'selected="selected"'; }
           ?>><?php echo $date . ": " . $shows[$i]['artist']; ?></option><?php
         }
       }
   ?></select><?php
  }
  public static function make_dates_dropdown_r($thedate='',$name='thedate') {
    $result  = '<tr valign="top"><th  scope="row"><label for="FM_date">Date:</label></th>'."\n";
    $result .= '<td><select name="'.$name.'" id="FM_ate">';
    if (method_exists('FMSchedule_Database','Get_UpcomingShows')) {
      $shows = FMSchedule_Database::Get_UpcomingShows();
      if ($thedate == '' && count($shows) > 0) {
        $thedate = $shows[0]['thedate'];
      }
      for ($i = 0; $i < count($shows); $i++) {
        $date = $shows[$i]['thedate'];
        $result .= '<option value="'.$date.'"';
        if ($date == $thedate) { $result .=  ' selected="selected"'; }
        $result .= '>'.$date . ": " . $shows[$i]['artist'].'</option>';
      }
    }
    $result .= '</select></td></tr>';
    return $result;
  }
  public static function Get_Future_Reservations($format='OBJECT') {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM " .
                              FMRESERVATIONS_TABLE.
                              " where thedate >= CURDATE() order by thedate, name",
                              $format);
  }
  public static function Get_ReservationsForDate($thedate,$format='OBJECT') {
    global $wpdb;
    $sql = $wpdb->prepare('SELECT * FROM '.
                          FMRESERVATIONS_TABLE.
                          " where thedate = %s order by name",
                          $thedate);
    return $wpdb->get_results($sql,$format);
  }
  public static function TotalReservedSeatsForDate($thedate) {
    global $wpdb;
    $sql = $wpdb->prepare('SELECT SUM(seatcount) FROM '.FMRESERVATIONS_TABLE.' WHERE thedate = %s',$thedate);
    return $wpdb->get_var($sql);
  }
  public static function ReservationsForDate($thedate) {
    global $wpdb;
    $sql = $wpdb->prepare('SELECT COUNT(*) FROM '.FMRESERVATIONS_TABLE.' WHERE thedate = %s',$thedate);
    return $wpdb->get_var($sql);
  }
  public static function Get_OneReservation($id) {
    file_put_contents("php://stderr","*** FMReservations_Database::Get_OneReservation($id)\n");
    global $wpdb;
    $sql = $wpdb->prepare('SELECT * FROM '.FMRESERVATIONS_TABLE.' WHERE id = %d',$id);
    return $wpdb->get_row($sql, 'OBJECT');
  }
  public static function InsertNewReservation($item) {
    file_put_contents("php://stderr","*** FMReservations_Database::InsertNewReservation(): item is ".print_r($item,true)."\n");
    $thedate   = $item->thedate;
    $seatcount = $item->seatcount;
    $name      = $item->name;
    global $wpdb;
    $wpdb->insert(FMRESERVATIONS_TABLE,array("thedate" => $thedate,
                                             "seatcount" => $seatcount,
                                             "name" => $name),
                                             array("%s","%d","%s"));
    file_put_contents("php://stderr","*** FMReservations_Database::InsertNewReservation(): inserted with id ".$wpdb->insert_id."\n");
    return $wpdb->insert_id;
  }
  public static function UpdateReservation($item) {
    $thedate   = $item->thedate;
    $seatcount = $item->seatcount; 
    $name      = $item->name;
    $id        = $item->id;
    global $wpdb;
    $wpdb->update(FMRESERVATIONS_TABLE,array("seatcount" => $seatcount,
                                             "name" => $name),
                                             array('id' => $item->id),
                                             array('%d','%s'),'%d');
  }
  public static function DeleteReservation($id) {
    global $wpdb;
    $sql = $wpdb->prepare('DELETE FROM '.FMRESERVATIONS_TABLE.' WHERE id = %d',$id);
    $wpdb->query($sql);
  }
  public static function Get_BlankReservation($thedate) {
    file_put_contents("php://stderr","*** FMReservations_Database::Get_BlankReservation('$thedate')\n");
    global $wpdb;
    return (object) array(
                          'id' => 0,
                          'thedate' => $thedate,
                          'seatcount' => 1,
                          'name' => '');
  }
  public static function NormalizeDate($datestring ) {
    $matches = array();
    if (preg_match('/^([0-9][0-9][0-9][0-9])-([0-9][0-9])-([0-9][0-9])$/',
		 $datestring,$matches)) {
      $year = $matches[1];
      $month = $matches[2];
      $day = $matches[3];
    } else if (preg_match('|^([0-9][0-9]*)/([0-9][0-9]*)/([0-9][0-9][0-9][0-9])$|',
			$datestring,$matches)) {
      $month = $matches[1];
      $day = $matches[2];
      $year = $matches[3];
    }
    return sprintf('%04d-%02d-%02d',$year,$month,$day);
  }
}  

        
?>
