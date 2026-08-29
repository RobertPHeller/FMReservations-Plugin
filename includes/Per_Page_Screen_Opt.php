<?php
 /*
  * Common code for handling Per Page Screen Opt
  *
  * (based on code in the article "How To Use Screen Options in your WordPress 
  * Plugin" by Chris Marslender at the URL: 
  * http://chrismarslender.com/wp-tutorials/wordpress-screen-options-tutorial/)
  */

class FMReservations_Per_Page_Screen_Option {

  var $option = 'per_page';
  var $args   = array('label' => 'Items',
		      'default' => 20,
		      'option' => 'fmdatabase_items_per_page');

  function __construct($screen_id,$option, $label = 'Items', $default = 20) {
    $this->args['option'] = $option;
    $this->args['label'] = $label;
    $this->args['default'] = $default;
    add_action("load-$screen_id", array($this,'add_option') );
  } 
  function add_option() {
    //file_put_contents("php://stderr","*** FMDatabase_Per_Page_Screen_Option::add_option: this->option is $this->option, this->args is ".print_r($this->args,true)."\n");
    add_screen_option( $this->option, $this->args );
  }
  function get() {
    $user = get_current_user_id();
    $screen = get_current_screen();
    $option = $screen->get_option($this->option,'option');
    $v = get_user_meta($user, $option, true);
    //file_put_contents("php://stderr","*** FMDatabase_Per_Page_Screen_Option::get: user is $user, screen is ".print_r($screen,true).", option = $option, v is |$v|\n");
    if (empty($v)  || $v < 1) {
      $v = $option = $screen->get_option($this->option,'default');
      //file_put_contents("php://stderr","*** FMDatabase_Per_Page_Screen_Option::get: v is |$v|\n");
    }
    return $v;
  }
}
