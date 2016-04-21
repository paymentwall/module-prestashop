<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a class="pay_paymentwall" href="{$path}order_summary.php" title="{l s='Pay by Paymentwall'}">
                {l s='Pay by Paymentwall'}
            </a>
        </p>
    </div>
</div>
<style>
    .pay_paymentwall {
        background: url('{$path}images/pw_logo.png') 15px 15px no-repeat;
    }

    .pay_paymentwall:after {
        display: block;
        content: "\f054";
        position: absolute;
        right: 15px;
        margin-top: -11px;
        top: 50%;
        font-family: "FontAwesome";
        font-size: 25px;
        height: 22px;
        width: 14px;
        color: #777;
    }
</style>