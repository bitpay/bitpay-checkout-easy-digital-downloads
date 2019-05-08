
function showBPInvoice($env, $invoiceID,$orderId,$cart_url,$fix_url) {
   
		var payment_status = null;
        var is_paid = false
		window.addEventListener("message", function(event) {
		    payment_status = event.data.status;
            if(payment_status == 'paid'){
                is_paid = true
            }
		}, false);
		//hide the order info
		bitpay.onModalWillEnter(function() {
            jQuery('#primary').css('opacity', '0.3');
        });
        
        bitpay.onModalWillLeave(function() {
            
		    if (is_paid == true) {
		        jQuery('#primary').css('opacity', '1');
		    } else {
               // window.location = $cart_url;
               var myKeyVals = {
                orderid: $orderId
            }
         
            var saveData = jQuery.ajax({
                type: 'POST',
                url: $fix_url,
                data: myKeyVals,
                dataType: "text",
                success: function(resultData) {
                    window.location = $cart_url;
                }
            });
		    }
		});



    if($env == 'test'){
        bitpay.enableTestMode()
    }
    bitpay.showInvoice($invoiceID)
}
