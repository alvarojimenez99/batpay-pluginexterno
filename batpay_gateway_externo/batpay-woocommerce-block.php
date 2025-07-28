<?php
/*
 * Plugin Name: BatPay Store
 * Plugin URI: https://batpay.store
 * Description: Pasarela de pago BatPay para WooCommerce.
 * Author: Álvaro Jiménez
 * Author URI: https://batpay.store
 * Version: 1.2.0
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

// Registrar la pasarela de pago al cargar los plugins.
add_action('plugins_loaded', 'batpay_gateway_init');

function batpay_gateway_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Gateway_BatPay extends WC_Payment_Gateway
    {
        public function __construct()
{
    $this->id = 'batpay';
    $this->icon = plugins_url('batpay-logo.png', __FILE__);
    $this->method_title = 'BatPay Store';
    $this->method_description = 'BatPay permite a los clientes pagar de forma segura con tarjeta de crédito.';
    $this->has_fields = false;

    // Declarar soporte para bloques de WooCommerce.
    $this->supports = array(
        'products', // Soporte básico de productos.
        'woocommerce_block_checkout', // Soporte para bloques de checkout.
        'tokenization',
        'add_payment_method',
    );

    $this->init_form_fields();
    $this->init_settings();

    $this->title = $this->get_option('title');
    $this->description = $this->get_option('description');

    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
}

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Habilitar/Deshabilitar',
                    'label' => 'Habilitar BatPay Gateway',
                    'type' => 'checkbox',
                    'default' => 'yes',
                ),
                'title' => array(
                    'title' => 'Título',
                    'type' => 'text',
                    'default' => 'Pago con Tarjeta de Débito o Créedito Internacional',
                    'description' => 'Título mostrado al cliente durante el pago.',
                ),
                'description' => array(
                    'title' => 'Descripción',
                    'type' => 'textarea',
                    'default' => 'Paga de forma segura con BatPay.',
                ),
                'llave_publica' => array(
                    'title' => 'Llave Pública',
                    'type' => 'text',
                ),
                'llave_privada' => array(
                    'title' => 'Llave Privada',
                    'type' => 'text',
                ),
                'url_retorno' => array(
                    'title' => 'Url regreso carrito de compras',
                    'type' => 'text',
                ),
                'url_proceso' => array(
                    'title' => 'Url exito compra procesada',
                    'type' => 'text',
                ),
            );
        }

        public function encriptar_cadena($cadena, $clave) {
			$ivlen = openssl_cipher_iv_length($cipher = "AES-256-GCM");
			$iv = openssl_random_pseudo_bytes($ivlen);
			$cadena_encriptada = openssl_encrypt($cadena, $cipher, $clave, $options = 0, $iv, $tag);
			return base64_encode($iv . $tag . $cadena_encriptada);
		}
		
		public function desencriptar_cadena($cadena_encriptada, $clave) {
			$cadena_encriptada = base64_decode($cadena_encriptada);
			$ivlen = openssl_cipher_iv_length($cipher = "AES-256-GCM");
			$iv = substr($cadena_encriptada, 0, $ivlen);
			$taglen = 16;
			$tag = substr($cadena_encriptada, $ivlen, $taglen);
			$cadena_encriptada = substr($cadena_encriptada, $ivlen + $taglen);
			return openssl_decrypt($cadena_encriptada, $cipher, $clave, $options = 0, $iv, $tag);
		}
        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);
            $nombre = $order->get_billing_first_name();
            $apellido = $order->get_billing_last_name();
         //   print_r($order->payment_method);
          //  die();
            //[payment_method] => batpay
            //[payment_method_title] => BatPay

            $total = $order->get_total();
            $order_key = $order->get_order_key();
            $publica = $this->get_option('llave_publica');
            $privada = $this->get_option('llave_privada');
            $url_retorno = $this->get_option('url_retorno');
            $url_proceso = $this->get_option('url_proceso');

            $description = "Orden #{$order_id}";

            $cadena = "{$publica}&{$privada}&{$order_id}&{$total}&{$description}&{$order_key}&{$url_retorno}&{$url_proceso}&{$nombre}&{$apellido}";
          //  $cadena_encriptada = base64_encode($cadena);
            $clave = "MiClaveSecretaDe32BytesOmas12345678";
			$cadena_encriptada=$this->encriptar_cadena($cadena, $clave);
			$cadena_encriptada=str_replace( "/", "*", $cadena_encriptada);
            $url_pago = "https://bridge.batpay.store/process_pay_new/{$order_id}/{$cadena_encriptada}";

            return array(
                'result' => 'success',
                'redirect' => $url_pago,
            );
        }
    }

    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'WC_Gateway_BatPay';
        return $gateways;
    });
}

add_action('enqueue_block_editor_assets', function () {
    if (class_exists('Automattic\\WooCommerce\\Blocks\\Package')) {
        wp_register_script(
            'batpay-integration-script',
            plugins_url('batpay-integration.js', __FILE__),
            array('wc-blocks-registry', 'wp-element'),
            '1.0.0',
            true
        );

        wp_enqueue_script('batpay-integration-script');
    }
});

// Registrar el bloque para el editor de WordPress.
add_action('init', function () {
    wp_register_script(
        'batpay-block-script',
        plugins_url('batpay-block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor'),
        '1.0.0',
        true
    );

    register_block_type('batpay/block', array(
        'editor_script' => 'batpay-block-script',
        'render_callback' => 'render_batpay_block',
    ));
});

function render_batpay_block($attributes)
{
    return '<button id="batpay-button" class="batpay-button">Pagar con BatPay</button>';
}

// Registrar el script para integrar con bloques de WooCommerce.
add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_script(
        'batpay-integration-script',
        plugins_url('batpay-integration.js', __FILE__),
        array('wc-blocks-registry'),
        '1.0.0',
        true
    );
});
?>
