<fieldset class="width3">
    <legend><img src="../img/admin/warning.gif"/>{l s='Information'}</legend>
    {l s='Please login to your Paymentwall Merchant Account and go to My Projects section.'}
    <br/>
    {l s='Click "Settings" button under your project and fill all the required fields.' d='Modules.Paymentwall.Admin'}

    <br/>
    <ul>
        <li>{l s='Select "Digital Goods" (or other API) under "Your API".' d='Modules.Paymentwall.Admin'}</li>
        <li>{l s='Set the Pingback URL to '}{$base_url}{l s='modules/paymentwall/pingback.php'}</li>
        <li>{l s='Set the Signature Version to 3.' d='Modules.Paymentwall.Admin'}</li>
    </ul>

    {l s='Project Key and Secret Key are available in My Projects section under your Paymentwall Merchant Account.'}
    <br/><br/>
    {l s='Widget Code is available in the Column code of Widgets section of your project.'}
</fieldset>