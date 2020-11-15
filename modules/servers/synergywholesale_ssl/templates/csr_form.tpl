{if $csr}
    <h3>Decoded CSR details:</h3>
    <table width="100%" border="0" cellpadding="2" cellspacing="2" class="table table-striped">
        <tr>
            <td class="fieldarea">Common Name:</td>
            <td>{$csr->commonName}</td>
        </tr>
        <tr>
            <td width="150" class="fieldarea">State:</td>
            <td>{$csr->state}</td>
        </tr>
        <tr>
            <td width="150" class="fieldarea">Country:</td>
            <td>{$csr->country}</td>
        </tr>
        <tr>
            <td class="fieldarea">City:</td>
            <td>{$csr->city}</td>
        </tr>
        <tr>
            <td class="fieldarea">Organisation Unit:</td>
            <td>{$csr->organisationUnit}</td>
        </tr>
        <tr>
            <td class="fieldarea">Organisation:</td>
            <td>{$csr->organisation}</td>
        </tr> 
    </table>
{/if}
<h3>Decode CSR</h3>
<p>Input CSR string to decode it:</p>
<form action="clientarea.php?action=productdetails" method="post">
    <p>
        <textarea name="csr_string" cols="80" rows="10" class="form-control">
        </textarea>
    </p>
    <input type="hidden" name="id" value="{$serviceid}" />
    <input type="hidden" name="modop" value="custom" />
    <input type="hidden" name="a" value="DecodeCSR" />
    <p>
        <input class="btn btn-primary" type="submit" value="Decode" id="submit">
    </p>
</form>
{literal}
<style>
    .blockhover{
        background-color: transparent !important;
        cursor: not-allowed;
    }
    .block {
        color: #777 !important;
        border: transparent;
    }
</style>
<script>
    jQuery('form').submit(function() {
        jQuery('#submit').prop('disabled', true);
    });

    jQuery('document').ready(function() {
        var certStatus = '{/literal}{$certs}{literal}';
        if (certStatus == 'ACTIVE') {
            jQuery('#showcerts').prop('disabled', false);
            jQuery('#showprivate').prop('disabled', false);
        }
    });
</script>
{/literal}