# IPClues PHP SDK

Official PHP SDK for the [IPClues](https://ipclues.com) IP intelligence API.

## Requirements

- PHP 8.2+
- An IPClues API key

## Install

```bash
composer require ipclues/sdk
```

## Quick start

```php
use IPCLues\Sdk\{Client,Database};
use IPCLues\Sdk\Exception\IPCluesException;

$client = new Client("YOUR_API_KEY");

try {
    $response = $client->lookup("1.1.1.1", [
        Database::GeoI3Lite,
        Database::NetI3Identity
    ]);

    var_dump($response->data); // original decoded response body
    var_dump($response->metadata); // Metadata drived from HTTP headers
    var_dump($response->metadata->status); // HTTP status code
    var_dump($response->metadata->statusText); // HTTP status reason
    var_dump($response->metadata->creditsRemaining); // remaining credits
} catch (IPCluesException $ie) {
    echo "IPClues error: {$ie->getMessage()}";
}
```

## Response behavior

The SDK wraps the API response body and IPClues HTTP metadata:

- `$response->data`: original decoded response body.
- `$response->metadata->status`: HTTP status code.
- `$response->metadata->requestId`: `I3-Request-Id` header.
- `$response->metadata->creditsCharged` and `creditsRemaining`: credit usage headers.
- `$response->metadata->databases` and `databaseVersions`: response database headers.
- `$response->metadata->rateLimit`: parsed rate-limit headers.

HTTP error responses such as 401, 402, and 429 are returned as `APIResponse` objects too. Network failures and timeouts raise exceptions.

## License

MIT
