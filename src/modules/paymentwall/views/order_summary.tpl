
<h1 class="page-heading">
    {l s='Order summary'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form action="#" method="post">
    <div class="box cheque-box">
        <h3 class="page-subheading">
            Paymentwall
        </h3>
        <p class="cheque-indent">
            <strong class="dark">
                You have chosen to pay by Paymentwall. Here is a short summary of your order:
            </strong>
        </p>
        <p>
            - The total amount of your order comes to:
            <span id="amount" class="price"><strong class="dark">{$totalOrder} {$currencyCode}</strong></span>
            (tax incl.)
        </p>
        <p>
            -
            We allow the following currency to be sent via Paymentwall:&nbsp;<b>{$currencyCode}</b>
        </p>
        <br/>
        {$HOOK_PW_LOCAL}
    </div>
    <p class="cart_navigation clearfix" id="cart_navigation">
        <a class="button-exclusive btn btn-default"
           href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}">
            <i class="icon-chevron-left"></i>Other payment methods
        </a>
    </p>
</form>
<script type="text/javascript">
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if (xhttp.readyState == 4 && xhttp.status == 200) {
            if ({$payment_success} == xhttp.responseText) {
                window.location.href = 'order_confirm.php?orderId={$orderId}';
            }
        }
    };
    setInterval(function () {
        xhttp.open("GET", "ajax.php?orderId={$orderId}", true);
        xhttp.send();
    }, 5000);
</script>