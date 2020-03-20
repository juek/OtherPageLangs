<?php
/**
 * 'Other Page Languages' plugin for Typesetter CMS
 * Author: Jürgen Krausz
 * License: GPL version 2
 *
 * NOT IMPLEMENTED YET
 *
 */

namespace Addon\OtherPageLangs;

defined('is_running') or die('Not an entry point...');


class Admin extends \Addon\OtherPageLangs\Common{

  public $admin_url;

  public function __construct(){
    parent::__construct();
  }

  private function AdminPage(){
    global $langmessage, $page;

    \gp\tool\Plugins::css('css/admin_page.css', false);
  }



  /**
   * New filter hook as of 5.1.1-b1
   * we may now use translated Admin Link labels
   * @param array $array [string $link_label, string $link_name]
   * @return string changed $link_label
   */
  public function AdminLinkLabel($link_label, $link_name) {

    if( $link_name !== 'Admin_OtherPageLangs' ){
      return $link_label;
    }

    if( !empty($this->$i18n['Admin_OtherPageLangs']) ){
      $link_label = $this->$i18n['Admin_OtherPageLangs'];
    }

    return $link_label;
  }

}
