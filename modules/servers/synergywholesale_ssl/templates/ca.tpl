<div align="left">
    <table width="100%" cellspacing="1" cellpadding="0" class="">
        <tr>
            <td>
                <table width="100%" border="0" cellpadding="2" cellspacing="2" class="table table-striped" id="ca_table">
                    <tr>
                        <td class="fieldarea">SSL Provisioning Date:</td>
                        <td>{$provisiondate}</td>
                    </tr>
                    <tr>
                        <td class="fieldarea">SSL Expiry Date:</td>
                        <td>{$expirydate}</td>
                    </tr>
                    <tr>
                        <td class="fieldarea">{$lang_sslstatus}:</td>
                        <td>{$status}</td>
                    </tr>
                    {if $status eq 'Completed'}
                        <tr>
                            <td class="fieldarea">Validation Status:</td>
                            <td>
                                <span id="validation-status">{$certs}</span>
                                {if $certs neq 'Active'}
                                &nbsp;
                                <i id="refresh-status" class="glyphicon glyphicon-refresh" data-toggle="tooltip" data-placement="right" title="" data-original-title="Click to refresh validation status"></i>
                                {/if}
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Show Certificates & Private Key:</td>
                            <td>
                                <div class="loader" id="cert-loader"></div>
                                {if $certs eq 'Active'}
                                <input type="button" class="btn btn-primary" name="showcerts" id="showcerts" value="Show" />
                                {else}
                                <input type="button" class="btn btn-primary" name="showcerts" id="showcerts" value="Show" disabled="disabled" />
                                {/if}
                            </td>
                        </tr>
                    {/if}
                </table>
            </td>
        </tr>
    </table>
</div>
{literal}
<style>
    .fieldarea {
        font-weight: bold;
        width: 40%;
    }
    
    .loading {
        animation: spin 2s infinite linear;
        -moz-animation: spin 2s infinite linear;
        -webkit-animation: spin2 2s infinite linear;
    }

    .loader {
        display:none;
        border: 15px solid #f3f3f3; /* Light grey */
        border-top: 16px solid #3498db; /* Blue */
        border-radius: 50%;
        width: 15px;
        height: 15px;
        animation: spin 2s linear infinite;
    }

    @-webkit-keyframes spin2 {
        from {
            -webkit-transform: rotate(0deg);
        }
        to {
            -webkit-transform: rotate(360deg);
        }
    }

    @keyframes spin {
        from {
            transform: scale(1) rotate(0deg);
        }
        to {
            transform: scale(1) rotate(360deg);
        }
    }

    .blockhover {
        background-color: transparent !important;
        cursor: not-allowed;
    }

    .block {
        color: #777 !important;
        border: transparent;
    }
</style>
<script type="text/javascript">
    jQuery('document').ready(function() {
        var certStatus = '{/literal}{$certs}{literal}';
        if (certStatus == 'ACTIVE') {
            jQuery('#showcerts').prop('disabled', false);
            jQuery('#showprivate').prop('disabled', false);
        }

        jQuery('#showcerts').click(function() {
            jQuery('#showcerts').hide();
            jQuery('#cert-loader').show();
            var sender = this;
            jQuery.get('clientarea.php?action=productdetails&id={/literal}{$serviceid}{literal}&method=certs', function (data) {
                jQuery('#cert-loader').hide();
                jQuery(sender).parent().parent().remove();
                jQuery('#ca_table').find('tbody').append(data);
            });
        });

        jQuery('#refresh-status').click(function() {
            if (jQuery('#refresh-status').hasClass('loading')) {
                return;
            }

            jQuery('#refresh-status').toggleClass('loading');
            jQuery.get('clientarea.php?action=productdetails&id={/literal}{$serviceid}{literal}&method=validation-status', function (data) {
                jQuery('#refresh-status').toggleClass('loading');
                try {
                    data = JSON.parse(data);
                    jQuery('#validation-status').text(data.status);
                    if (data.status == 'Active') {
                        $('#refresh-status').fadeOut();
                    }
                } catch (e) {
                    jQuery('#validation-status').text('An error occured.');
                }
            });
        });
    });
</script>
{/literal}