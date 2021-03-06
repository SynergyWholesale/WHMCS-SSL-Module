<script type="text/javascript" src="/assets/js/StatesDropdown.js"></script>
<form method="post" action="?action=productdetails&id={$serviceid}&modop=custom&a=reissue&step=2">
    <h2>Request New Certificate Process</h2>

    <p>You must have a valid "CSR" (Certificate Signing Request) to configure your SSL Certificate. The CSR is an encrypted piece of text that is generated by the web server where the SSL Certificate will be installed. If you do not already have a CSR, you must generate one or ask your web hosting provider to generate one for you.</p>
    <div class="form-group">
        <label for="inputCsr">CSR</label>
        <textarea name="csr" id="inputCsr" rows="7" class="form-control">-----BEGIN CERTIFICATE REQUEST-----

-----END CERTIFICATE REQUEST-----</textarea>
    </div>
    <h2>CSR Generation Data</h2>
    <p>If you want to generate a new CSR, you need to fill up all fields below. If you already have a CSR, you can paste it above and proceed.</p>
    <fieldset class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-4 control-label" for="inputEmail">Generate New CSR</label>
                <div class="col-sm-8">
                    <input type="checkbox" name="generateNew" id="gen">
                </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label" for="inputEmail">Email Address</label>
            <div class="col-sm-8">
                <input type="text" class="form-control" name="email" id="inputEmail" value="{$email}" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label" for="inputDomain">Domain Name</label>
            <div class="col-sm-8">
                <input type="text" class="form-control" name="Domain" id="inputDomain" value="{$commonName}" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label" for="inputOrgName">Organisation Name</label>
            <div class="col-sm-8">
                <input type="text" class="form-control" name="orgname" id="inputOrgName" value="{$organisation}" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label" for="inputOrganisationUnit">Organisation Unit</label>
            <div class="col-sm-8">
                <input type="text" class="form-control" name="orgunit" id="inputOrganisationUnit" value=""  />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label" for="inputCity">City</label>
            <div class="col-sm-8">
                <input type="text" class="form-control" name="city" id="inputCity" value="{$city}" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label" for="stateSelect">State/Region</label>
            <div class="col-sm-8">
                <input type="text" class="form-control" name="state" id="stateSelect" value="{$state}" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label" for="countrySelect">Country</label>
            <div class="col-sm-8">
                {$countrydropdown}
            </div>
        </div>
    </fieldset>
    <p class="text-center">
        <input name="reissue" type="submit" value="Click to Continue >>" class="btn btn-primary" id="submit" />
    </p>
</form>
{literal}
<script type="text/javascript">
    jQuery('document').ready(function() {
        initCountry('{/literal}{$code}{literal}');
        jQuery('#gen').click(function() {
            jQuery('#inputCsr').prop('disabled', !jQuery('#inputCsr').prop('disabled'));
        });

        jQuery('form').submit(function() {
            jQuery('#submit').prop('disabled',true);
        });
    });
</script>
{/literal}