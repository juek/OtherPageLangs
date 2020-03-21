<?php
/**
 * 'Other Page Languages' plugin for Typesetter CMS
 * Author: Jürgen Krausz
 * License: GPL version 2
 *
 */

namespace Addon\OtherPageLangs;

defined('is_running') or die('Not an entry point...');


class Output extends \Addon\OtherPageLangs\Common{

  /**
   * Class constructor
   *
   */
  public function __construct(){
    parent::__construct();
  }



  /**
   * Typesetter filter hook
   *
   */
  public function WhichPage($path){
    // exit if the page is already redirected
    if( !empty($_GET['redir']) ){
      return $path;
    }

    // exit if auto-redirect is disabled (default) and user is not logged-in
    if( empty($this->config['auto_redirect']) && !\gp\tool::LoggedIn() ){
      return $path;
    }

    // auto redirect
    $auto_redirect_url = $this->GetAutoRedirUrl($path);
    if( $auto_redirect_url ){
      \gp\tool::Redirect($auto_redirect_url); // --> dies then
    }

    return $path;
  }



  /**
   * Typesetter action hook
   *
   */
  public function GetHead(){
    global $page, $gp_index, $gp_titles;

    \gp\tool\Plugins::css('css/user.css');

    if( \gp\tool::LoggedIn() ){
      // msg('$page = ' . pre(get_object_vars($page)));
      // msg('$gp_index = ' . pre($gp_index));
      // msg('$gp_titles = ' . pre($gp_titles));

      \gp\tool\Plugins::css('css/admin.css', false);
      \gp\tool\Plugins::js('js/admin.js', false);
      $page->head_script .= "\n" . 'var OtherPageLangs_config = ' . json_encode($this->config) . ';';

      // autocomplete values
      $opl_acvals = array();
      foreach( $gp_index as $title => $index ){
        $label = \gp\tool::GetLabel($title);
        $label = str_replace( 
          array('&lt;', '&gt;', '&quot;', '&#39;',  '&amp;'), 
          array('<',    '>',    '"',      "'",      '&'),
          $label
        );
        $url   = \gp\tool::GetUrl($title, '', false);
        // $url   = rawurldecode($url);
        $opl_acvals[] = array($label, $title, $url);
      }
      $opl_acvals_js = 'OtherPageLangs_titles = ' . json_encode($opl_acvals) . ';';
      // msg('$opl_acvals = ' . pre($opl_acvals_js));
      $page->head_script .= "\n" . $opl_acvals_js . "\n";

    }
  }



  /*
   * ##########################
   * ### Typesetter Gadgets ###
   * ##########################
   */



   /**
   * Default Gadget
   * best for non-bootstrap themes
   *
   */
  public function DefaultGadget(){
    \gp\tool\Plugins::css('css/default_gadget.css');

    $gadget_content = $this->GetGadgetContentArray();

    if( empty($gadget_content) ){
      echo '<!-- OtherPageLangs: The current page has no associated languages -->';
      return;
    }

    echo  '<div class="opl-gadget opl-default-gadget" lang="' . $gadget_content['lang'] . '" dir="' . $gadget_content['dir'] . '">';
    echo    '<div class="opl-gadget-close" title="' . $gadget_content['close'] . '" aria-label="' . $gadget_content['close'] . '" ';
    echo      'onClick="$(this).closest(\'.opl-gadget\').fadeOut()">';
    echo      $gadget_content['close'];
    echo    '</div>';
    echo    '<div class="opl-gadget-heading">' . $gadget_content['message'] . '</div>';
    echo    '<div class="opl-gadget-switch-to"><span>' . $gadget_content['switch_to'] . '</span>';

    echo      '<ul class="opl-gadget-langlist">';

    foreach($gadget_content['links'] as $link_lang => $link_data ){
      $link_inner = '<span class="opl-gadget-langlist-language">' . $link_data['language'] . '</span>'
                    . '<span class="opl-gadget-langlist-langcode">(' . $link_data['lang'] . ')<span>';
      $css_class  = 'opl-gadget-langlist-link' . ($link_data['accepted']  ? ' opl-gadget-langlist-accepted-lang' : '');

      echo  '<li>';
      echo    '<a href="' . $link_data['url'] . '" title="' . $link_data['title'] . '" class="' . $css_class . '">';
      echo      $link_inner;
      echo    '</a>';
      echo  '</li>';
    }

    echo      '</ul>';

    echo    '</div>'; // /.opl-gadget-switch-to
    echo  '</div>'; // /.opl-gadget
  }



   /**
   * Bootstrap 4 Toolbar Gadget
   * for Bootstrap-4 based themes
   *
   */
  public function BS4_NavbarGadget(){
    \gp\tool\Plugins::css('css/bs4_nav_gadget.css');

    $gadget_content = $this->GetGadgetContentArray();

    if( empty($gadget_content) ){
      echo '<!-- OtherPageLangs: The current page has no associated languages -->';
      return;
    }

    echo  '<nav class="opl-gadget opl-bs4-nav-gadget navbar navbar-light bg-light py-2 justify-content-center text-center" ';
    echo    'aria-label="Other page languages" ';
    echo    'lang="' . $gadget_content['lang'] . '" dir="' . $gadget_content['dir'] . '">';

    echo    '<div class="opl-gadget-close" title="' . $gadget_content['close'] . '" aria-label="' . $gadget_content['close'] . '" ';
    echo      'onClick="$(this).closest(\'.opl-gadget\').fadeOut()">';
    echo      $gadget_content['close'];
    echo    '</div>';

    // echo    '<div class="container">';
    echo    '<span class="navbar-text mx-2 py-2"><strong>' . $gadget_content['message'] . '</strong></span>';
    echo    '<span class="navbar-text mx-2 py-2">' . $gadget_content['switch_to'] . '</span>';

    echo      '<div class="btn-group flex-wrap" role="group" aria-label="Page links">';

    foreach($gadget_content['links'] as $link_lang => $link_data ){
      $link_inner = $link_data['language'] . '</span> <span class="small">(' . $link_data['lang'] . ')<span>';
      $css_class  = 'btn ' . ($link_data['accepted']  ? 'btn-success' : 'btn-secondary');

      echo  '<a href="' . $link_data['url'] . '" title="' . $link_data['title'] . '" class="' . $css_class . '">';
      echo    $link_inner;
      echo  '</a>';
    }

    echo    '</div>'; // /.btn-group
    // echo    '</div>'; // /.container
    echo  '</nav>'; // /.opl-gadget
  }



  /**
   * Get an array to build Gadget html output from
   * @return array
   *
   */
  public function GetGadgetContentArray(){
    global $page, $config;

    $cms_lang         = $config['language'];
    $page_index       = $page->gp_index;
    $page_lang        = $this->GetPageLang();
    $preferred_pages  = $this->GetPreferredPages();

    $content = array();

    if( count($preferred_pages) < 2 ){
      return $content;
    }

     // fallback values
    $content['lang']        = $cms_lang;
    $content['close']       = $this->i18n[$cms_lang]['close'];
    $content['message']     = $this->i18n[$cms_lang]['message'];
    $content['switch_to']   = $this->i18n[$cms_lang]['switch_to'];
    $content['dir']         = 'ltr';


    // get the most preferred lang, but not the one of the current page
    foreach( $preferred_pages as $lang => $unused ){
      // skip current page lang
      if( $lang == $page_lang ){
        continue;
      }
      $preferred_lang = $lang;
      break;
    }

    // get preferred values / translations
    if( !empty($this->i18n[$preferred_lang]) ){
      $content['lang']      = $preferred_lang;
      $content['dir']       = isset($this->i18n[$preferred_lang]['dir']) ? $this->i18n[$preferred_lang]['dir'] : 'ltr';
      $content['close']     = $this->i18n[$preferred_lang]['close'];
      $content['message']   = $this->i18n[$preferred_lang]['message'];
      $content['switch_to'] = $this->i18n[$preferred_lang]['switch_to'];
    }

    $content['links'] = array();
    foreach($preferred_pages as $lang => $page_info ){
      // skip current page lang
      if( $lang == $page_lang ){
        continue;
      }
      $index        = $page_info['index'];
      $language     = $this->i18n[$lang]['name'];
      $title        = \gp\tool::IndexToTitle($index);
      $label        = \gp\tool::GetLabel($title);
      $accepted     = $page_info['q_factor'] > 0;

      $content['links'][$lang] = array(
        'language'  => $language,
        'lang'      => $lang,
        'url'       => \gp\tool::GetUrl($title) . '?redir=' . $page_index,
        'title'     => $label,
        'accepted'  => $accepted,
      );
    }

    return $content;
  }

}
