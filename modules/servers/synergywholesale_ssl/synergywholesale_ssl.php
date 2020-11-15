<?php

/**
 * Synergy Wholesale SSL Module
 *
 * @copyright Copyright (c) Synergy Wholesale Pty Ltd 2020
 * @license https://github.com/synergywholesale/whmcs-ssl-module/LICENSE
 */

use WHMCS\Database\Capsule as DB;

define('SW_SSL_PRODUCTS_ENDPOINT', 'https://manage.synergywholesale.com/ssl-products');
define('SW_SSL_API_PROD_ENDPOINT', 'https://api.synergywholesale.com/?wsdl');
define('SW_SSL_API_TEST_ENDPOINT', 'https://api-ote.synergywholesale.com/?wsdl');

// phpcs:disable
set_exception_handler(function ($exception) {
    echo '<b>Error:</b> ', $exception->getMessage();
});
/**
 * Module Metadata - Displays the module's nice name.
 *
 * @return     string  HTML Anchor Tag with a hyperlink
 */
function synergywholesale_ssl_MetaData()
{
    return [
        'DisplayName' => 'Synergy Wholesale SSL',
        'RequiresServer' => false
    ];
}

/**
 * Helper function generates Certificate Configuration links
 *
 * @param      int  $order_id  The order identifier
 *
 * @return     string  HTML Anchor Tag with a hyperlink
 */
function synergywholesale_ssl_getLink($order_id)
{
    global $CONFIG;
    return str_replace(
        '{{url}}',
        $CONFIG['SystemURL'] . '/configuressl.php?cert=' . md5($order_id),
        '<a href="{{url}}">{{url}}</a>'
    );
}

/**
 * Helper function returns snippets from the module snippets folder
 *
 * @param      string  $fileName  Name of the snippet
 *
 * @return     string|boolean  Returns the contents of the snippet file, false if file doesn't exist.
 */
function synergywholesale_ssl_getSnippet($fileName)
{
    $file = realpath(join(DIRECTORY_SEPARATOR, [
        __DIR__,
        'snippets',
        $fileName
    ]));

    if (!$file) {
        throw new \Exception("Snippet $fileName not found!");
    }

    return file_get_contents($file);
}

function synergywholesale_ssl_ProductsLoader()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SW_SSL_PRODUCTS_ENDPOINT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $certificates = curl_exec($ch);
    curl_close($ch);
  
    if (!$certificates) {
        throw new \Exception(sprintf('Unable to fetch SSL Products from Synergy Wholesale. Please ensure you have whitelisted this web servers IP Address (%s) for API access.', $_SERVER['SERVER_ADDR']));
    }
    
    $certificates = json_decode($certificates);
    if (isset($certificates->error)) {
        throw new \Exception('Failed to decode JSON contents: ' . json_last_error_msg());
    }

    $list = [];
    foreach ($certificates->result as $certificate) {
        preg_match('/(.*) -/i', $certificate->name, $matches);
        $list[$matches[1]] = $matches[1];
    }

    return $list;
}

/**
 * Config Options
 *
 * @param      array  $params  The parameters
 *
 * @return     array   Configuration Properties
 */
function synergywholesale_ssl_ConfigOptions(array $params)
{
    $relid = isset($_POST['id']) ? $_POST['id'] : $_GET['id'];
    $fieldExists =  DB::table('tblcustomfields')
        ->where('relid', $relid)
        ->count();

    if (!$fieldExists) {
        DB::table('tblcustomfields')
            ->insert([
                'type' => 'product',
                'relid' => $relid,
                'fieldname' => 'privKey|Private Key',
                'fieldtype' => 'textarea',
                'adminonly' => 'on'
            ]);
    }

    $result = DB::table('tblemailtemplates')
        ->where('name', 'SSL Certificate Configuration Required')
        ->first();

    if (!count($result)) {
        $emailTemplate = file_get_contents(join(DIRECTORY_SEPARATOR, [
            __DIR__,
            'templates',
            'configuration-required-email.txt'
        ]));

        DB::table('tblemailtemplates')
            ->insert([
                'type' => 'product',
                'name' => 'SSL Certificate Configuration Required',
                'subject' => 'SSL Certificate Configuration Required',
                'message' => $emailTemplate,
                'fromname' => '',
                'fromemail' => '',
                'disabled' => '',
                'custom' => '',
                'language' => '',
                'copyto' => '',
                'plaintext' => '0'
            ]);
    }

    return [
        'API Key' => [
            'Type' => 'text',
            'Size' => 60,
            'SimpleMode' => true
        ],
        'Reseller ID' => [
            'Type' => 'text',
            'Size' => 10,
            'SimpleMode' => true
        ],
        'SSL Certificate Type' => [
            'Type' => 'text',
            'Options' => implode(',', array_unique($certificateTypes)),
            'Loader' => 'synergywholesale_ssl_ProductsLoader',
            'SimpleMode' => true
        ],
        'Purchase Period' => [
            'Type' => 'dropdown',
            'Options' => implode(',', range(1, 2)),
            'Description' => 'Years',
            'SimpleMode' => true
        ],
        'Test Mode' => [
            'Type' => 'yesno',
            'SimpleMode' => true
        ]
    ];
}

/**
 * Create function
 *
 * @param      array  $params  The parameters
 *
 * @return     string  Error or Success Message
 */
function synergywholesale_ssl_CreateAccount(array $params)
{
    $result = DB::table('tblsslorders')
        ->where('serviceid', $params['serviceid'])
        ->count();

    if ($result > 0) {
        return 'An SSL Order already exists for this order';
    }

    $order_id = DB::table('tblsslorders')
        ->insertGetId([
            'userid' => $params['clientsdetails']['userid'],
            'serviceid' => $params['serviceid'],
            'remoteid' => '',
            'module' => 'synergywholesale_ssl',
            'certtype' => $params['configoption3'],
            'status' => 'Awaiting Configuration'
        ]);

    sendMessage('SSL Certificate Configuration Required', $params['serviceid'], [
        'ssl_configuration_link' => synergywholesale_ssl_getLink($order_id)
    ]);

    return 'success';
}

/**
 * Buttons for the Admin Area
 *
 * @return     array    Associate array of buttons and their related functions
 */
function synergywholesale_ssl_AdminCustomButtonArray()
{
    return [
        'Cancel' => 'cancel',
        'Resend Configuration Email' => 'resend',
        'Resend Approval Email' => 'resendapprover',
        'Resend Completed Certificate Email' => 'resendcompleted',
    ];
}

/**
 * Buttons for the Client Area
 *
 * @return     array  Associate array of buttons and their related functions
 */
function synergywholesale_ssl_ClientAreaCustomButtonArray(array $params)
{
    $buttons = [
        'Request New Certificate' => 'reissue',
        'Decode CSR' => 'DecodeCSR'
    ];

    list($certID, $status) = synergywholesale_ssl_getService($params['serviceid']);
    
    if (!$certID) {
        return $buttons;
    }

    $response = synergywholesale_ssl_api('SSL_getCertSimpleStatus', [
        'certID' => $certID
    ], $params);

    if ($response->status != 'OK') {
        return $buttons;
    }
 
    switch ($response->certStatus) {
        case 'ACTIVE':
            return array_merge($buttons, [
                'Request New Certificate' => 'reissue',
                'Resend Completed Certificate Email' => 'resendcompleted'
            ]);
        case 'PENDING':
            $buttons['Resend Approval Email'] = 'resendapprover';
            break;
    }

    return $buttons;
}

/**
 * Decode CSR Page
 *
 * @param      array  $params  The parameters
 *
 * @return     array   Template and variables
 */
function synergywholesale_ssl_DecodeCSR(array $params)
{
    list($certID, $status) = synergywholesale_ssl_getService($params['serviceid']);

    $response = synergywholesale_ssl_api('SSL_getCertSimpleStatus', [
        'certID' => $certID
    ], $params);

    $certs = $response->certStatus;

    if (isset($_POST['csr_string'])) {
        $csr = synergywholesale_ssl_api('SSL_decodeCSR', [
            'csr' => $_POST['csr_string']
        ], $params);

        if ($csr->status != 'OK') {
            return $csr->errorMessage;
        }
    }

    return [
        'templatefile' => 'templates/csr_form',
        'vars' => [
            'certs' => $certs,
            'csr' => (isset($csr) ? $csr : ''),
        ],
    ];
}

/**
 * Cancels SSL Certificate
 *
 * @param      array  $params  The parameters
 *
 * @return     string  Success or error message
 */
function synergywholesale_ssl_cancel(array $params)
{
    list($certID, $status) = synergywholesale_ssl_getService($params['serviceid']);

    if (!$certID) {
        return 'No SSL Order exists for this service';
    }

    $request = [
        'certID' => $certID
    ];

    $checkStatus = synergywholesale_ssl_api('SSL_getCertSimpleStatus', $request, $params);

    if ($checkStatus->certStatus != 'PENDING') {
        return 'Only SSL Orders in Pending status can be cancelled';
    }
    
    $response = synergywholesale_ssl_api('SSL_cancelSSLCertificate', $request, $params);
    if ($response->status != 'OK') {
        return ($response->errorMessage ?
            $response->errorMessage :
            'An Unknown Error Occurred'
        );
    }
    
    DB::table('tblsslorders')
        ->where('serviceid', $params['serviceid'])
        ->update([
            'status' => 'Cancelled'
        ]);
    
    DB::table('tblhosting')
        ->where('id', $params['serviceid'])
        ->update([
            'domainstatus' => 'Cancelled'
        ]);

    return 'success';
}

/**
 * Resends the Configuration Required Email
 *
 * @param      array   $params  The parameters
 *
 * @return     string  Success or failure message
 */
function synergywholesale_ssl_resend(array $params)
{
    $data = DB::table('tblsslorders')
        ->where('serviceid', $params['serviceid'])
        ->select('id')
        ->first();

    $email = sendMessage('SSL Certificate Configuration Required', $params['serviceid'], [
        'ssl_configuration_link' => synergywholesale_ssl_getLink($data->id)
    ]);

    return ($email ? 'success' : 'Cannot send email');
}

/**
 * Resends the Certificate Approver Email
 *
 * @param      array  $params  The parameters
 *
 * @return     string  Success or failure message
 */
function synergywholesale_ssl_resendapprover(array $params)
{
    list($certID, $status) = synergywholesale_ssl_getService($params['serviceid']);

    if (!$certID) {
        return 'No SSL Order exists for this service';
    }

    if ($status != 'Completed') {
        return 'Only SSL Orders in Completed status can be resent approval emails';
    }

    $response = synergywholesale_ssl_api('SSL_resendDVEmail', [
        'certID' => $certID
    ], $params);

    if ($response->status == 'OK') {
        return 'success';
    } else {
        return ($response->errorMessage ?
            $response->errorMessage :
            'An Unknown Error Occurred'
        );
    }
}

/**
 * SSL Certificate Reissue
 *
 * @param      array        $params  The parameters
 *
 * @return     array|string  Template Array or Error Message
 */
function synergywholesale_ssl_reissue(array $params)
{
    $certPeriod = $params['configoption4'];

    if (isset($_POST['email'])) {
        if ($_POST['generateNew'] == 'on') {
            $response = synergywholesale_ssl_api('SSL_generateCSR', [
                'numOfYears' => $certPeriod,
                'city' => $_POST['city'],
                'state' => $_POST['state'],
                'country' => $_POST['country'],
                'organisation' => $_POST['orgname'],
                'organisationUnit' => $_POST['orgunit'],
                'commonName' => $_POST['Domain'],
                'emailAddress' => $_POST['email'],
            ], $params);
            
            if ($response->status != 'OK') {
                return ($response->errorMessage ?
                    $response->errorMessage :
                    'An Unknown Error Occurred'
                );
            }

            DB::table('tblcustomfieldsvalues')
                ->where('relid', $params['serviceid'])
                ->update([
                    'value' => $response->privKey
                ]);

            $newCSR = $response->csr;
        } elseif (isset($_POST['csr'])) {
            $newCSR = $_POST['csr'];
        }

        if (!isset($newCSR)) {
            return 'Missing CSR.';
        }

        list($certID, $status) = synergywholesale_ssl_getService($params['serviceid']);
       
         $response = synergywholesale_ssl_api('SSL_reissueCertificate', [
            'certID' => $certID,
            'newCSR' => $newCSR
         ], $params);

        if ($response->status != 'OK') {
            return ($response->errorMessage ?
                $response->errorMessage :
                'An Unknown Error Occurred'
            );
        }

        DB::table('tblsslorders')
            ->where('serviceid', $params['serviceid'])
            ->update([
                'remoteid' => $output->newCertID
            ]);

        return [
            'templatefile' => 'templates/reissue_completed',
            'vars' => [
                'cer' => $output->cer,
                'p7b' => $output->p7b,
                'status' => $output->status,
            ]
        ];
    }

    return [
        'templatefile' => 'templates/reissue',
        'breadcrumb' => [
            'stepurl.php?action=this&var=that' => 'Request New Certificate',
        ],
        'vars' => [
            'serviceid' => $params['serviceid'],
            'city' => $params['clientsdetails']['city'],
            'state' => $params['clientsdetails']['state'],
            'country' => $params['clientsdetails']['countryname'],
            'code' => $params['clientsdetails']['countrycode'],
            'organisation' => $params['clientsdetails']['companyname'],
            'commonName' => $params['domain'],
            'email' => $params['clientsdetails']['email'],
            'countrydropdown' => getCountriesDropDown($params['clientsdetails']['countrycode'], 'country', '', false)
        ]
    ];
}

/**
 * Communicate with the Synergy Wholesale API
 *
 * @param      string  $action   API Command
 * @param      array   $request  The request parameters
 * @param      array   $params   The parameters
 *
 * @throws     \SoapFault
 *
 * @return     object  API Response
 */
function synergywholesale_ssl_api($action, array $request, array $params)
{
    $resellerID = $params['configoption2'];
    $apiKey = $params['configoption1'];
    $testMode = $params['configoption5'];

    $request = array_merge([
        'resellerID' => $resellerID,
        'apiKey' => $apiKey
    ], $request);

    $url = ($testMode ? SW_SSL_API_TEST_ENDPOINT : SW_SSL_API_PROD_ENDPOINT);

    $client = new \SoapClient(null, [
        'location' => $url,
        'uri' => ''
    ]);

    // We don't catch the exception because we want it to bubble up
    $output = $client->$action($request);
    logModuleCall('Synergy Wholesale SSL', $action, $request, (array)$output);
    return $output;
}

/**
 * Get Service Helper Function
 *
 * @param      integer  $service_id  The service identifier
 *
 * @return     array   Contains Synergy Wholesale Certificate ID and local status
 */
function synergywholesale_ssl_getService($service_id)
{
    $data = DB::table('tblsslorders')
        ->where('serviceid', $service_id)
        ->select('remoteid', 'status')
        ->first();

    return [
        $data->remoteid,
        $data->status
    ];
}

/**
 * Resends certificate completion email
 *
 * @param      array  $params  The parameters
 *
 * @return     string  Success or error message
 */
function synergywholesale_ssl_resendcompleted(array $params)
{
    list($certID, $status) = synergywholesale_ssl_getService($params['serviceid']);

    if (!$certID) {
        return 'No SSL Order exists for this service';
    }

    if ($status != 'Completed') {
        return 'Only SSL Orders in Completed status can be resent completed certificate emails';
    }

    $response = synergywholesale_ssl_api('SSL_resendIssuedCertificateEmail', [
        'certID' => $certID
    ], $params);

    if ($response->status == 'OK') {
        return 'success';
    } else {
        return ($response->errorMessage ?
            $response->errorMessage :
            'An Unknown Error Occurred'
        );
    }
}

/**
 * Default WHMCS function which provide output in client area product details
 *
 * @global  $_LANG      global variable with actual lang
 *
 * @param   $params     WHMCS variables
 *
 * @return  array       html code to show
 */
function synergywholesale_ssl_ClientArea(array $params)
{
    global $_LANG;

    if (empty(get_registered_hooks('ClientAreaPrimarySidebar'))) {
        include __DIR__ . DIRECTORY_SEPARATOR . 'hooks.php';
    }

    if ($params['status'] == 'Completed') {
        DB::table('tblhosting')
            ->where('id', $params['serviceid'])
            ->update([
                'domainstatus' => 'Active'
            ]);
        header('Location: clientarea.php?action=productdetails&id=' . $params['serviceid']);
        exit;
    }
         
    list($certID, $status) = synergywholesale_ssl_getService($params['serviceid']);

    $request = [
        'certID' => $certID
    ];

    switch ($_GET['method']) {
        case 'certs':
            if (empty($certID)) {
                break;
            }
            $response = synergywholesale_ssl_api('SSL_getSSLCertificate', $request, $params);
                
            if (isset($response->errorMessage)) {
                echo '<tr><td class="fieldarea">Error:</td><td>', $response->errorMessage, '</td></tr>';
                exit;
            }

            $fieldid = DB::table('tblcustomfields')
                ->where('fieldname', 'LIKE', 'privKey%')
                ->where('relid', '=', $params['packageid'])
                ->value('id');

            $privateKey = DB::table('tblcustomfieldsvalues')
                ->where('relid', $params['serviceid'])
                ->where('fieldid', $fieldid)
                ->value('value');

            $certInfoTemplate = synergywholesale_ssl_getSnippet('certificate-display.html');
            $privateKeyTemplate = synergywholesale_ssl_getSnippet('privatekey-display.html');

            echo str_replace(
                [
                '{{certificate}}',
                '{{p7b}}',
                '{{privateKey}}',
                '{{cabundle}}'
                ],
                [
                    $response->cer,
                    $response->p7b,
                    $privateKey,
                    $response->caBundle
                ],
                $certInfoTemplate . $privateKeyTemplate
            );
            die;
        case 'private':
            $privateKey = DB::table('tblcustomfieldsvalues')
                ->where('relid', $params['serviceid'])
                ->select('value')
                ->first();

            $privateKeyTemplate = synergywholesale_ssl_getSnippet('privatekey-display.html');

            echo str_replace(
                [
                '{{privateKey}}'
                ],
                [
                    $privateKey
                ],
                $privateKeyTemplate
            );
            die;
        case 'validation-status':
            // Get the certificate status.
            $response = synergywholesale_ssl_api('SSL_getCertSimpleStatus', $request, $params);
            echo json_encode([
                'status' => ucfirst(strtolower($response->certStatus))
            ]);
            die;
    }

    // mysql query to obtain data fot this ssl order
    $cert = DB::table('tblsslorders')
        ->where("serviceid", $params['serviceid'])
        ->first();

    if (!$cert || !$cert->id) {
        // if product isn't created, this error will occur
        // if so, admin need to make "Create" action in product details (admin area)
        $error = '
            <div class="alert alert-danger">
                <p class="danger">Product must be created manually. Please contact administrator.</p>
            </div>
        ';

        return [
            'templatefile' => 'templates/error',
            'vars' => [
                'error' => $error
            ]
        ];
    }

    // Completion Date is the day the certificate was issued
    $completionDate = strtotime($cert->completiondate);
    $provisionDate = ($completionDate < 0 ?
        ' Not Yet Configured' :
        date('d/m/Y', $completionDate)
    );

    // Backwards compatibility stuff.
    if (isset($params['configoptions']['Years'])) {
        // grab value from configureble option set to module
        $certPeriod = $params['configoptions']['Years'];
    } else {
        $certPeriod = $params['configoption4'];
    }
    
   // The expiry date isn't stored in database
   // So we calculate it based off of the Certificate's Period.
    $expiryDate = ($completionDate < 0 ?
        ' Not Yet Created' :
        date('d/m/Y', strtotime("+$certPeriod year", $completionDate))
    );
        
    $status = $cert->status;
    if ($cert->status == 'Awaiting Configuration') {
        $status .= ' - <a href="configuressl.php?cert=' . md5($cert->id) . '"><strong>Configure Now</strong></a>';
    }

    $response = synergywholesale_ssl_api('SSL_getCertSimpleStatus', $request, $params);
    
    return [
        'templatefile' => 'templates/ca',
        'vars' => [
            'certs' => ucfirst(strtolower($response->certStatus)), // Certificate status
            'provisiondate' => $provisionDate, // Formatted version of completion date
            'lang_sslstatus' => $_LANG['sslstatus'],
            'expirydate' => $expiryDate, // Certificate expiry date
            'status' => ($status ? $status : $cert->errorMessage)
        ]
    ];
}

/**
 * Configuration - Step One
 *
 * @param      <type>  $params  The parameters
 *
 * @return     <type>  ( description_of_the_return_value )
 */
function synergywholesale_ssl_SSLStepOne(array $params)
{
    if ($params['customfields']['privKey']) {
        $privKey = preg_replace("/\r?\n/", "\\n", $params['customfields']['privKey']);
    } else {
        $privKey = '-----BEGIN PRIVATE KEY-----\n\n-----END PRIVATE KEY-----';
    }

    return [
        'additionalfields' => [
            'Private Key' => [
                'private_key' => [
                    'Type' => 'textarea',
                    'Description' => '
                    <script>
                        jQuery(\'[name="fields[private_key]"]\').addClass(\'form-control\').attr(\'rows\', \'7\').attr(\'id\', \'inputPrivKey\').val(\'' . $privKey . '\');
                        jQuery(\'label:contains("")\').remove();
                        jQuery(\'[name="fields[private_key]"]\').parent().removeClass(\'col-md-8\').addClass(\'col-md-12\');
                    </script>',
                    'Required' => false,
                ]
            ],
            '' => [
                'business_category' => [
                    'FriendlyName' => 'Business Category',
                    'Type' => 'dropdown',
                    'Description' => '<p class="help-block">(Required for Comodo SSL EV)</p>
                    <script>
                        var categories = ["Private Organisation", "Government Entity", "Business Entity", "Non-commercial Entity"];
                        jQuery(\'[name="fields[business_category]"]\').addClass(\'form-control\');
                        jQuery.each(categories, function (i, category) {
                            jQuery(\'[name="fields[business_category]"]\').append(jQuery(\'<option>\', { 
                                value: category,
                                text : category 
                            }));
                        });
                    </script>',
                    'Required' => false,
                ]
            ],
            'Generate CSR' => [
                'csr_generate_check' => [
                    'FriendlyName' => 'Generate CSR',
                    'Type' => 'yesno',
                    'Description' => 'If checked, new CSR will be generated based upon information below.
                    <script>
                        jQuery(\'[name="fields[csr_generate_check]"]\').on(\'change\', function() { 
                            jQuery(\'#inputCsr\').prop(\'readonly\', jQuery(this).prop(\'checked\'));
                            jQuery(\'#inputPrivKey\').prop(\'readonly\', jQuery(this).prop(\'checked\'));
                        });
                        jQuery(document).ready(function() {
                            jQuery(\'#inputCsr\').prop(\'readonly\', jQuery(\'[name="fields[csr_generate_check]"]\').prop(\'checked\'));
                        });
                    </script>',
                    'Required' => false
                ],
                'common_name' => [
                    'FriendlyName' => 'Common Name (Domain)',
                    'Type' => 'text',
                    'Description' => '
                    <script>
                        jQuery(\'[name="fields[common_name]"]\').addClass(\'form-control\').val(\'' . $params['domain'] . '\'); 
                        jQuery(\'#inputServerType\').val(1031); setTimeout(function() {
                            jQuery(jQuery(\'fieldset\')[1]).find(\'input\').each(function(){ 
                                if (jQuery(this).attr(\'name\') != \'address2\')
                                    jQuery(this).prop(\'required\', true); 
                            }); 
                            jQuery(\'form\').submit(function() {
                                jQuery(\'input[type="submit"]\').prop(\'disabled\', true); });
                            }, 100);
                    </script>',
                    'Required' => false
                ]
            ]
        ]
    ];
}

/**
 * Configuration - Step Two
 *
 * @param      array  $params  The parameters
 *
 * @return     array  Success || Error Response
 */
function synergywholesale_ssl_SSLStepTwo(array $params)
{
    $csr = $params['csr'];
    $productName = $params['configoption3'];
    $certPeriod = $params['configoption4'];

    if (isset($params['configoptions']['Years'])) {
        $certPeriod = $params['configoptions']['Years'];
    }
    
    // Get the SSL Pricing list to match this up with the SW Product ID
    $productList = synergywholesale_ssl_api('getSSLPricing', [], $params);
    if ($productList->status != 'OK') {
        // We aren't interested in accidentally leaking Wholesale pricing.
        return [
            'error' => 'An Unknown Error Occurred'
        ];
    }

    foreach ($productList->pricing as $product) {
        if (preg_match("/^$productName - $certPeriod year$/i", $product->productName)) {
            $productID = $product->productID;
        }
    }

    if (!isset($productID)) {
        return [
            'error' => 'Failed to map product name and period to an SSL Product'
        ];
    }

    // Generate CSR
    if ($params['fields']['csr_generate_check'] == 'on') {
        $request = [
            'numOfYears' => $certPeriod,
            'country' => $params['country'],
            'state' => $params['state'],
            'city' => $params['city'],
            'organisation' => $params['orgname'],
            'organisationUnit' => $params['jobtitle'],
            'commonName' => $params['fields']['common_name'],
            'emailAddress' => $params['email']
        ];

        if (empty($params['fields']['common_name'])) {
            return [
                'error' => 'You need to enter domain name.'
            ];
        }

        DB::table('tblhosting')
            ->where('id', $params['serviceid'])
            ->update([
                'domain' => $params['fields']['common_name']
            ]);

        $generateCSR = synergywholesale_ssl_api('SSL_generateCSR', $request, $params);
        if ($generateCSR->status != 'OK') {
            return ['error' => ($generateCSR->errorMessage ?
                'Generate CSR: ' . $generateCSR->errorMessage :
                'An Unknown Error Occurred'
            )];
        }
        
        $fieldid = DB::table('tblcustomfields')
            ->where('fieldname', 'LIKE', 'privKey%')
            ->where('relid', '=', $params['packageid'])
            ->value('id');

        $fieldval = DB::table('tblcustomfieldsvalues')
            ->where('fieldid', '=', $fieldid)
            ->where('relid', '=', $params['serviceid'])
            ->value('value');

        if (!$fieldval) {
            DB::table('tblcustomfieldsvalues')
                ->insert([
                    'fieldid' => $fieldid,
                    'relid' => $params['serviceid'],
                    'value' => $generateCSR->privKey
                ]);
        } else {
            DB::table('tblcustomfieldsvalues')
                ->where('relid', '=', $params['serviceid'])
                ->where('fieldid', '=', $fieldid)
                ->update([
                    'value' => $generateCSR->privKey
                ]);
        }
        
        $csr = $generateCSR->csr;
    }

    $request = [
        'csr' => $csr,
        'privateKey' => $params['fields']['csr_generate_check'] == 'on' ? $generateCSR->privKey : $params['fields']['private_key'],
        'productID' => $productID,
        'businessCategory' => $params['fields']['business_category'],
        'firstName' => $params['firstname'],
        'lastName' => $params['lastname'],
        'emailAddress' => $params['email'],
        'address' => $params['address1'],
        'city' => $params['city'],
        'state' => $params['state'],
        'postCode' => $params['postcode'],
        'country' => $params['country'],
        'phone' => $params['phonenumber'],
        'fax' => $params['phonenumber']
    ];

    $response = synergywholesale_ssl_api('SSL_purchaseSSLCertificate', $request, $params);
    
    if ($response->status != 'OK') {
        return ['error' => ($response->errorMessage ?
            'Purchase: ' . $response->errorMessage :
            'An Unknown Error Occurred'
        )];
    }

    return [
        'remoteid' => $response->certID,
        'domain' => $params['fields']['common_name']
    ];
}

/**
 * Renews the SSL Certificate.
 *
 * @param      array  $params  The parameters
 *
 * @return     string  Success
 */
function synergywholesale_ssl_Renew(array $params)
{
    list($certID, $status) = synergywholesale_ssl_getService($params['serviceid']);
    
    $response = synergywholesale_ssl_api('SSL_renewSSLCertificate', [
        'certID' => $certID,
        'firstName' => $params['clientsdetails']['firstname'],
        'lastName' => $params['clientsdetails']['lastname'],
        'emailAddress' => $params['clientsdetails']['email'],
        'address' => $params['clientsdetails']['address1'],
        'city' => $params['clientsdetails']['city'],
        'state' => $params['clientsdetails']['state'],
        'postCode' => $params['clientsdetails']['postcode'],
        'country' => $params['clientsdetails']['country'],
        'phone' => $params['clientsdetails']['phonenumber'],
        'fax' => $params['clientsdetails']['phonenumber'],
    ], $params);

    if ($response->status != 'OK') {
        return ($response->errorMessage ?
            $response->errorMessage :
            'An Unknown Error Occurred'
        );
    }

    DB::table('tblsslorders')
        ->where('serviceid', $params['serviceid'])
        ->update([
            'remoteid' => $output->certID
        ]);

    return 'success';
}
