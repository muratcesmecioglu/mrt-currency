<?php
/*
Plugin Name:  MrT - Currency
Plugin URI: https://murat.cesmecioglu.net
Description:  Ödeme sayfasında belirlenen kurdan TL'ye çevrilmiş hali gösterir.
Version:      0.1
Author: Murat Çeşmecioğlu
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


/*===============================
=            Eklenti            =
===============================*/
define( 'MY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

function mrt_toplamtlfiyat($order_id) {
  $eklentiayar = get_option( 'mrt_currency_options' );

  $eklentionoff = $eklentiayar['eklenti_onoff'];
  if ($eklentionoff == 1) {
    //$gunceltoplam = WC()->cart->get_cart_contents_total();
    $gunceltoplam = WC()->cart->get_total($context='');
    $guncelparabirimi = get_woocommerce_currency(); //EUR USD TRY
    $tlsembol = get_woocommerce_currency_symbol( "TRY" );

    $eurokur = $eklentiayar['kur_euro'];
    $dolarkur = $eklentiayar['kur_dolar'];


    if ($guncelparabirimi == "EUR") {
      $tlfiyat = number_format($gunceltoplam * $eurokur, 2, ",", ".");
    }

    if ($guncelparabirimi == "USD") {
      $tlfiyat = number_format($gunceltoplam * $dolarkur, 2, ",", ".");
    }

    if ($guncelparabirimi !== "TRY") {
      echo '<tr class="order-mrt">';
      echo '  <th colspan="2">Toplam (TL):</th>'; //WooCommerce ödeme sayfasındaki sütuna göre ayarlanmalı
      echo '  <td>'. $tlfiyat . ' ' . $tlsembol  . '</td>';
      echo '</tr>';
    }
  }
}

add_action('woocommerce_review_order_after_order_total','mrt_toplamtlfiyat');

/*----------  Ek Fonksiyon  ----------*/

function convert_to_tl( $price, $birim = false){
    $convertion_rate = 6;
    $new_price = floatval(str_replace(",",".",str_replace(".","",$price))) * $convertion_rate;
    $new_price = number_format($new_price, 2, ',', '.');
    
    if ($birim) {
      $currency = 'TRY';
      $currency_symbol = get_woocommerce_currency_symbol( $currency );
      $new_price = $new_price . ' ' . $currency_symbol;
    }
    
    return $new_price;
}



/*===========================================
=            EKLENTİ AAR SAYFASI            =
===========================================*/

//mrt_eklenti_onoff
//mrt_ayar_kur_euro
//mrt_ayar_kur_dolar

function mrt_currency_theme_menu() {
 
  add_menu_page(
    'TL Göster - Eklenti Ayarları', 
    'TL Göster',          
    'administrator',      
    'mrt_currency',      
    'mrt_currency_display'   
  );
  
} // end mrt_currency_theme_menu
add_action( 'admin_menu', 'mrt_currency_theme_menu' );

function mrt_currency_display() {
?>
  <div class="wrap">
  
    <div id="icon-themes" class="icon32"></div>
    <h2>TL Göster Eklentisi</h2>
    <?php settings_errors(); ?>
    
    <form id="mrt_currency_form" method="post" action="options.php">
      <?php

          settings_fields( 'mrt_currency_options' );
          do_settings_sections( 'mrt_currency_options' );
          
        submit_button();
      
      ?>
    </form>

    <input type="button" onclick="mrt_currency_kurguncelle()" value="Kurları Güncelle">

    <script>
	function mrt_currency_kurguncelle() {
		var xhr = new XMLHttpRequest();
		xhr.onload = function () {
			if (xhr.status >= 200 && xhr.status < 300) {
				if (window.DOMParser)
			      {
			        parser=new DOMParser();
			        xmlDoc=parser.parseFromString(xhr.response,"text/xml");
			        dolar = xmlDoc.getElementsByTagName("Currency")[0].getElementsByTagName("ForexSelling")[0].textContent;
			        euro = xmlDoc.getElementsByTagName("Currency")[3].getElementsByTagName("ForexSelling")[0].textContent;
			        document.querySelector('input#kur_euro').value = euro;
			        document.querySelector('input#kur_dolar').value = dolar;
			        console.log('Dolar Satış: ' + dolar);
			        console.log('Euro Satış: ' + euro);
			      }
			    else // Internet Explorer
			      {
			        xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
			        xmlDoc.async=false;
			        xmlDoc.loadXML(xhr.response);
			        dolar = xmlDoc.getElementsByTagName("Currency")[0].getElementsByTagName("ForexSelling")[0].textContent;
			        euro = xmlDoc.getElementsByTagName("Currency")[3].getElementsByTagName("ForexSelling")[0].textContent;
			        document.querySelector('input#kur_euro').value = euro;
			        document.querySelector('input#kur_dolar').value = dolar;
			        console.log('Dolar Satış: ' + dolar);
			        console.log('Euro Satış: ' + euro);
			      }
			} else {
				console.log('Olmadı..');
			}
			console.log('Kurları güncelle');
		};
		xhr.open('GET', '<?php echo plugins_url( 'mrt-currency/mrt-currency-api.php', _FILE_ ); ?>');
		xhr.send();
	}
    </script>
    
  </div><!-- /.wrap -->
<?php
} // end mrt_currency_theme_display

function mrt_currency_theme_default_display_options() {
  
  $defaults = array(
    'eklenti_onoff'   =>  '',
    'kur_euro' => '',
    'kur_dolar' => '',
  );
  
  return apply_filters( 'mrt_currency_theme_default_display_options', $defaults );
  
} // end mrt_currency_theme_default_display_options

function mrt_currency_initialize_theme_options() {
  if( false == get_option( 'mrt_currency_options' ) ) {  
    add_option( 'mrt_currency_options', apply_filters( 'mrt_currency_theme_default_display_options', mrt_currency_theme_default_display_options() ) );
  } // end if

  add_settings_section(
    'genel_ayarlar',    
    'TL Göster',  
    'mrt_currency_general_options_callback', 
    'mrt_currency_options'   
  );
  
  add_settings_field( 
    'mrt_eklenti_onoff',            
    'Eklenti Açık/Kapalı',           
    'mrt_currency_toggle_header_callback',
    'mrt_currency_options',
    'genel_ayarlar',     
    array(               
      "Açık",
    )
  );

  add_settings_field( 
    'kur_euro',            
    'Eur/Try',              
    'mrt_currency_euro_callback', 
    'mrt_currency_options', 
    'genel_ayarlar'     
  );
  add_settings_field( 
    'kur_dolar',           
    'Usd/Try',             
    'mrt_currency_dolar_callback',  
    'mrt_currency_options', 
    'genel_ayarlar'     
  );
  
  
  register_setting(
    'mrt_currency_options',
    'mrt_currency_options'
  );
  
} // end mrt_currency_initialize_theme_options
add_action( 'admin_init', 'mrt_currency_initialize_theme_options' );

function mrt_currency_general_options_callback() {
  echo '<p>Ödeme ekranında aşağıdaki kurlar ile çarpılıp TL karşılığı yazılacaktır.</p>';
} // end mrt_currency_general_options_callback


function mrt_currency_toggle_header_callback($args) {
  $options = get_option( 'mrt_currency_options' );
  $html = '<input type="checkbox" id="eklenti_onoff" name="mrt_currency_options[eklenti_onoff]" value="1" ' . checked( 1, isset( $options['eklenti_onoff'] ) ? $options['eklenti_onoff'] : 0, false ) . '/>'; 
  $html .= '<label for="show_header">&nbsp;'  . $args[0] . '</label>'; 
  echo $html;
  
} // end mrt_currency_toggle_header_callback

function mrt_currency_euro_callback() {
  $options = get_option( 'mrt_currency_options' );
  echo '<input type="text" id="kur_euro" name="mrt_currency_options[kur_euro]" value="' . $options['kur_euro'] . '" />';
} // end mrt_currency_euro_callback

function mrt_currency_dolar_callback() {
  $options = get_option( 'mrt_currency_options' );
  echo '<input type="text" id="kur_dolar" name="mrt_currency_options[kur_dolar]" value="' . $options['kur_dolar'] . '" />';
} // end mrt_currency_dolar_callback


?>