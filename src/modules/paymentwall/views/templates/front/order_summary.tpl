{extends file='page.tpl'}
<h1 class="page-heading">
    {l s='Order summary'}
</h1>

{assign var='current_step' value='payment'}
{*{include file="$tpl_dir./order-steps.tpl"}*}

{block name='page_content'}
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
        {$HOOK_PW_LOCAL nofilter}
    </div>
    {literal}
        <script type="text/javascript">
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (xhttp.readyState == 4 && xhttp.status == 200) {
                    if ({/literal}{$payment_success}{literal} == xhttp.responseText) {
                        window.location.href = '{/literal}{$base_url}{literal}order-confirmation?orderId={/literal}{$orderId}{literal}';
                    }
                }
            };
            setInterval(function () {
                xhttp.open("GET", "ajax?orderId={/literal}{$orderId}{literal}", true);
                xhttp.send();
            }, 5000);
        </script>
    {/literal}
{/block}

