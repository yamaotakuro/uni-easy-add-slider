<?php
/**
* @package UNIONNET_AddPlugin
* @version 1.0
*/
/*
Plugin Name: AddSliderField
Description: 管理画面サイドメニューにスライダー用のカスタムフィールドを追加する
Author: Takuro Yamao
Version: 1.0
*/


add_action('init', 'AddSliderField::init');

class AddSliderField{

  static function init(){
    return new self();
  }
  public function __construct(){
    if (is_admin() && is_user_logged_in()) {
      add_action('admin_menu', [$this, 'uni_add_slider_pages']);
      add_action('admin_enqueue_scripts', [$this, 'admin_load_styles']);
      add_action('admin_enqueue_scripts', [$this, 'admin_load_scripts']);
      add_action('admin_print_footer_scripts', [$this, 'admin_script']);
      add_action('wp_ajax_uni_add_field_update_options', [$this, 'update_options']);
    }
  }

  // メニューを追加する
  public function uni_add_slider_pages(){
    add_menu_page(
      'メインスライダー変更',
      'メインスライダー変更',
      'read',
      'uni_add_slider',
      [$this, 'uni_add_slider'],
      plugins_url( 'images/smile.png', __FILE__ )
    );
  }


  //管理画面でのJS読み込み
  public function admin_load_scripts() {
    wp_enqueue_script('media-upload');//画像アップローダー用
    wp_enqueue_style('thickbox');//画像アップローダー用
    wp_enqueue_script('jquery');
    wp_enqueue_script('UI', plugin_dir_url(__FILE__) .'js/jquery-ui.min.js');//ソート デイトピッカー用
    wp_enqueue_script('datepicker', plugin_dir_url(__FILE__) .'js/jquery.ui.datepicker-ja.min.js');// デイトピッカー用
  }

  //管理画面でのCSS読み込み
  public function admin_load_styles() {
    wp_enqueue_style('thickbox'); //画像アップローダー用
    wp_enqueue_style('UI' , plugin_dir_url(__FILE__) .'css/jquery-ui.min.css');
    wp_enqueue_style('uni_add_field' , plugin_dir_url(__FILE__) .'css/uni_add_field.css');
  }

  //管理画面のJSの実行
  public function admin_script(){
    ?>
    <script>
    (function($){
      $(function(){

        //jQueryUIのsortable呼び出し
        $('#uni_table tbody').sortable();

        $( '.uni_add_field_date_start' ).datepicker({
          changeYear: true,
          changeMonth: true,
          firstDay: 1,
          autoclose : true,
          showButtonPanel:true,
          dateFormat:"yy-mm-dd",
        });
        $( '.uni_add_field_date_end' ).datepicker({
          changeYear: true,
          changeMonth: true,
          firstDay: 1,
          autoclose : true,
          showButtonPanel:true,
          dateFormat:"yy-mm-dd",
        });

        $(document).on('click', '.uni_add_field_date_start', function(){
          $(this).datepicker({
            changeYear: true,
            changeMonth: true,
            firstDay: 1,
            autoclose : true,
            showButtonPanel:true,
            dateFormat:"yy-mm-dd",
          });
          $(this).datepicker('show');
        });

        $(document).on('click', '.uni_add_field_date_end', function(){
          $(this).datepicker({
            changeYear: true,
            changeMonth: true,
            firstDay: 1,
            autoclose : true,
            showButtonPanel:true,
            dateFormat:"yy-mm-dd",
          });
          $(this).datepicker('show');
        });

        //画像アップローダー
        $(document).on('click','.media-upload',function(){
          var click_elem = $(this);
          var imgUrl = "";
          var input = click_elem.parent().find('.uni_add_field_url');
          var image = click_elem.parent().find('.uni_add_field_elem');
            window.send_to_editor = function(html) {
              imgUrl = $('img', html).attr('src');//srcの値を取得
              if (imgUrl === undefined) {
                imgUrl = $(html).attr("src");
              }

              input.val(imgUrl);//.after('<img src="'+imgUrl+'" >');//値をセット
              image.attr('src',imgUrl);//srcを上書き
              tb_remove();
            }
            tb_show(null, 'media-upload.php?post_id=0&type=image&TB_iframe=true');
            return false;
        });

        //カラム追加用
        $('#add_calumn').on('click',function(){
          var len = parseInt($('.field_data').length);
          var tr = "";
              tr +=  '<tr class="field_data">';
              tr +=  '<th>'+ len +'</th>';
              tr +=  '<td>';
              tr +=  '<input type="hidden" name="uni_add_field['+ len +'][url]" class="uni_add_field_url" id="uni_add_field_url'+ len +'" value="">';
              tr +=  '<a class="media-upload" href="JavaScript:void(0);" rel="uni_add_field'+ len +'">画像選択</a>';
              tr +=  '<img src="" style="width:150px;" class="uni_add_field_elem">';
              tr +=  '<input type="text" name="uni_add_field['+ len +'][alt]" class="uni_add_field_alt" id="uni_add_field_alt'+ len +'" value="" placeholder="altを入力">';
              tr +=  '<input type="text" name="uni_add_field['+ len +'][link]" class="uni_add_field_link" id="uni_add_field_link'+ len +'" value="" placeholder="リンクURLを入力してください">';
              tr +=  '<input type="checkbox" class="uni_add_field_blank" id="uni_add_field['+ len +'][blank]" name="uni_add_field_blank"><label for="uni_add_field_blank">外部リンク</label>';
              tr +=  '<input type="text" name="uni_add_field['+ len +'][date_start]" class="uni_add_field_date_start" id="uni_add_field_date_start'+ len +'" value="" placeholder="開始日を入力">';
              tr +=  '<input type="text" name="uni_add_field['+ len +'][date_end]" class="uni_add_field_date_end" id="uni_add_field_date_end'+ len +'" value="" placeholder="終了日を入力">';
              tr +=  '</td>';
              tr +=  '<td><span class="delete">削除</span></td>';
              tr +=  '</tr>';
          $('#uni_table tbody').append(tr);
        });

        //設定ページでのAjax(保存する)
        function setting_update(type){
          var slideObj ={};
          $('.field_data').each(function(i){
            var arr = [];
            var urlVal = $(this).find('.uni_add_field_url').val();
            var altVal = $(this).find('.uni_add_field_alt').val();
            var linkVal = $(this).find('.uni_add_field_link').val();
            var startVal = $(this).find('.uni_add_field_date_start').val();
            var endVal = $(this).find('.uni_add_field_date_end').val();
            var blankVal = $(this).find('.uni_add_field_blank').prop('checked')
            arr.push(urlVal,altVal,linkVal,startVal,endVal,blankVal);
            slideObj[i] = arr;
          });

          $.ajax({
            url : ajaxurl,
            type : 'POST',
            data : {action : 'uni_add_field_update_options' ,uni_custum_field : slideObj  },
          })
          .done(function(data) {
            if(type =="update"){
              alert('保存しました');
            }
          })
          .fail(function() {
            window.alert('失敗しました');
          });
        }

        //保存
        $('#field_update').on('click',function(){
          setting_update('update');
        });

        //削除
        $(document).on('click','.delete',function(){
          $(this).parents('tr').remove();
        });

      });
    })(jQuery);



  </script>
  <?php
  }

  //Ajaxで受け取ったデータを保存
  public function update_options(){
    update_option('uni_custum_field',$_POST['uni_custum_field']);
    exit('保存しました。');
  }

  //保存したフィールドを取得
  public function get_settings(){
    $uni_custum_field= get_option('uni_custum_field');
    return $uni_custum_field;
  }

  // メニューがクリックされた時にコンテンツ部に表示する内容(初期表示用)
  public function uni_add_slider() {
    $uni_custum_fields = $this->get_settings();
  ?>
  <h2>トップページのスライド用</h2>

  <form method="post" action="">

    <table id="uni_table">
      <tbody>
      <?php foreach((array)$uni_custum_fields as $k => $v) : ?>
        <tr class="field_data">
          <th><?php echo $k;?></th>
          <td>
          <input type="hidden" name="uni_add_field[<?php echo $k;?>][url]" class="uni_add_field_url" id="uni_add_field_url<?php echo $k;?>" value="<?php echo $v[0];?>" />
          <a class="media-upload" href="JavaScript:void(0);" rel="uni_add_field<?php echo $k;?>">画像選択</a>
          <img src="<?php echo $this->get_attachment_image_src($v[0], 'thumbnail') ;?>" style="width:150px;" class="uni_add_field_elem">
          <input type="text" name="uni_add_field[<?php echo $k;?>][alt]" class="uni_add_field_alt" id="uni_add_field_alt<?php echo $k;?>" value="<?php echo $v[1];?>" placeholder="altを入力">
          <input type="text" name="uni_add_field[<?php echo $k;?>][link]" class="uni_add_field_link" id="uni_add_field_link<?php echo $k;?>" value="<?php echo $v[2];?>" placeholder="リンクURLを入力">
          <input type="checkbox" class="uni_add_field_blank" id="uni_add_field[<?php echo $k;?>][blank]" name="uni_add_field_blank" <?php if($v[5] == 'true') { echo 'checked';}?>><label for="uni_add_field_blank">外部リンク</label>
          <input type="text" name="uni_add_field[<?php echo $k;?>][date_start]" class="uni_add_field_date_start" id="uni_add_field_date_start<?php echo $k;?>" value="<?php echo $v[3];?>" placeholder="開始日を入力">
          <input type="text" name="uni_add_field[<?php echo $k;?>][date_end]" class="uni_add_field_date_end" id="uni_add_field_date_end<?php echo $k;?>" value="<?php echo $v[4];?>" placeholder="終了日を入力">

          </td>
          <td>
          <span class="delete">削除</span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>

    </table>
    <ul class="btn_list">
      <li><input type="button" class="button button-primary" value="保存" id="field_update" name="update"></li>
      <li><input type="button" class="button button-primary" value="カラム追加" id="add_calumn"></li>
    </ul>

  </form>
  <?php
  }

  /**
   * 画像のURLからattachemnt_idを取得する
   *
   * @param string $url 画像のURL
   * @return int attachment_id
   */
  public function get_attachment_id($url)
  {
    global $wpdb;
    $sql = "SELECT ID FROM {$wpdb->posts} WHERE guid = %s";
      $post_name = $url;
      $id = (int)$wpdb->get_var($wpdb->prepare($sql, $post_name));

      if($id == 0){
      $sql = "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s";
      preg_match('/([^\/]+?)(-e\d+)?(-\d+x\d+)?(\.\w+)?$/', $url, $matches);
      $post_name = $matches[1];
      $id = (int)$wpdb->get_var($wpdb->prepare($sql, $post_name));
      }
    return $id;
  }

  /**
   * 画像のURLのサイズ違いのURLを取得する
   *
   * @param string $url 画像のURL
   * @param string $size 画像のサイズ (thumbnail, medium, large or full)
   */
  public function get_attachment_image_src($url, $size) {
    $image = wp_get_attachment_image_src($this->get_attachment_id(esc_url($url)), $size);

    return $image[0];
  }
}
