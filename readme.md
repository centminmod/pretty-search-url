# Pretty Search Url Wordpress Plugin

This plugin allows Wordpress `?s=wordpress+cache` query strings to be redirected to pretty url format `/search/wordpress+cache/` which is a necessary step to enable Wordpress Cache Enabler plugin's advanced Nginx caching configuration with [Centmin Mod 123.09beta01 and higher's Wordpress auto installer setup](https://servermanager.guide/122/how-to-install-wordpress-on-centmin-mod-lemp-stack-guide/) as outlined [here](https://community.centminmod.com/threads/caching-search-results-with-standard-centminmod-install.20027/#post-85037).

Cache Enabler plugin has 2 methods of doing caching:

1. The default way for non-Centmin Mod LEMP stack plugin installs relies on Cache Enabler doing at PHP process level both cached file generation on requests and also doing the caching logic - determining if a request has an associated cached HTML page generated and whether to serve that cached HTML page or whether to bypass cache and grab the request from backend PHP/MySQL.


2. Cache Enabler also allows you to configure advanced caching where the second part for caching logic is offloaded from PHP to the web server i.e. Centmin Mod Nginx. This is what [Centmin Mod 123.09beta01 and higher's LEMP stack Wordpress autoinstaller](https://community.centminmod.com/threads/differences-between-wordpress-regular-install-vs-centmin-sh-menu-option-22-install.15435/) configures for Cache Enabler out of the box - advanced guest full HTML page caching for Wordpress requests where PHP only generates the cached files but offloads the caching logic itself from PHP to Nginx. This allows for much faster caching performance as Nginx can serve cached files much better than serving the cached files through PHP process. That is why Centmin Mod's auto configured Cache Enabler caching performance is better than other folks caching results when they only install Cache Enabler plugin without configuring any advanced web server level caching logic offload.

Cache Enabler in this advanced caching mode will then do guest based full HTML page caching for Wordpress search requests from pretty url format `/search/wordpress+cache/` by saving the cached full HTML page to local disk cached file via PHP process for the first request. Then subequent requests will bypass PHP completely and move caching logic to Nginx level. So if a cached version of the request exists, Nginx server itself (not PHP) will determine if it will serve the cached file or if it detects a query string request i.e. `?s=wordpress+cache`, it will bypass Wordpress Pretty Search Url plugin's PHP logic for 302 redirect and let Nginx server itself do the 302 redirect to `/search/wordpress+cache/`. Allowing Nginx to do the 302 redirect instead of Pretty Search Url plugin's PHP based 302 redirect, results in up to 44x times faster performance!

# Install

You can upload contents of this repository to `wp-content/plugins/pretty-search-url` directory you manually create and actiavte plugin from within Wordpress Admin.

Or for [Centmin Mod LEMP stack Nginx users](https://centminmod.com), install from SSH command line - replacing domain variable `domain.com` with your domain name:

```
domain=domain.com
cd /home/nginx/domains/$domain/public/wp-content/plugins/
mkdir -p pretty-search-url
wget -O /home/nginx/domains/$domain/public/wp-content/plugins/pretty-search-url/pretty-search-url.php https://github.com/centminmod/pretty-search-url/raw/master/pretty-search-url.php
wget -O /home/nginx/domains/$domain/public/wp-content/plugins/pretty-search-url/index.html https://github.com/centminmod/pretty-search-url/raw/master/index.html
wget -O /home/nginx/domains/$domain/public/wp-content/plugins/pretty-search-url/readme.md https://github.com/centminmod/pretty-search-url/blob/master/readme.md
#wget -O /home/nginx/domains/$domain/public/wp-content/plugins/pretty-search-url/LICENSE https://github.com/centminmod/pretty-search-url/raw/master/LICENSE
chown -R nginx:nginx /home/nginx/domains/$domain/public/wp-content/plugins/pretty-search-url
ls -lah /home/nginx/domains/$domain/public/wp-content/plugins/pretty-search-url
cd /home/nginx/domains/$domain/public
wp plugin activate pretty-search-url
wp plugin status pretty-search-url
```

Activate plugin

```
domain=domain.com
cd /home/nginx/domains/$domain/public

wp plugin activate pretty-search-url  
Plugin 'pretty-search-url' activated.
Success: Activated 1 of 1 plugins.
```

Check Status of plugin

```
domain=domain.com
cd /home/nginx/domains/$domain/public

wp plugin status pretty-search-url
Plugin pretty-search-url details:
    Name: Pretty Search Url
    Status: Active
    Version: 0.1
    Author: George Liu
    Description: Redirect search to pretty /search/ urls
```

Deactivate plugin

```
domain=domain.com
cd /home/nginx/domains/$domain/public

wp plugin deactivate pretty-search-url
Plugin 'pretty-search-url' deactivated.
Success: Deactivated 1 of 1 plugins.
```

# Example Enabling Wordpress Search Caching With Cache Enabler

Wordpress Cache Enabler plugin's advanced Nginx caching configuration with Centmin Mod 123.09beta01 and higher's Wordpress auto installer's Cache Enabler setup as outlined [here](https://community.centminmod.com/threads/caching-search-results-with-standard-centminmod-install.20027/#post-85037) after installing the Pretty Search Url plugin and making the following changes to KeyCDN's Cache Enabler's `cache_enabler.class.php` file to remove `is_search()` cache exclusion:

```
diff -u cache_enabler.class.php-orig cache_enabler.class.php-removesearch 
--- cache_enabler.class.php-orig        2020-07-17 05:33:33.933006105 +0000
+++ cache_enabler.class.php-removesearch        2020-07-17 05:34:17.572342714 +0000
@@ -1407,7 +1407,7 @@
         }
 
         // conditional tags
-        if ( self::_is_index() OR is_search() OR is_404() OR is_feed() OR is_trackback() OR is_robots() OR is_preview() OR post_password_required() ) {
+        if ( self::_is_index() OR is_404() OR is_feed() OR is_trackback() OR is_robots() OR is_preview() OR post_password_required() ) {
             return true;
         }
```

I've posted an issue request on Cache Enabler's Github repo at https://github.com/keycdn/cache-enabler/issues/83 to add support for toggling Wordpress search cache exclusion at the WP admin dashboard level so that we don't need to do the above modifications. Fingers crossed :)

Then in `/usr/local/nginx/conf/wpincludes/cache-enabler.domain.com/wpcacheenabler_cache-enabler.domain.com.conf` where domain of wordpress site = `cache-enabler.domain.com` is generated by centmin.sh menu option 22, reassigning $cache_uri with `/search/wordpres+cache` by changing parts of includes above the initial `set $cache_enabler_uri` to

July 20, 2020 updated config so Nginx takes over from Wordpress redirects so PHP-FPM request is totally bypassed for query string `?s=` requests and specific `/search/` location match so that you can do more specific things in future i.e. rate limit within `/search/` if needed

In `/usr/local/nginx/conf/wpincludes/cache-enabler.domain.com/wpcacheenabler_cache-enabler.domain.com.conf`

change from

```
    # default html file
    set $cache_enabler_uri '${custom_subdir}/wp-content/cache/cache-enabler/${http_host}${cache_uri}index.html';

    # webp html file
    if ($http_accept ~* "image/webp") {
        set $cache_enabler_uri_webp '${custom_subdir}/wp-content/cache/cache-enabler/${http_host}${cache_uri}index-webp.html';
    }
```

to

```
    if ($args ~* s=(.*)) {
      set $cache_uri $request_uri;
      set $check_surl $cache_uri;
      set $cache_uri /search/$1/;
      set $cache_uri_search /search/$1/;
      set $cache_enabler_uri_search '${custom_subdir}/wp-content/cache/cache-enabler/${http_host}${cache_uri_search}index.html';
    }
    location ~ /search/(.*) {
      set $cache_uri $request_uri;
      set $check_surl $cache_uri;
      set $cache_uri_search $request_uri;
      set $cache_enabler_uri_search '${custom_subdir}/wp-content/cache/cache-enabler/${http_host}${check_surl}index.html';
      try_files $cache_enabler_uri_search $cache_enabler_uri_webp $cache_enabler_uri $uri $uri/ $custom_subdir/index.php?$args;
    }
    add_header Check-Uri "$check_surl";
    add_header Set-Uri "$cache_uri";

    # default html file
    set $cache_enabler_uri '${custom_subdir}/wp-content/cache/cache-enabler/${http_host}${cache_uri}index.html';

    # webp html file
    if ($http_accept ~* "image/webp") {
        set $cache_enabler_uri_webp '${custom_subdir}/wp-content/cache/cache-enabler/${http_host}${cache_uri}index-webp.html';
    }

    if (-f $document_root$cache_enabler_uri_search) {
      set $search_exists $cache_enabler_uri_search;
      return 302 https://$host$cache_uri_search;
    }
    if (!-f $document_root$cache_enabler_uri_search) {
      set $search_exists $cache_enabler_uri_search;
    }
    #add_header Check-File "$search_exists";
```
So now returned header via `Set-Uri` is = `$cache_uri`

```
curl -IL http://cache-enabler.domain.com/?s=wordpress+cache
HTTP/1.1 200 OK
Date: Thu, 16 Jul 2020 15:07:02 GMT
Content-Type: text/html; charset=utf-8
Content-Length: 25301
Last-Modified: Thu, 16 Jul 2020 14:41:58 GMT
Connection: keep-alive
Vary: Accept-Encoding
ETag: "5f106736-62d5"
Server: nginx centminmod
X-Powered-By: centminmod
X-Xss-Protection: 1; mode=block
X-Content-Type-Options: nosniff
Check-Uri: /?s=wordpress+cache
Set-Uri: /search/wordpress+cache/
Accept-Ranges: bytes
```
direct cached url access
```
curl -I http://cache-enabler.domain.com/search/worldpress+cache/
HTTP/1.1 200 OK
Date: Thu, 16 Jul 2020 15:08:23 GMT
Content-Type: text/html; charset=utf-8
Content-Length: 19736
Last-Modified: Thu, 16 Jul 2020 15:07:54 GMT
Connection: keep-alive
Vary: Accept-Encoding
ETag: "5f106d4a-4d18"
Server: nginx centminmod
X-Powered-By: centminmod
X-Xss-Protection: 1; mode=block
X-Content-Type-Options: nosniff
Set-Uri: /search/worldpress+cache/
Accept-Ranges: bytes
```

### wrk-cmm with wordpress search cached

Before July 20, 2020 load test with my forked wrk
```
wrk-cmm -t4 -c50 -d20s --latency --breakout http://cache-enabler.domain.com/search/worldpress+cache/
Running 20s test @ http://cache-enabler.domain.com/search/worldpress+cache/
  4 threads and 50 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     1.01ms    4.16ms  99.65ms   97.01%
    Connect   159.21us   71.17us 288.00us   58.33%
    TTFB        1.00ms    4.16ms  99.64ms   97.01%
    TTLB       10.21us   32.01us  11.02ms   99.97%
    Req/Sec    23.94k     9.82k   45.89k    64.12%
  Latency Distribution
     50%  415.00us
     75%  603.00us
     90%    1.19ms
     99%   10.78ms
  1907359 requests in 20.04s, 35.79GB read
Requests/sec:  95189.83
Transfer/sec:      1.79GB
```
July 20, 2020 updated load test with my forked wrk
```
wrk-cmm -t4 -c50 -d20s --latency --breakout http://cache-enabler.domain.com/search/worldpress+cache/
Running 20s test @ http://cache-enabler.domain.com/search/worldpress+cache/
  4 threads and 50 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     2.25ms   15.86ms 301.40ms   98.66%
    Connect   203.46us  148.93us 660.00us   87.50%
    TTFB        2.24ms   15.87ms 301.39ms   98.66%
    TTLB       10.40us   37.28us   9.02ms   99.96%
    Req/Sec    28.00k     9.43k   44.87k    66.67%
  Latency Distribution
     50%  311.00us
     75%  534.00us
     90%    1.61ms
     99%   49.76ms
  2226160 requests in 20.07s, 41.85GB read
Requests/sec: 110910.78
Transfer/sec:      2.08GB
```
Before July 20, 2020 direct query search cached
```
wrk-cmm -t4 -c50 -d20s --latency --breakout http://cache-enabler.domain.com/?s=wordpress+cache             
Running 20s test @ http://cache-enabler.domain.com/?s=wordpress+cache
  4 threads and 50 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   738.15us    2.44ms 108.60ms   95.81%
    Connect   144.73us   69.16us 280.00us   60.42%
    TTFB      724.86us    2.44ms 108.58ms   95.81%
    TTLB       13.46us   58.63us  14.02ms   99.96%
    Req/Sec    26.26k     6.27k   39.64k    70.00%
  Latency Distribution
     50%  351.00us
     75%  546.00us
     90%    1.21ms
     99%    7.54ms
  2092164 requests in 20.03s, 50.16GB read
Requests/sec: 104448.19
Transfer/sec:      2.50GB
```
July 20, 2020 updated direct query search cached which totally bypasses PHP-FPM if cached file exists
```
wrk-cmm -t4 -c50 -d20s --latency --breakout http://cache-enabler.domain.com/?s=wordpress+cache
Running 20s test @ http://cache-enabler.domain.com/?s=wordpress+cache
  4 threads and 50 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   775.44us    1.85ms  29.36ms   91.06%
    Connect   237.04us  131.89us 567.00us   62.75%
    TTFB      771.92us    1.85ms  29.36ms   91.06%
    TTLB        2.09us   32.86us  10.16ms   99.98%
    Req/Sec    50.01k    12.86k   71.21k    68.12%
  Latency Distribution
     50%  151.00us
     75%  308.00us
     90%    2.14ms
     99%    9.24ms
  3985306 requests in 20.08s, 2.36GB read
Requests/sec: 198476.64
Transfer/sec:    120.38MB
```
### wrk-cmm with wordpress search non-cached

compared to default without wordpress search cache
```
curl -IL http://cache-enabler.domain.com/?s=wordpress+cache                                                                                
HTTP/1.1 200 OK
Date: Thu, 16 Jul 2020 15:46:04 GMT
Content-Type: text/html; charset=UTF-8
Connection: keep-alive
Vary: Accept-Encoding
Link: <http://cache-enabler.domain.com/wp-json/>; rel="https://api.w.org/"
Server: nginx centminmod
X-Powered-By: centminmod
X-Xss-Protection: 1; mode=block
X-Content-Type-Options: nosniff
Check-Uri: /?s=wordpress+cache
Set-Uri: /?s=wordpress+cache
```
cached search at 95,189 or 104,448 requests/sec versus non-cached search at 332 requests/sec
```
wrk-cmm -t4 -c50 -d20s --latency --breakout http://cache-enabler.domain.com/?s=wordpress+cache            
Running 20s test @ http://cache-enabler.domain.com/?s=wordpress+cache
  4 threads and 50 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   143.66ms   16.60ms 238.90ms   68.44%
    Connect   165.79us   83.37us 334.00us   56.25%
    TTFB      143.60ms   16.60ms 238.79ms   68.47%
    TTLB       63.16us   25.84us   1.19ms   85.77%
    Req/Sec    83.38     14.48   121.00     68.62%
  Latency Distribution
     50%  144.40ms
     75%  154.74ms
     90%  163.80ms
     99%  185.14ms
  6663 requests in 20.02s, 181.04MB read
Requests/sec:    332.85
Transfer/sec:      9.04MB
```

# Custom /search/ cache invalidation cronjob

Centmin Mod 123.09beta01's centmin.sh menu option 22 wordpress auto installer with Cache Enabler caching selected will also generate an optional cronjob to invalidate and remove Cache Enabler cached files from cache and look similar to:

Remove Cache Enabler's cached files every day at 11:16pm (every 24hrs).

```
16 23 * * * echo "cache-enabler.domain.com cacheenabler cron"; sleep 174s ; rm -rf /home/nginx/domains/cache-enabler.domain.com/public/wp-content/cache/cache-enabler/* > /dev/null 2>&1
```

With above `/search/` caching modifications, you could setup a separate `/search/` cache invalidation cronjob which can have a shorter or longer interval than default Cache Enabler cache time.

For example delete `/search/` cached files every 5 minutes

```
*/5 * * * * echo "cache-enabler.domain.com cacheenabler search cron"; sleep 14s ; rm -rf /home/nginx/domains/cache-enabler.domain.com/public/wp-content/cache/cache-enabler/search/* > /dev/null 2>&1
```