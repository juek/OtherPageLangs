<?php
/**
 * 'Other Page Languages' plugin for Typesetter CMS
 * Author: Jürgen Krausz
 * License: GPL version 2
 *
 */

namespace Addon\OtherPageLangs;

defined('is_running') or die('Not an entry point...');


class Common{

  public $config;
  public $i18n;
  public $request_langs;
  public $page_lang;
  public $page_links;
  public $page_links_count;
  public $page_has_links;
  public $page_lang_is_default;

  public $page_is_preferred;
  public $page_would_redir_to;



  /**
   * Class constructor
   *
   */
  public function __construct(){
    $this->LoadI18n();
    $this->LoadConfig();
    $this->GetRequestLangs();
  }



  /**
   * Set class variables that cannot be set in the class constructor (called too early)
   *
   */
  public function SetVars(){
    global $page, $config;
    // msg('Common -> Setvars(): $page = ' . pre(get_object_vars($page)));

    $this->page_lang            = $this->GetPageLang();
    $this->page_links           = $this->GetLinkedPages();
    $this->page_links_count     = count($this->page_links);
    $this->page_has_links       = $this->page_links_count > 1;
    $this->page_lang_is_default = $this->page_lang == $config['language'];
  }



  /**
   * Get other pages linked / assigned to a certain page
   * @param string 'gp_index' of the page in question,  optional - uses the current page if not passed
   * @return array of linked pages and their assigned languages
   *
   */
  public function GetLinkedPages($index = false){
    global $page;

    $pages                  = array();
    $current_index          = $index;
    $current_lang           = $this->GetPageLang($current_index);

    if( !$index ){
      $current_index        = $page->gp_index;
      $current_lang         = $this->GetPageLang($page->gp_index);
    }

    $pages[$current_index]  = $current_lang;

    if( empty($this->config['links']) ){
      return $pages;
    }

    foreach( $this->config['links'] as $link_array ){
      if( in_array($current_index, $link_array) ){
        foreach( $link_array as $index ){
          if( $index == $current_index ){
            // strip current page, already inserted
            continue;
          }
          $pages[$index] = $this->GetPageLang($index);
        }
        break;
      }
    }

    return $pages;
  }



  /**
   * Get the language assigned to a certain page
   * @param string 'gp_index' of the page in question,  optional - uses the current page if not passed
   * @return string lang code assigned to the page in question
   *
   */
  public function GetPageLang($index = false){
    global $page, $config;

    if( !$index ){
      if( $page->pagetype == 'admin_display' ){
        return $config['language'];
      }
      $index = $page->gp_index;
    }

    if( isset($this->config['page_langs'][$index]) ){
      $lang = $this->config['page_langs'][$index]['lang'];
    }else{
      $lang = $config['language'];
    }

    return $lang;
  }



  /**
   * Load the translations required for Gadgets and Editing
   *
   */
  public function LoadI18n(){
    global $addonPathCode;

    include $addonPathCode . '/i18n/i18n.php';
    $this->i18n = $i18n;
  }


  /**
   * Load the plugin configuration
   * that contains page-language and page-page associations
   *
   */
  public function LoadConfig(){
    $config = \gp\tool\Plugins::GetConfig();
    if( !$config ){
      $this->config = array();
    }
    $this->config = $config;
  }



  /**
   * Helper function to replace last occurence of a string in another string
   * @param string $search, string to find in $subject
   * @param string $replace, string to $search should be replaced with
   * @param string $subject, string in which $search should be found and replaced
   * @return string $subject, result of the operation
   *
   */
  public function StrReplaceLast($search, $replace, $subject){
      $pos = strrpos($subject, $search);
      if( $pos !== false ){
          $subject = substr_replace($subject, $replace, $pos, strlen($search));
      }
      return $subject;
  }



  /**
   * Helper function to sort a multidimensional array based on the values of a specified direct subarray key
   * @param array $arr to be sorted
   * @param string $subkey key of subarrays of $arr that contains the value for sorting
   * @param boolean (optional) $reverse to reverse-sort if passed true
   * @return array $return sorted array
   *
   */
  public function SortArrayBySubkeyValue($arr, $subkey, $reverse = false){
    $tmp    = array();
    $return = array();

    foreach( $arr as $key => $val ){
      if( !is_array($val) ){
        // not a consisitent multidimensional array
        return $arr;
      }
      $tmp[$key] = strtolower($val[$subkey]);
    }

    if( $reverse ){
      arsort($tmp);
    }else{
      asort($tmp);
    }
    
    foreach($tmp as $key => $val ){
      $return[$key] = $arr[$key];
    }

    return $return;
  }



  /**
   * Get the 'accepted' languages the web browser sends in the request header
   * borrowed from Multi-Language Manager, thanks Josh ;)
   * @return array $langs array of language codes preferred by the web browser, sorted by q_factor (descending)
   *
   */
  public function GetRequestLangs(){
    $langs = array();
    $temp = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

    // break up string into pieces (languages and q factors)
    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $temp, $lang_parse);

    if( count($lang_parse[1]) ){
      // create a list like "en" => 0.8
      $langs = array_combine($lang_parse[1], $lang_parse[4]);

      // set default to 1 for any without q factor
      foreach( $langs as $lang => $val ){
        if( $val === '' ){
          $langs[$lang] = 1;
        }
      }

      // sort list based on value
      arsort($langs, SORT_NUMERIC);
    }

    $this->request_langs = $langs;
    return $langs;
  }



  /**
   * Get the set of pages assigned to a certain page
   * @param string $index 'gp_index' of the page in question, optional - uses the current page if not passed
   * @return array $preferred_pages sorted descending by its subarray key 'q_factor', including the passed / current page
   *
   */
  public function GetPreferredPages($index = false){
    global $page;

    $preferred_pages = array();

    if( !$index && is_object($page) ){
      if( $page->pagetype == 'admin_display' ){
        msg('Warning - OPL called on Admin Page!');
        return $preferred_pages;
      }
      $index = $page->gp_index;
    }

    $linked_pages = $this->GetLinkedPages($index); 
    foreach( $linked_pages as $index => $lang ){
      $preferred_pages[$lang] = array(
        'index'     => $index,
        'q_factor'  => (array_key_exists($lang, $this->request_langs) ? $this->request_langs[$lang] : 0),
      );
    }
    $preferred_pages = $this->SortArrayBySubkeyValue($preferred_pages, 'q_factor', true); // true = reverse  

    return $preferred_pages;
  }



  /**
   * Get the URL for automatic redirection
   * @param string $slug the slug of a requested page (empty for homepage)
   * @param string|boolean
   *  string $auto_redir_url of the most suitable redirection based on associated pages and the web browser's accepted languages
   *  boolean false if
   *    * user is logged-in or
   *    * the current page is already the most suitable one or
   *    * none of the associated pages' language meets the web browser's preferred languages
   *
   */
  public function GetAutoRedirUrl($slug){
    global $config, $gp_index;

    // get the page $title
    $current_title = ($slug == '') ? $config["homepath"] : $slug;
    // msg('GetAutoRedirUrl --> $current_title = ' . $current_title);

    $current_index = !empty($gp_index[$current_title]) ? $gp_index[$current_title] : false;
    // msg('GetAutoRedirUrl --> $current_index = ' . pre($current_index));

    // exit when the page index doesn't exist (== Admin page)
    if( !$current_index ){
      // msg('GetAutoRedirUrl --> is Admin Page);
      return false;
    }

    // get preferred pages
    $preferred_pages  = $this->GetPreferredPages($current_index);
    // msg('GetAutoRedirUrl --> $preferred_pages = ' . pre($preferred_pages));

    $do_redirect = true;
    foreach( $preferred_pages as $lang => $page_info ){

      if( $page_info['q_factor'] > 0 ){
        $preferred_index = $page_info['index'];
        $preferred_title = \gp\tool::IndexToTitle($preferred_index);

        // don't auto-redirect when logged-in
        if( \gp\tool::LoggedIn() ){
          $this->page_would_redir_to = $preferred_index;
          // msg('GetAutoRedirUrl --> would auto-redirect to ' . $this->page_would_redir_to);
          $do_redirect = false;
        }

        // don't auto-redirect when we are already on the most preferred page
        if( $current_title == $preferred_title ){
          // msg('GetAutoRedirUrl --> already on the most preferred page');
          $this->page_is_preferred = true;
          $do_redirect = false;
        }

        if( !$do_redirect ){
          return false;
        }

        $auto_redir_url = \gp\tool::GetUrl($preferred_title) . '?redir=auto';
        // msg('redirect (HTTP 302) to ' . $auto_redir_url . ' (' . $lang . ', ' . $page_info['q_factor'] . ')');

        return $auto_redir_url;
      }

    }

    return false;
  }

}
