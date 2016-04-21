<h1 class="page-heading">
    {l s='Order confirmation'}
</h1>
<p class="alert alert-success">
    {l s='Your order on %s is complete.' sprintf=$shop_name mod='paymentwall'}
</p>
<div class="box cheque-box">
    {l s='An email has been sent with this information.' mod='paymentwall'}
    <br/>{l s='Your order: '} <strong>#{$orderId}</strong>
    <br/> <strong>{l s='Thank you for payment' mod='paymentwall'}</strong>
    <br/>{l s='If you have questions, comments or concerns, please contact our' mod='paymentwall'} <a
            href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='paymentwall'}</a>.
</div>