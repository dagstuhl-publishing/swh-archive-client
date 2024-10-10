# dagstuhl/swh-archive-client

A SoftwareHeritage web API client in php

This project provides a php wrapper around the [SoftwareHeritage web API](https://archive.softwareheritage.org/api/).
It is used by Dagstuhl Publishing to integrate automatic archiving of software projects by authors into its publication workflow.

At https://github.com/dagstuhl-publishing/swh-deposit-client, we also provide a php client for the [SoftwareHeritage Deposit API](https://github.com/dagstuhl-publishing/swh-deposit-client).  


## Installation
```shell
composer require dagstuhl/swh-archive-client
```
The client is designed to work smoothly with the config mechanism of laravel. When used inside a laravel project,
you can configure it by creating a file `swh.php` inside the `config` folder with the following contents:
```php
<?php

return [
    'web-api' => [
        'token' => env('SWH_WEB_API_TOKEN'),
        'url' => env('SWH_WEB_API_URL'),
        // optional: caching of get requests for objects in the archive 
        'cache-folder' => env('SWH_WEB_API_CACHE_FOLDER'), // return null to disable cache
        'cache-ttl' => env('SWH_WEB_API_CACHE_TTL'),
    ]
];
```
Based on that configuration, a default client is implicitly initialized and used whenever you request a SwhObject.
In a non-laravel environment just implement a global config function `config` in such a way that
`config('swh.web-api.token')` is your token, `config('swh.web-api.url')` is the api url, and so on. 


## Code Examples

### 1) Browsing the archive

Searching through the archive is quite intuitive, as you don't realise that you are dealing with an API.
You simply request the relevant objects, like this:

```php
// start with a url
$repo = Repository::fromNodeUrl('https://github.com/dagstuhl-publishing/styles');

// create the corresponding origin object
$origin = Origin::fromRepository($repo);

// ask the origin for the SoftwareHeritage visits
$visits = $origin->getVisits();

// get the snapshot object from a specific visit 
$snapshot = $visits[0]->getSnapshot();

// get the list of branches from a snapshot
$branches = $snapshot->getBranches();
```
Further supported objects are `Revision`, `Release`, `Directory`, `Content`.
To fetch an object by its id, just call the `byId` method:
```php
$revision = Revision::byId('60476b518914683d35ef08dd6cfdc7809e280c75');
```
To identify a directory/file inside a snapshot, use the `Context` class. Continuing the example from above, we can do the following:
```php
// take the last snapshot
$snapshot = $visits[0]->getSnapshot();

// take a "path" to a file/directory inside the repo
$repoNode = new RepositoryNode('https://github.com/dagstuhl-publishing/styles/blob/master/LIPIcs/authors/lipics-v2021.cls');

// identify this node inside the snapshot (i.e., get the context) 
$context = $snapshot->getContext($repoNode);

// display the full identifier
dd($context->getIdentifier());
```

### 2) Archiving a repository

* In a first step, a `SaveRequest` has to be created:

```php
$swhClient = SwhWebApiClient::getCurrent();

// create a repository instance from a url that points to a repo or a specific file/directory inside the repo
$repo = Repository::fromNodeUrl('https://github.com/.../...');

// submit a save request to Software Heritage 
$origin = Origin::fromRepository($repo);
$saveRequest = $origin->postSaveRequest();

if ($saveRequest === null) {
    // connection or network error
    dd('Internal server error', $swhClient->getException(), $swhClient->getLastResponse());
}
else {
    dd('SaveRequest created by SoftwareHeritage, SaveRequestId: '.$saveRequest->id);
    // store $saveRequest->id in local DB to track the status of this request
}
```
* In a second step, the status of the `SaveRequest` has to be watched (within a loop/cron-job). The `$saveRequestId` is the id obtained at the end of the first step.

```php
$saveRequest = SaveRequest::byId($saveRequestId)

if ($saveRequest->saveRequestStatus == SaveRequestStatus::REJECTED) {
    dd('save request rejected -> abort');
}
elseif ($saveRequest->saveTaskStatus == SaveTaskStatus::SUCCEEDED) {
    if ($saveRequest->snapshotSwhId === null) {
        dd('no snapshot though request succeeded -> this should actually not happen');
    }
    else {
        $snapshot = $saveRequest->getSnapshot();
        $repoNode = new RepositoryNode($repoNodeUrl ?? $saveRequest->originUrl);
        $context = $snapshot->getContext($repoNode);
        dd('success', $snapshot, $context, $context->getIdentifier());
    }
}
else {
    dd('pending -> loop this code block again', $saveRequest);
}
```

### 3) Error Handling
If null is returned instead of an object of the requested type, that indicates that an error has occurred.
More information about the error can be obtained from the current `SwhWebApiClient` instance like so:
```php
$snapshot = Snapshot::byId('non-existing-or-invalid-id'); // to provoke an error 

if ($snapshot === null) {
    $swhClient = SwhWebApiClient::getCurrent();
    dd(
        $swhClient->getException(),     // last exception (e.g., in case of a network issue)
        $swhClient->getLastResponse()   // access the last HTTP response (incl. status code, headers) for debugging purposes 
    );
}
```

### 4) Caching and rate limit
To reduce the number of requests, all requests except for the `/origin/` and the `/stat/counters/` endpoint are cached.
The cache folder has to specified as absolute path in `config('swh.web-api.cache-folder')`.
To clear the cache, you can use the `clearCache` command: 
```php
$swhClient = SwhWebApiClient::getCurrent();
$swhClient->clearCache('2024-09-07'); // clears the cache for a specific date
$swhClient->clearCache(); // clears the whole cache
```
To obtain information about your rate-limiting, call
`$swhClient->getRateLimits();`. This will return an array of the following type:
```php
[
    'X-RateLimit-Limit' => 1200,      // max. number of permitted requests per hour
    'X-RateLimit-Remaining' => 1138,  // remaining in current period
    'X-RateLimit-Reset' => 1620639052 // at this timestamp, the rate-limit will be refreshed
]
```

 
