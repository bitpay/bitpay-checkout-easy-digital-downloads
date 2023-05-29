<?php

class BitPayEddPrintEnqueueScripts
{
    public function execute(): void
    {
        wp_enqueue_script('remote-bitpayquickpay-js', 'https://bitpay.com/bitpay.min.js', null, null, true);
        wp_enqueue_script('bitpayquickpay-js', plugins_url('../js/bitpay_edd.js', __FILE__), null, null, false);
        ?>
        <script type="text/javascript">
            if (window.location.href.indexOf("&bpedd=1&invoiceID=") > -1) {
                setTimeout(function () {
                        jQuery('#primary').css('opacity', '0.3');
                    },
                    200
                );

                var urlParams = new URLSearchParams(window.location.search);
                var $oid = urlParams.get('order_id')
                var $iid = urlParams.get('invoiceID')
                $cart_url = "<?php echo edd_get_checkout_uri(); ?>"
                $fix_url = "<?php echo get_home_url() . '/wp-json/bitpay-edd/cartfix/update'; ?>"

                setTimeout(function () {
                        showBPInvoice('<?php echo BitPayEndpoint::getType($this->getOption('test_mode')); ?>', $iid, $oid, $cart_url, $fix_url)
                    },
                    250
                );
            }
        </script>
        <?php
    }

    private function getOption(string $optionName): string
    {
        global $edd_options;

        return $edd_options[$optionName];
    }
}
