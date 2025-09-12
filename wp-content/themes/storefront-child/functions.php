<?php
// 1) CSS padre + hijo
add_action('wp_enqueue_scripts', function () {
    $parent_style = 'storefront-style';
    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css', [], wp_get_theme('storefront')->get('Version'));
    wp_enqueue_style('storefront-child-style', get_stylesheet_uri(), [$parent_style], wp_get_theme()->get('Version'));
    wp_enqueue_style('storefront-child-custom', get_stylesheet_directory_uri() . '/assets/css/custom.css', ['storefront-child-style'], wp_get_theme()->get('Version'));
});

// 2) Fuentes
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'storefront-child-google-fonts',
        'https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Poppins:wght@400;500;600&display=swap',
        [],
        null
    );
});

// 3) Soportes
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('woocommerce');
});

// 4) SVG (opcional)
add_filter('upload_mimes', function ($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});

// Miniatura en checkout/carrito
add_filter('woocommerce_cart_item_name', function($name, $cart_item){
    if (is_checkout() || is_cart()) {
        $product = $cart_item['data'];
        if ($product && !is_wp_error($product)) {
            $thumb = $product->get_image('woocommerce_thumbnail', [
                'style' => 'width:48px;height:48px;object-fit:cover;border-radius:8px;margin-right:8px;vertical-align:middle;'
            ]);
            $name = '<span class="dp-item-thumb">'.$thumb.'</span>'.$name;
        }
    }
    return $name;
}, 10, 3);

// Loader
add_action('wp_footer', function(){ ?>
    <script>
      (function() {
        var loader = document.getElementById('site-loader');
        if (!loader) return;
        var MIN_TIME = 1500, startTime = Date.now();
        var safety = setTimeout(hideLoader, 8000);
        window.addEventListener('load', function(){
          var remaining = MIN_TIME - (Date.now() - startTime);
          setTimeout(hideLoader, remaining > 0 ? remaining : 0);
        });
        function hideLoader(){ if (!loader) return; loader.classList.add('hide'); clearTimeout(safety); document.documentElement.classList.remove('is-loading'); }
        document.documentElement.classList.add('is-loading');
        var css = document.createElement('style'); css.innerHTML = ".is-loading, .is-loading body{overflow:hidden;}"; document.head.appendChild(css);
      })();
    </script>
<?php });

// Forzar habilitar la pasarela (pruebas)
add_filter('woocommerce_available_payment_gateways', function($gws){
    if(isset($gws['disruptive_payments'])) $gws['disruptive_payments']->enabled = 'yes';
    return $gws;
});

if (!defined('ABSPATH')) exit;

define('DP_API_BASE', 'https://my.disruptivepayments.io/api');

// Registrar gateway
add_filter('woocommerce_payment_gateways', function ($methods) {
  $methods[] = 'WC_Gateway_Disruptive_Payments';
  return $methods;
});

// Definir clase
add_action('init', function () {
  if (!class_exists('WC_Payment_Gateway')) {
    if (function_exists('WC') && method_exists(WC(), 'plugin_path')) {
      @include_once WC()->plugin_path() . '/includes/abstracts/abstract-wc-payment-gateway.php';
    }
  }
  if (!class_exists('WC_Payment_Gateway') || class_exists('WC_Gateway_Disruptive_Payments')) return;

  class WC_Gateway_Disruptive_Payments extends WC_Payment_Gateway {

    public function __construct() {
      $this->id                 = 'disruptive_payments';
      $this->method_title       = 'Disruptive Payments (Crypto)';
      $this->method_description = 'Pagos con criptomonedas usando Disruptive (QR + estado).';
      $this->has_fields         = true;
      $this->supports           = ['products'];

      $this->init_form_fields();
      $this->init_settings();

      $this->enabled      = $this->get_option('enabled', 'no');
      $this->title        = $this->get_option('title', 'Criptomonedas (Disruptive)');
      $this->description  = $this->get_option('description', 'BBBC / USDT en TRC20, ERC20, BSC y Polygon.');
      $this->client_api   = $this->get_option('client_api', '');
      $this->currency_map = $this->get_option('currency_map', '{}');

      add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
      add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function init_form_fields() {
      $default_map = json_encode([
        'BBBC_Polygon' => [
          'label' => 'BBBC (Polygon)',
          'network' => 'POLYGON',
          'smartContractAddress' => '0x3929d67c9cB39199165211f7f44067ae4E3197F8'
        ],
        'USDT_TRC20' => [
          'label' => 'USDT (TRC20)',
          'network' => 'TRX',
          'smartContractAddress' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
        ],
        'USDT_ERC20' => [
          'label' => 'USDT (ERC20)',
          'network' => 'ETH',
          // minúsculas ok también en ETH
          'smartContractAddress' => '0xdac17f958d2ee523a2206206994597c13d831ec7'
        ],
        'USDT_BSC' => [
          'label' => 'USDT (BSC)',
          'network' => 'BSC',
          'smartContractAddress' => '0x55d398326f99059ff775485246999027b3197955'
        ],
        'USDT_Polygon' => [
          'label' => 'USDT (Polygon)',
          'network' => 'POLYGON',
          // ¡minúsculas para evitar checksum error!
          'smartContractAddress' => '0xc2132d05d31c914a87c6611c10748aeb04b58e8f'
        ],
      ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

      $this->form_fields = [
        'enabled' => [
          'title'   => 'Activar/Desactivar',
          'type'    => 'checkbox',
          'label'   => 'Activar Disruptive Payments',
          'default' => 'no',
        ],
        'title' => [
          'title'   => 'Título en checkout',
          'type'    => 'text',
          'default' => 'Criptomonedas (Disruptive)',
        ],
        'description' => [
          'title'   => 'Descripción',
          'type'    => 'textarea',
          'default' => 'Paga con BBBC/USDT, se generará un QR.',
        ],
        'client_api' => [
          'title'       => 'Client-API',
          'type'        => 'text',
          'description' => 'API Key de Disruptive',
          'default'     => '',
        ],
        'currency_map' => [
          'title'       => 'Monedas disponibles (JSON)',
          'type'        => 'textarea',
          'description' => 'Direcciones de contrato por red',
          'default'     => $default_map,
        ],
      ];
    }

    public function payment_fields() {
      echo wpautop(wp_kses_post($this->description));
      $map = json_decode($this->currency_map, true) ?: [];
      echo '<fieldset class="dp-crypto-options">';
      echo '<p><label for="dp_currency_key"><strong>Elige moneda/red:</strong></label></p>';
      echo '<select name="dp_currency_key" id="dp_currency_key" required style="max-width:100%;">';
      echo '<option value="">— Selecciona una opción —</option>';
      foreach ($map as $key => $cfg) {
        $label = esc_html($cfg['label'] . ' · ' . $cfg['network']);
        echo '<option value="' . esc_attr($key) . '">' . $label . '</option>';
      }
      echo '</select>';
      echo '</fieldset>';
    }

    public function validate_fields() {
      if (empty($_POST['dp_currency_key'])) {
        wc_add_notice('Selecciona una moneda/red.', 'error');
        return false;
      }
      return true;
    }

    public function process_payment($order_id) {
      $order = wc_get_order($order_id);
      $amount_usd = round((float)$order->get_total(), 2);

      $map    = json_decode($this->currency_map, true) ?: [];
      $chosen = sanitize_text_field($_POST['dp_currency_key'] ?? '');
      if (empty($map[$chosen])) {
        wc_add_notice('Moneda/red no válida.', 'error');
        return;
      }

      $smartAddress = $map[$chosen]['smartContractAddress'];
      $network      = $map[$chosen]['network'];

      $resp = $this->dp_create_single_payment([
        'network'              => $network,
        'fundsGoal'            => $amount_usd,
        'smartContractAddress' => $smartAddress,
      ], $order_id);

      if (!$resp || empty($resp['data']['address'])) {
        wc_add_notice('Error al crear el pago en Disruptive.', 'error');
        return;
      }

      $address   = sanitize_text_field($resp['data']['address']);
      $paymentId = !empty($resp['data']['id']) ? sanitize_text_field($resp['data']['id']) : '';

      $order->update_meta_data('_dp_address', $address);
      $order->update_meta_data('_dp_network', $network);
      $order->update_meta_data('_dp_amount_usd', $amount_usd);
      $order->update_meta_data('_dp_currency_key', $chosen);
      $order->update_meta_data('_dp_payment_id', $paymentId);
      $order->save();

      $order->update_status('on-hold', 'Esperando pago cripto.');
      WC()->cart->empty_cart();

      return ['result' => 'success', 'redirect' => $this->get_return_url($order)];
    }

    // POST /payments/single con client-api-key
    private function dp_create_single_payment($payload, $order_id = 0) {
      $api_key = trim($this->client_api);
      if (empty($api_key)) { $api_key = apply_filters('dp_client_api_override', ''); }

      $logger  = wc_get_logger();
      $res = wp_remote_post(DP_API_BASE.'/payments/single', [
        'headers' => [
          'client-api-key' => $api_key,
          'content-type'   => 'application/json',
        ],
        'timeout' => 30,
        'body'    => wp_json_encode($payload),
      ]);

      if (is_wp_error($res)) {
        $logger->error('[DP] single WP_Error: '.$res->get_error_message(), ['source'=>'dp','order_id'=>$order_id]);
        return null;
      }

      $code = wp_remote_retrieve_response_code($res);
      $raw  = wp_remote_retrieve_body($res);
      $body = json_decode($raw, true);

      if ($code>=200 && $code<300 && !empty($body['data']['address'])) {
        return $body;
      }

      $logger->error('[DP] single HTTP '.$code.' BODY: '.$raw, ['source'=>'dp','order_id'=>$order_id]);
      if ($order_id && ($order = wc_get_order($order_id))) {
        $order->add_order_note('Disruptive: fallo al crear el pago. Revisa Estado → Registros (source: dp).');
      }
      return null;
    }

    // FRONT assets + localización aquí mismo
    public function enqueue_assets() {
      if (is_checkout() || is_order_received_page()) {
        wp_enqueue_style('dp-responsive', get_stylesheet_directory_uri() . '/css/responsive.css', [], '1.0');

        $handle = 'dp-payment-js';
        wp_register_script($handle, get_stylesheet_directory_uri() . '/js/payment-methods.js', ['jquery'], '1.0', true);
        wp_localize_script($handle, 'DP_AJAX', [
          'url'   => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('dp_nonce'),
        ]);
        wp_enqueue_script($handle);
      }
    }
  }
}, 0);

// Bloque QR en Gracias / Ver pedido
add_action('woocommerce_thankyou', 'dp_show_qr_block', 20);
add_action('woocommerce_view_order', 'dp_show_qr_block', 20);
function dp_show_qr_block($order_id){
  if (!$order_id) return;
  $order   = wc_get_order($order_id);
  $address = $order->get_meta('_dp_address');
  $network = $order->get_meta('_dp_network');
  $amount  = $order->get_meta('_dp_amount_usd');
  if (!$address) return;

  $qr_src = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode($address);

  echo '<section class="dp-qr-section">';
  echo '<h2>Pago con Cripto</h2>';
  echo '<p><strong>Red:</strong> ' . esc_html($network) . ' | <strong>Meta (USD):</strong> ' . esc_html($amount) . '</p>';
  echo '<div class="dp-qr-wrap"><img class="dp-qr-img" src="' . esc_url($qr_src) . '" alt="QR de pago"><p class="dp-qr-address">' . esc_html($address) . '</p></div>';
  echo '<button class="button button-primary dp-check-status" data-order-id="' . esc_attr($order_id) . '">Revisar pago</button>';
  echo '<div id="dp-status-modal" class="dp-modal" style="display:none;"><div class="dp-modal__inner"><div class="dp-modal__content"><pre class="dp-status-pre"></pre><button class="button dp-modal-close">Cerrar</button></div></div></div>';
  echo '</section>';
}

// Metabox Admin
add_action('add_meta_boxes', function () {
  add_meta_box('dp_check_payment_box', 'Disruptive Payments', 'dp_admin_box_content', 'shop_order', 'side', 'high');
});
function dp_admin_box_content($post){
  $order = wc_get_order($post->ID);
  $address = $order->get_meta('_dp_address');
  if (!$address) { echo '<p>Sin pago cripto.</p>'; return; }
  echo '<p><strong>Address:</strong><br><code>' . esc_html($address) . '</code></p>';
  echo '<p><a href="#" class="button button-primary dp-check-status" data-order-id="' . esc_attr($post->ID) . '">Revisar pago</a></p>';
  echo '<div id="dp-status-modal" class="dp-modal" style="display:none;"><div class="dp-modal__inner"><div class="dp-modal__content"><pre class="dp-status-pre"></pre><button class="button dp-modal-close">Cerrar</button></div></div></div>';
}

// AJAX status
function dp_ajax_check_payment(){
  check_ajax_referer('dp_nonce', '_ajax_nonce');

  $order_id = absint($_POST['order_id'] ?? 0);
  if (!$order_id) wp_send_json_error(['message'=>'Pedido inválido']);

  $order = wc_get_order($order_id);
  if (!$order) wp_send_json_error(['message'=>'Pedido no encontrado']);

  $address = $order->get_meta('_dp_address');
  $network = strtoupper($order->get_meta('_dp_network'));
  if (!$address) wp_send_json_error(['message'=>'El pedido no tiene address de pago']);
  if (!$network) wp_send_json_error(['message'=>'El pedido no tiene network']);

  $client_api = '';
  $gws = WC()->payment_gateways()->payment_gateways();
  if (!empty($gws['disruptive_payments'])) $client_api = $gws['disruptive_payments']->client_api;
  if (empty($client_api)) $client_api = apply_filters('dp_client_api_override', '');

  $url = add_query_arg(['network'=>$network,'address'=>$address], DP_API_BASE.'/payments/status');

  $res = wp_remote_get($url, [
    'headers' => [
      'client-api-key' => $client_api,
      'content-type'   => 'application/json',
    ],
    'timeout' => 20,
  ]);

  if (is_wp_error($res)) wp_send_json_error(['message'=>'Error al conectar']);

  $code = wp_remote_retrieve_response_code($res);
  $body = json_decode(wp_remote_retrieve_body($res), true);

  if ($code < 200 || $code >= 300 || empty($body['data'])) {
    $msg = !empty($body['errorMessage']) ? $body['errorMessage'] : 'Respuesta inválida';
    wp_send_json_error(['message'=>$msg]);
  }

  $data = $body['data'];
  $captured = floatval($data['amountCaptured'] ?? 0);

  if ($captured > 0 && $order->has_status(['on-hold','pending','failed'])) {
    $order->payment_complete();
    $order->add_order_note('Pago recibido vía Disruptive. amountCaptured: '.$captured);
  }

  wp_send_json_success(['data'=>$data]);
}
add_action('wp_ajax_dp_check_payment', 'dp_ajax_check_payment');
add_action('wp_ajax_nopriv_dp_check_payment', 'dp_ajax_check_payment');

// Admin lista de pedidos: assets + localización
add_action('admin_enqueue_scripts', function($hook){
  if ($hook === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type']==='shop_order') {
    wp_enqueue_style('dp-responsive', get_stylesheet_directory_uri() . '/css/responsive.css', [], '1.0');
    $handle = 'dp-payment-js';
    wp_register_script($handle, get_stylesheet_directory_uri() . '/js/payment-methods.js', ['jquery'], '1.0', true);
    wp_localize_script($handle, 'DP_AJAX', [
      'url'   => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('dp_nonce'),
    ]);
    wp_enqueue_script($handle);
  }
});

// Columna "Cripto" en Pedidos + botón Revisar pago
add_filter('manage_edit-shop_order_columns', function($cols){
  $cols['dp_crypto'] = 'Cripto';
  return $cols;
}, 20);
add_action('manage_shop_order_posts_custom_column', function($col, $post_id){
  if ($col !== 'dp_crypto') return;
  $order = wc_get_order($post_id);
  $addr  = $order ? $order->get_meta('_dp_address') : '';
  $net   = $order ? $order->get_meta('_dp_network') : '';
  if (!$addr) { echo '<em>—</em>'; return; }
  echo '<div><code style="font-size:11px">'.esc_html($net).'</code></div>';
  echo '<button class="button button-small dp-check-status" data-order-id="'.esc_attr($post_id).'">Revisar pago</button>';
  echo '<div class="dp-inline-result" style="margin-top:6px;font-size:11px;color:#555;"></div>';
  echo '<div id="dp-status-modal" class="dp-modal" style="display:none;"><div class="dp-modal__inner"><div class="dp-modal__content"><pre class="dp-status-pre"></pre><button class="button dp-modal-close">Cerrar</button></div></div></div>';
}, 10, 2);

// Fix share cart (doble ?)
add_action('init', function () {
  if (empty($_SERVER['REQUEST_URI'])) return;
  $uri = $_SERVER['REQUEST_URI'];
  if (preg_match('#\?page_id=\d+\?share=([A-Za-z0-9]+)#', $uri, $m)) {
    $token    = $m[1];
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    wp_safe_redirect(add_query_arg('share', $token, $cart_url), 302);
    exit;
  }
});


/* =========================
 * 1) Metabox también en HPOS
 * ========================= */
add_action('add_meta_boxes', function () {
    // Legacy (post.php)
    add_meta_box('dp_check_payment_box', 'Disruptive Payments', 'dp_admin_box_content', 'shop_order', 'side', 'high');

    // HPOS (wc-orders)
    if (function_exists('wc_get_page_screen_id')) {
        $screen = wc_get_page_screen_id('shop-order');
        add_meta_box('dp_check_payment_box', 'Disruptive Payments', 'dp_admin_box_content', $screen, 'side', 'high');
    }
});

/* =============================================
 * 2) Columna "Cripto" también en lista HPOS
 * ============================================= */
// Columna "Cripto" en lista HPOS (WooCommerce → Orders)
add_filter('manage_woocommerce_page_wc-orders_columns', function($cols){
    $cols['dp_crypto'] = 'Cripto';
    return $cols;
}, 20);

add_action('manage_woocommerce_page_wc-orders_custom_column', function($column, $order){
    if ($column !== 'dp_crypto') return;

    // En HPOS $order puede ser objeto o ID → normalizamos
    $order_obj = $order instanceof WC_Order ? $order : wc_get_order($order);
    if (!$order_obj) { echo '<em>—</em>'; return; }

    $id   = $order_obj->get_id();
    $addr = $order_obj->get_meta('_dp_address');
    $net  = $order_obj->get_meta('_dp_network');

    if (!$addr) { echo '<em>—</em>'; return; }

    echo '<div><code style="font-size:11px">'.esc_html($net).'</code></div>';
    echo '<button class="button button-small dp-check-status" data-order-id="'.esc_attr($id).'">Revisar pago</button>';
    echo '<div class="dp-inline-result" style="margin-top:6px;font-size:11px;color:#555;"></div>';
}, 10, 2);


/* =====================================================
 * 3) Cargar JS/vars también en wc-orders y order HPOS
 * ===================================================== */
add_action('admin_enqueue_scripts', function($hook){
    $screen = get_current_screen();
    if (!$screen) return;

    $is_wc_orders_list = ($screen->id === 'woocommerce_page_wc-orders');
    $is_wc_order_edit  = in_array($screen->id, [
        'shop_order',
        function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop-order') : ''
    ], true);

    if ($is_wc_orders_list || $is_wc_order_edit) {
        wp_enqueue_style('dp-responsive', get_stylesheet_directory_uri() . '/css/responsive.css', [], '1.0');
        wp_enqueue_script('dp-payment-js', get_stylesheet_directory_uri() . '/js/payment-methods.js', ['jquery'], '1.0', true);
        wp_localize_script('dp-payment-js', 'DP_AJAX', [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dp_nonce'),
        ]);
    }
});

/* =================================================
 * 4) Modal reutilizable también en pantallas HPOS
 * ================================================= */
add_action('admin_footer', function(){
    $screen = get_current_screen();
    if (!$screen) return;

    $targets = [
        'edit-shop_order',
        'shop_order',
        'woocommerce_page_wc-orders',
        function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop-order') : ''
    ];
    if (!in_array($screen->id, $targets, true)) return;
    ?>
    <div id="dp-status-modal" class="dp-modal" style="display:none;">
      <div class="dp-modal__inner"><div class="dp-modal__content">
        <pre class="dp-status-pre"></pre><a href="#" class="button dp-modal-close">Cerrar</a>
      </div></div>
    </div>
    <?php
});

/* ==========================================
 * 5) Seguridad AJAX (verifica el nonce)
 * ========================================== */
add_action('init', function(){
    if (wp_doing_ajax()) {
        // tu handler ya existe como dp_ajax_check_payment
        // check_ajax_referer('dp_nonce', '_ajax_nonce');
    }
});
