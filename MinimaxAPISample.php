<?php
$params = array(
    'client_id' => 'xxx',
    'client_secret' => 'xxx',
    'grant_type' => 'password',
    'username' => 'xxx',
    'password' => 'xxx',
    'scope' => 'minimax.si'
);

$Lokalizacija = "SI"; // RS, HR
$ApiTokenEndpoint = "https://moj.minimax.".$Lokalizacija."/".$Lokalizacija."/AUT/oauth20/token";
echo $ApiTokenEndpoint . PHP_EOL;

$APIBaseUrl = "https://moj.minimax.".$Lokalizacija."/".$Lokalizacija."/API/";
echo $APIBaseUrl . PHP_EOL;

$token = getToken($ApiTokenEndpoint, $params);

$access_token = $token->access_token;

function getToken($url, $params)
{
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
	curl_setopt($curl, CURLOPT_URL, $url);
	
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'Content-type: application/x-www-form-urlencoded'
	));
		
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	curl_setopt($curl, CURLOPT_HEADER, 1);
	curl_setopt($curl, CURLOPT_NOBODY, 0);
	//curl_setopt($curl, CURLOPT_VERBOSE, 1);
	curl_setopt($curl, CURLOPT_HEADER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	// EXECUTE:
	$result = curl_exec($curl);
	if (!$result)
	{
		die("Connection Failure");
	}

	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
	$header = substr($result, 0, $header_size);
	$body = substr($result, $header_size);
	//print $header .PHP_EOL;
	print $http_code .PHP_EOL;
	print $body .PHP_EOL;;
	$json = json_decode($body);
	print_r($json);
	
	curl_close($curl);
	return $json;
}

function callAPI($method, $url, $data, $access_token)
{
    $curl = curl_init();
    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        default:
            if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
	}
	// OPTIONS:
	curl_setopt($curl, CURLOPT_URL, $url);

	$authorization = "Authorization: Bearer " . $access_token; // Prepare the authorisation token
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($data) ,
		$authorization
	)); // Inject the token into the header
	

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	curl_setopt($curl, CURLOPT_HEADER, 1);
	curl_setopt($curl, CURLOPT_NOBODY, 0);
	//curl_setopt($curl, CURLOPT_VERBOSE, 1);
	curl_setopt($curl, CURLOPT_HEADER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	// EXECUTE:
	$result = curl_exec($curl);
	if (!$result)
	{
		die("Connection Failure");
	}

	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
	$header = substr($result, 0, $header_size);
	$body = substr($result, $header_size);

	//$headers = get_headers_from_curl_response($result);	
	//print_r($headers);
	
	
	print $http_code .PHP_EOL;
	print $body .PHP_EOL;
	
	curl_close($curl);
	
	if($method == "POST" AND $http_code = 201)
	{
		if(preg_match("!\r\n(?:location|URI): *(.*?) *\r\n!", $header, $matches))
		{
			$locationurl = $matches[1];
			print $locationurl .PHP_EOL;
			
			$get_data = callAPI('GET', $locationurl, false, $access_token);
			
			return $get_data;
		}
		return "";
	}
	else
	{
		$json = json_decode($body);
		print_r($json);
		return $json;
	}
}

function get_headers_from_curl_response($response)
{
    $headers = array();

    $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

    foreach (explode("\r\n", $header_text) as $i => $line)
        if ($i === 0)
            $headers['http_code'] = $line;
        else
        {
            list ($key, $value) = explode(': ', $line);

            $headers[$key] = $value;
        }

    return $headers;
}

$get_data = callAPI('GET', $APIBaseUrl . 'api/currentuser/orgs', false, $access_token);

$org_id = $get_data->Rows[0]->Organisation->ID; //take first org on the list

echo $org_id . PHP_EOL;


//$org_id = "195417";

$currencyCode = "EUR";
$vatRateCode = "S";
$date = "2022-03-03";
$itemCode = bin2hex(openssl_random_pseudo_bytes(16));
$countryCode = "HU";
$customerCode = bin2hex(openssl_random_pseudo_bytes(16));
$IRreportTemplateCode = "IR";
$DOreportTemplateCode = "DO";

$get_data = callAPI('GET', $APIBaseUrl . 'api/orgs/' .$org_id .'/vatrates/code(' .$vatRateCode .')?date=' .$date, false, $access_token);
$vatRateId = $get_data -> VatRateId;
$vatPercent = $get_data -> Percent;
echo $vatRateId . PHP_EOL;
echo $vatPercent . PHP_EOL;

$get_data = callAPI('GET', $APIBaseUrl . 'api/orgs/' .$org_id .'/currencies/code(' .$currencyCode .')', false, $access_token);
$currencyId = $get_data -> CurrencyId;
echo $currencyId . PHP_EOL;

$get_data = callAPI('GET', $APIBaseUrl . 'api/orgs/' .$org_id .'/countries/code(' .$countryCode .')', false, $access_token);
$countryId = $get_data -> CountryId;
echo $countryId . PHP_EOL;

$get_data = callAPI('GET', $APIBaseUrl . 'api/orgs/' .$org_id .'/report-templates?SearchString=' .$IRreportTemplateCode .'&PageSize=100' .$date, false, $access_token);
$IRreportTemplateId = $get_data -> Rows[0] -> ReportTemplateId;
echo $IRreportTemplateId . PHP_EOL;

$get_data = callAPI('GET', $APIBaseUrl . 'api/orgs/' .$org_id .'/report-templates?SearchString=' .$DOreportTemplateCode .'&PageSize=100' .$date, false, $access_token);
$DOreportTemplateId = $get_data -> Rows[0] -> ReportTemplateId;
echo $DOreportTemplateId . PHP_EOL;

$jsonStr = '
{
Name: "Test",
Code: "' .$itemCode .'",
ItemType: "B",
VatRate: {ID: ' .$vatRateId .'},
Price: 100.0,
Currency: {ID: ' .$currencyId .'}
}';

echo $jsonStr;

$get_data = callAPI('POST', $APIBaseUrl . 'api/orgs/' .$org_id .'/items', $jsonStr, $access_token);
$itemId = $get_data->ItemId;
echo $itemId . PHP_EOL;

$jsonStr = '
{
	Name: "Test",
	Address: "test",
	PostalCode: "1234",
	City: "Nowhere",
	Code: "' .$customerCode .'",
	Country: {ID: ' .$countryId .'},
	CountryName: "-",
	SubjectToVAT: "N",
	Currency: {ID: ' .$currencyId .'},
	EInvoiceIssuing: "SeNePripravlja"
}';

echo $jsonStr;

$get_data = callAPI('POST', $APIBaseUrl . 'api/orgs/' .$org_id .'/customers', $jsonStr, $access_token);
$customerId = $get_data->CustomerId;
echo $customerId . PHP_EOL;

$employeeBlock = "";

if ($Lokalizacija == "HR")
{
	$employeeString = "Test";
	$get_data = callAPI('GET', $APIBaseUrl . 'api/orgs/' .$org_id .'/employees?SearchString=' .$employeeString, false, $access_token);

	$employeeId = $get_data -> Rows[0] -> EmployeeId;

	$employeeBlock = 'Employee:{ID:'.$employeeId .'},';
}

$jsonStr = '
{
	Customer:{ID:' .$customerId .'},'
	.$employeeBlock .'
	DateIssued:"' .$date .'",
	DateTransaction:"' .$date .'",
	DateTransactionFrom:"' .$date .'",
	DateDue:"' .$date .'",
	AddresseeName:"Test",
	AddresseeAddress:"Nowhere",
	AddresseePostalCode:"1234",
	AddresseeCity:"Test",
	AddresseeCountryName :"-",
	AddresseeCountry:{ID:'.$countryId .'},
	Currency:{ID:'.$currencyId .'},
	IssuedInvoiceReportTemplate:{ID:' .$IRreportTemplateId .'},
	DeliveryNoteReportTemplate:{ID:' .$DOreportTemplateId .'},
	Status:"O",
	PricesOnInvoice:"N",
	RecurringInvoice:"N",
	InvoiceType:"R",
	IssuedInvoiceRows:[
		{
			Item:{ID:' .$itemId .'},
			ItemName:"Test",
			RowNumber:1,
			ItemCode:"code",
			Description:"description",
			Quantity:1,
			UnitOfMeasurement:"kom",
			Price:10.6475,
			PriceWithVAT:12.99,
			VATPercent:' .$vatPercent .',
			Discount:0,
			DiscountPercent:0,
			Value:10.6475,
			VatRate:{ID:' .$vatRateId .'}
		}
	]
}';

echo $jsonStr . PHP_EOL;

$get_data = callAPI('POST', $APIBaseUrl . 'api/orgs/' .$org_id .'/issuedinvoices', $jsonStr, $access_token);
$issuedInvoiceId = $get_data->IssuedInvoiceId;
$status = $get_data->Status;
$actionName = "IssueAndGeneratePdf";
$rowVersion = $get_data->RowVersion;
echo $issuedInvoiceId . PHP_EOL;

$put_data = callAPI('PUT', $APIBaseUrl . 'api/orgs/' .$org_id .'/issuedinvoices/' .$issuedInvoiceId .'/actions/'.$actionName .'?rowVersion='.$rowVersion, false, $access_token);
$pdf = base64_decode($put_data->Data->AttachmentData);
file_put_contents($put_data->Data->AttachmentFileName, $pdf);

?>
