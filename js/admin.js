/**
 * 'Other Page Languages' plugin for Typesetter CMS
 * Author: Jürgen Krausz
 * License: GPL version 2
 *
 */

OtherPageLangs = {

  init : function(){
    $('#opl-settings-form')
      .find('.opl-settings-page-title:not([readonly]), select')
        .on('input change paste', function(){
          var $tr = $(this).closest('tr');
          OtherPageLangs.checkRow($tr)
        });

    $('.opl-settings-page-title:not([readonly])').each( function(){
      OtherPageLangs.bind_autocomplete($(this));
    });

    $gp.links.opl_settings_pagelang_set_lock = function(evt){
      var $tr = $(this).closest('tr');
      OtherPageLangs.toggleLangLock($tr);
    }

    $gp.links.opl_settings_remove_row = function(evt){
      var $tr = $(this).closest('tr');
      if( $tr.find('.opl-settings-page-title').val().trim() == '' ){
        // if page title field is empty, remove without confirmation
        $tr.remove();
        return;
      }
      var confirm_remove = confirm('Really remove this language association?'); // TODO: i18n
      if( confirm_remove ){
        $tr.remove();
        OtherPageLangs.validateForm();
      }
    };

    $gp.links.opl_settings_add_row = function(evt){
      var $tr = $(this).closest('tr');
      var $new_row = $tr.closest('tbody').find('tr').first().clone();

      $new_row.find('.opl-settings-page-info').empty();

      $new_row.find('.opl-settings-page-title')
        .val('')
        .attr('value','')
        .removeAttr('readonly')
        .on('input', function(){
          OtherPageLangs.checkRow($new_row)
        });

      $new_row.find('.opl-settings-page-lang option')
        .removeAttr('disabled');
      $new_row.find('.opl-settings-page-lang')
        .on('change', function(){
          OtherPageLangs.checkRow($new_row)
        })
        .get(0).selectedIndex = 0;

      $new_row.find('.opl-settings-pagelang-locked')
        .removeClass('opl-settings-pagelang-locked');

      $new_row.find('.opl-settings-remove-row')
        .removeClass('nodisplay');

      $tr.before($new_row);

      OtherPageLangs.bind_autocomplete($new_row.find('.opl-settings-page-title'));
      $new_row.find('.opl-settings-page-title').focus();

      OtherPageLangs.validateForm();
    };

    OtherPageLangs.validateForm();
  },


  toggleLangLock : function($tr, lock_unlock){
    var $label  = $tr.find('.opl-settings-pagelang-lock');
    var $select = $label.find('.opl-settings-page-lang');
    var is_locked = typeof(lock_unlock) != 'undefined' ? 
      !lock_unlock 
      : $label.hasClass('opl-settings-pagelang-locked');

    if( is_locked ){
      // unlock
      $label.removeClass('opl-settings-pagelang-locked');
      $select.off('click.opl_lock')
        .find('option')
          .prop('disabled', false)
          .removeAttr('disabled');
    }else{
      // lock again
      $label.addClass('opl-settings-pagelang-locked');
      $select
      .find('option').not(':selected')
        .prop('disabled', true)
        .attr('disabled', 'disabled');
    }
  },


  getTitleInfo : function(title){
    var title_info = {
      label : null,
      title : null,
      url   : null,
      lang  : null
    };

    $.each(OtherPageLangs_titles, function(i, title_arr){
      if( title == title_arr[1] ){
        title_info.label = title_arr[0];
        title_info.title = title_arr[1];
        title_info.url   = title_arr[2];
        title_info.lang  = OtherPageLangs.getLangFromTitle(title_info.title);
        return false; // = break loop
      }
    });

    return title_info;
  },


  getLangFromTitle : function(title){
    // console.log('getLangFromTitle() called with title: ', title );
    var lang = null;

    if( !'page_langs' in OtherPageLangs_config ){
      // console.log('getLangFromTitle(): page_langs does not exist in OtherPageLangs_config');
      return lang;
    }

    $.each(OtherPageLangs_config.page_langs, function(index, title_obj){
      // console.log('title_obj.lang = ', title_obj.lang);
      if( title == title_obj.title ){
        lang = title_obj.lang;
        return false; // = break loop
      }
    });

    return lang;
  },


  checkRow : function($tr){
    // console.log('checkRow called with $tr = ', $tr);

    var is_valid        = true;
    var $title_field    = $tr.find('.opl-settings-page-title');
    var $info_area      = $tr.find('.opl-settings-page-info');
    var $lang_select    = $tr.find('.opl-settings-page-lang');
    var is_current_page = $tr.find('.opl-settings-page-title').is('[readonly]');
    var title           = $tr.find($title_field).val();
    var lang            = $tr.find($lang_select).val();

    var title_info      = OtherPageLangs.getTitleInfo(title); // returns obj
    // console.log('checkRow title_info = ', title_info);

    var invalid_msg = '';
    if( title_info.title == null ){
      invalid_msg = 'This page does not exist. Select one from the dropdown'; // TODO: i18n
      $info_area.empty();
      $lang_select.val('');
      is_valid = false;
    }else if( !is_current_page ){
      $info_area.html('<a href="' + title_info.url + '" target="_blank">' + title_info.label + '</a>');
    }
    $title_field.get(0).setCustomValidity(invalid_msg);

    var title_has_lang  = title_info.lang !== null;

    if( !lang && title_has_lang ){
      // set existing lang
      $tr.find('.opl-settings-page-lang').val(title_info.lang);
      OtherPageLangs.toggleLangLock($tr, true);
    }

    OtherPageLangs.validateForm(is_valid);
  },


  validateForm : function(is_valid){
    // console.log('validateForm() called with is_valid = ', is_valid);
    var is_valid = typeof(is_valid) !== 'undefined' ? is_valid : true;

    var $form = $('#opl-settings-form');

    // check duplicate pages
    var used_titles = [];
    $form.find('.opl-settings-page-title').each(function(){
      var title = $(this).val();
      if( $.inArray(title, used_titles) !== -1 ){
        this.setCustomValidity('This page is already associated'); // TODO: i18n
        is_valid = false;
        return; // == continue
      }
      used_titles.push(title);
    });

    // check languages
    var used_langs = [];
    $form.find('.opl-settings-page-lang').each(function(){
      var selected_lang = $(this).val();
      this.setCustomValidity('');
      if( selected_lang == '' ){
        this.setCustomValidity('Please select a language'); // TODO: i18n
        is_valid = false;
        return; // == continue
      }
      if( $.inArray(selected_lang, used_langs) !== -1 ){
        this.setCustomValidity('This language is already used by another page'); // TODO: i18n
        is_valid = false;
        return; // == continue
      }
      used_langs.push(selected_lang);
    });

    return is_valid;
  },


  bind_autocomplete : function($elem){
    if( !$elem.length ){
      // console.log('$elem is empty');
      return;
    }

    $elem.autocomplete({
      source    : OtherPageLangs_titles,
      appendTo  : '#gp_admin_box',
      delay     : 100,
      minLength : 0,
      select    : function(event, ui){
        if( ui.item ){
          // $(this).val(encodeURI(ui.item[1]));
          $(this).val(ui.item[1]).trigger('input');
          event.stopPropagation();
          return false;
        }
      }
    }).focus(function(){
      $(this).data("uiAutocomplete").search($(this).val());
    }).data("ui-autocomplete")._renderItem = function(ul, item){
      return $("<li></li>")
        .data('ui-autocomplete-item', item[1])
        .append('<a>' + $gp.htmlchars(item[0]) + '<span>' + $gp.htmlchars(item[1]) + '</span></a>')
        .appendTo(ul);
    };
  }

};
