<?php
/**
 * 'Other Page Languages' plugin for Typesetter CMS
 * Author: Jürgen Krausz
 * License: GPL version 2
 *
 */

namespace Addon\OtherPageLangs;

defined('is_running') or die('Not an entry point...');


class Edit extends \Addon\OtherPageLangs\Common{

  public function __construct(){
    parent::__construct();
    $this->SetVars();
  }

  /**
   * Typesetter filter hook
   *
   */
  public function PageRunScript($cmd){
    global $page, $langmessage, $config;

    if( !\gp\tool::LoggedIn() || $page->pagetype == 'admin_display' ){
      return $cmd;
    }

    switch( $cmd ){

      case 'OPLsettings':
        $form_action =      \gp\tool::GetUrl($page->requested);
        $data_cmd =         $page->gp_index ? ' data-cmd="gppost" ' : '';
        $admin_box_close =  $page->gp_index ? 'admin_box_close ' : '';


        ob_start();

        echo '<div class="inline_box">';

        echo '<form method="post" action="' . $form_action . '" id="opl-settings-form">';

        $checked = !empty($this->config['auto_redirect']) ? ' checked="checked"' : '';
        echo  '<label class="opl-settings-autoredir-switch all_checkbox">';
        echo    '<input type="checkbox" name="auto_redirect"'. $checked . '/>';
        echo    '<span title="Global Setting: Auto-redirect visitors to the browser\'s preferred languages, if available">';  // TODO: i18n
        echo      'Automatic Redirection</span>';  // TODO: i18n
        echo  '</label>';

        echo  '<h3 class="opl-settings-heading"><i class="fa fa-language"></i> Assign related pages and languages</h3>'; // TODO: i18n

        echo    '<table id="opl-settings-table" class="bordered full_width">';

        echo      '<thead>';
        echo        '<tr>';
        echo          '<th class="gp_header">' . $langmessage['Page'] .'</th>';
        echo          '<th class="gp_header">Language</th>';
        echo          '<th class="gp_header">' . $langmessage['options'] .'</th>';
        echo        '</tr>';
        echo      '</thead>';

        echo      '<tbody>';
        foreach( $this->page_links as $index => $lang ){
          echo $this->GetLangRow($index, $lang);
        }

        echo        '<tr>';
        echo          '<td colspan="2"><em>New association</em></td>'; // TODO: i18n
        echo          '<td>';
        echo            '<a class="opl-settings-add-row" data-cmd="opl_settings_add_row">' . $langmessage['add'] . '</a>';
        echo          '</td>';
        echo        '</tr>';

        echo      '</tbody>';
        echo    '</table>';

        echo    '<p>';
        echo      '<input type="hidden" name="cmd" value="OPLsaveSettings"/>';
        echo      '<input type="submit" name="" value="' . $langmessage['save'] . '" class="gpsubmit"' . $data_cmd . '/>';
        echo      '<input type="button" class="' . $admin_box_close . 'gpcancel" name="" value="' . $langmessage['cancel'] . '" />';
        echo    '</p>';

        echo  '</form>';
        echo  '<script>OtherPageLangs.init();</script>';

        echo '</div>'; // /.inline_box

        $page->contentBuffer = ob_get_clean();
        return 'return';
        break; // useless after return but for the sake of switch->case->break grammar ;)

      case 'OPLsaveSettings':
        self::SaveConfig();
        $this->SetVars();

        $page->ajaxReplace = array();

        // update the gadgets
        $gadget_info = $this->GetGadgetInfo();
        // msg('$gadget_info = ' . pre($gadget_info));

        foreach($gadget_info as $gadget_name => $gadget_data){
          ob_start();
          \gp\tool\Output::GetGadget($gadget_name);
          $gadget_content = ob_get_clean();

          $page->ajaxReplace[] = array(
            'replace', // DO
            $gadget_data['selector'], // SELECTOR
            $gadget_content, // CONTENT
          );
        }

        // update the admin link
        $page->ajaxReplace[] = array(
          'replace', // DO
          '.opl-admin-link', // SELECTOR
          $this->GetAdminLink(true) // CONTENT
        );

        return 'return';
        break; // useless after return but for the sake of switch->case->break grammar ;)

    }

    $page->admin_links[] = $this->GetAdminLink();

    return $cmd;
  }



  /**
   * Get addon gadgets' info from Addon.ini
   * @return array
   *
   */
  public function GetGadgetInfo(){
    global $addonPathCode;

    $gadget_info = array();
    $addon_ini_contents = \gp\tool\Ini::ParseFile($addonPathCode . '/Addon.ini');
    // msg('$addon_ini_contents = ' . pre($addon_ini_contents));

    if( empty($addon_ini_contents) ){
      msg('OtherPageLangs\Edit::GetGadgetInfo(): Addon.ini parse error.');
      return $gadget_info;
    }

    foreach($addon_ini_contents as $section => $info){
      if( strpos($section, 'Gadget:') !== 0 ){
        continue;
      }
      $gadget_name = substr($section, 7);
      $gadget_info[$gadget_name] = $info;
    }

    return $gadget_info;
  }



  /**
   * Get the admin link
   * @return array|string of admin link
   *
   */
  public function GetAdminLink($get_link = false){
    global $page, $langmessage;

    $badge = '';
    if( $this->page_has_links ){

      $this->GetAutoRedirUrl($page->title); // called as sort of SetVars() for the badge indcator

      $title        = ($this->page_links_count - 1) . ' pages associated'; // TODO: i18n
      $css_classes  = 'opl-admin-link-badge';
      if( $this->page_would_redir_to ){
        $css_classes .= ' opl-badge-indicate-redir';
      }
      if( $this->page_is_preferred ){
        $css_classes .= ' opl-badge-indicate-preferred';
      }
      $badge .= '<span class="' . $css_classes .'" title="' . $title . '">';
      $badge .=   '<span class="opl-admin-link-badge-inner">' . ($this->page_links_count - 1) . '</span>';
      $badge .= '</span>';
    }

    $button_class = 'admin-link opl-admin-link' . (!$this->page_lang_is_default ? ' opl-admin-link-language-set' : '');

    $title  = $page->requested;
    $label  = '<i class="fa fa-language"></i>' . ' ' . $this->page_lang . ($this->page_has_links ? '&nbsp;' : '') . $badge;
    $query  = 'cmd=OPLsettings';
    $attrs  = 'data-cmd="gpabox" class="' . $button_class . '" title="Other Languages"'; // TODO: i18n

    if( $get_link ){
      return \gp\tool::Link($title, $label, $query, $attrs);
    }

    return array($title, $label, $query, $attrs);
  }



  /**
   * Get a page-language-link row
   * @param string $index optional page index
   * @param string $lang optional associated language
   * @return string html of table row
   *
   */
  public function GetLangRow($index=false, $lang=false){
    global $page, $config, $langmessage;

    $title              = $index ? \gp\tool::IndexToTitle($index) : '';
    $label              = $index ? \gp\tool::GetLabel($title) : '';
    $link               = $index ? \gp\tool::Link($title, $label, '', array('target' => '_blank')) : '';
    $page_info          = '<span class="opl-settings-page-info">' . $link . '</span>';
    $readonly_attr      = '';
    $remove_link_class  = '';
    $is_current_page    = $index === $page->gp_index;

    if( $is_current_page ){
      $readonly_attr      = ' readonly="readonly"';
      $page_info          = '<span class="opl-settings-page-info">' . $langmessage['Current Page'] . '</span>';
      $remove_link_class  = ' nodisplay';
    }



    // msg('$title = ' . $title . ', $page->gp_index = ' . $page->gp_index . ', $page_info = ' . $page_info);

    ob_start();

    echo      '<tr>';

    echo        '<td>';
    echo          '<input class="gpinput opl-settings-page-title" name="titles[]" ';
    echo            'type="text" value="' . $title . '"' . $readonly_attr . '/>' . $page_info;
    echo        '</td>';

    echo        '<td>';
    $locked_class = $lang ? ' opl-settings-pagelang-locked' : '';
    echo          '<label class="opl-settings-pagelang-lock' . $locked_class .'">';
    echo            '<select class="gpselect opl-settings-page-lang" name="langs[]" required="required">';
    echo              '<option value="">Select a Language</option>';
    foreach( $this->i18n as $lang_code => $lang_data ){
      $selected         = $lang_code == $lang ? ' selected="selected"' : '';
      $disabled         = $lang && $lang_code != $lang ? ' disabled="disabled"' : '';
      $is_default_lang  = $lang_code == $config['language'] ? ', default' : '';
      echo            '<option value="' . $lang_code . '"' . $selected . $disabled . '>';
      echo              $lang_data['name'] . ' (' . $lang_code . $is_default_lang . ')';
      echo            '</option>';
    }
    echo            '</select>';

    echo            '<a data-cmd="opl_settings_pagelang_set_lock">';
    echo              '<i title="unlock this language setting" class="fa fa-fw fa-lock"></i>'; // TODO: i18n
    echo              '<i title="lock this language setting" class="fa fa-fw fa-unlock-alt"></i>'; // TODO: i18n
    echo            '</a>';
    echo          '</label>';
    echo        '</td>';

    echo        '<td>';
    echo        '<a class="opl-settings-remove-row' . $remove_link_class . '" ';
    echo          'data-cmd="opl_settings_remove_row" title="Remove this language association">';
    echo          $langmessage['remove'] . '</a>';
    echo        '</td>';

    echo      '</tr>';

    return ob_get_clean();
  }



  public function SaveConfig(){
    global $page, $gp_index, $langmessage;

    if( empty($_POST['titles']) || empty($_POST['langs']) ){
      msg($langmessage['OOPS'] . ' no post values.');
      return;
    }

    $titles = $_POST['titles'];
    $langs  = $_POST['langs'];

    if( count($titles) !== count($langs) ){
      msg($langmessage['OOPS'] . ' post value count mismatch.');
      return;
    }

    $page_langs = array();
    $links = array();
    $i = 0;
    foreach( $titles as $title ){
      if( !isset($gp_index[$title]) ){
        msg($langmessage['OOPS'] . ' the page ' . htmlspecialchars($title) . ' does not exist.');
        $i++;
        continue;
      }
      $index = $gp_index[$title];
      $page_langs[$index] = array(
        'title' => $title,
        'lang'  => $langs[$i],
      );
      $links[] = $index;
      $i++;
    }

    // reverse sort by index -> newer pages will be shown first
    rsort($links);

    // check if pages are already linked
    $check_duplicates = array();
    $messages         = array();
    $replace_link_key = false;

    if( !empty($this->config['links']) ){

      // for safety: purge possible leftover invalid links subarrays that contain no or only a single page index
      foreach( $this->config['links'] as $existing_link_key => $existing_link_arr ){
        if( count($existing_link_arr) < 2 ){
          unset($this->config['links'][$existing_link_key]);
        }
      }

      // iterate through existing links subarrays
      foreach( $this->config['links'] as $existing_link_key => $existing_link_arr ){
        // check link subarray
        foreach( $existing_link_arr as $existing_key => $existing_index ){
          if( in_array($existing_index, $check_duplicates) ){
            // found a duplicate, remove it from config
            unset($this->config['links'][$existing_link_key][$existing_key]);
            $messages[] = array(
              'type'  => 'removed',
              'index' => $existing_index,
              'from'  => $existing_link_arr,
            );
          }else{
            // add page index to $check_duplicates
            $check_duplicates[] = $existing_index;
            if( in_array($existing_index, $links) ){
              // first existing link subarray found that contains a posted page index
              $replace_link_key = $existing_link_key;
            }
          }
        }
        // purge leftover empty links
        if( empty($this->config['links'][$existing_link_key]) ){
          unset($this->config['links'][$existing_link_key]);
        }
      }

      // messages
      foreach($messages as $message){
        switch( $message['type'] ){
          case 'removed':
            $linked_titles = array();
            foreach( $message['from'] as $index ){
              // strip current
              if( $index == $message['index'] ){
                continue;
              }
              $title = \gp\tool::IndexToTitle($index);
              $label = \gp\tool::Link_Page($title);
              if( isset($this->config['page_langs']) && in_array($index, $this->config['page_langs']) ){
                $label .= ' (' . $this->config['page_langs'][$index] . ')';
              }
              $linked_titles[] = '<em><nobr>' . $label . '</nobr></em>';
            }

            $linked_labels  = implode(', ', $linked_titles);
            $linked_labels  = $this->StrReplaceLast(',', ' and', $linked_labels);

            $title          = \gp\tool::IndexToTitle($message['index']);
            $page_label     = \gp\tool::Link_Page($title);

            msg('The page <em>' . $page_label .'</em> was removed from its former association with ' . $linked_labels);
            break;
        }
      }
    }

    if( count($links) > 1 ){
      if( $replace_link_key ){
        // we found a link subarray that contains one of our posted pages, so update it
        $this->config['links'][$replace_link_key] = $links;
        msg('The current page-language association was updated.');
      }else{
        // create a new link subarray array
        $this->config['links'][] = $links;
      }
    }else{
      if( $replace_link_key ){
        // a single page doesn't qualify as link, remove it
        unset($this->config['links'][$replace_link_key]);
        msg('Previous page associations have been detached.');
      }
    }


    // msg('$page_langs = ' . pre($page_langs));
    // msg('$links = ' . pre($links));

    if( isset($this->config['page_langs']) ){
      $this->config['page_langs'] = array_merge($this->config['page_langs'], $page_langs);
    }else{
      $this->config['page_langs'] = $page_langs;
    }

    // auto-redirect
    $this->config['auto_redirect'] = isset($_POST['auto_redirect']);

    if( \gp\tool\Plugins::SaveConfig($this->config) ){
      msg($langmessage['SAVED']);
    }else{
      msg($langmessage['OOPS']);
    }

    // msg('--------------------------------<br/>$config = ' . pre($this->config));

  }


}
