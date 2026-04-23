<?php

namespace Larva\Volc;

use Closure;
use Exception;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;

/**
 * This is NOT a freeware, use is subject to license terms.
 */
class VolcMiddleware
{
    const ISO8601_BASIC = 'Ymd\THis\Z';

    protected string $ak = '';
    protected string $sk = '';
    protected string $region = 'cn-beijing';
    protected string $service = 'TLS';

    protected static array $headerBlacklist = [
        'cache-control' => true,
        'content-type' => true,
        'content-length' => true,
        'expect' => true,
        'max-forwards' => true,
        'pragma' => true,
        'range' => true,
        'te' => true,
        'if-match' => true,
        'if-none-match' => true,
        'if-modified-since' => true,
        'if-unmodified-since' => true,
        'if-range' => true,
        'accept' => true,
        'authorization' => true,
        'proxy-authorization' => true,
        'from' => true,
        'referer' => true,
        'user-agent' => true
    ];

    public function __construct(string $ak, string $sk, string $region, string $service)
    {
        $this->ak = $ak;
        $this->sk = $sk;
        $this->region = $region;
        $this->service = $service;
    }

    /**
     * Called when the middleware is handled.
     *
     * @param  callable  $handler
     *
     * @return Closure
     */
    public function __invoke(callable $handler): Closure
    {
        return function ($request, array $options) use ($handler) {
            $request = $this->onBefore($request);
            return $handler($request, $options);
        };
    }

    /**
     * 请求前调用
     * @param  RequestInterface  $request
     * @return RequestInterface
     * @throws Exception
     */
    private function onBefore(RequestInterface $request): RequestInterface
    {
        $ldt = gmdate(self::ISO8601_BASIC);
        $sdt = substr($ldt, 0, 8);
        $parsed = $this->parseRequest($request);
        $payload = $this->getPayload($request);
        $parsed['headers']['X-Date'] = [$ldt];
        $parsed['headers']['X-Tls-Apiversion'] = ['0.3.0'];
        $parsed['headers']['X-Content-Sha256'] = [$payload];

        $cs = $this->createScope($sdt, $this->region, $this->service);
        $context = $this->createContext($parsed, $payload);
        $toSign = $this->createStringToSign($ldt, $cs, $context['creq']);
        $signingKey = $this->getSigningKey($sdt, $this->region, $this->service, $this->sk);
        $signature = hash_hmac('sha256', $toSign, $signingKey);

        $parsed['headers']['Authorization'] = [
            "HMAC-SHA256 "
            ."Credential={$this->ak}/{$cs}, "
            ."SignedHeaders={$context['headers']}, Signature={$signature}"
        ];
        return $this->buildRequest($parsed);
    }

    /**
     * @param $shortDate
     * @param $region
     * @param $service
     * @param $secretKey
     * @return string
     */
    private function getSigningKey($shortDate, $region, $service, $secretKey): string
    {
        $dateKey = hash_hmac('sha256', $shortDate, "{$secretKey}", true);
        $regionKey = hash_hmac('sha256', $region, $dateKey, true);
        $serviceKey = hash_hmac('sha256', $service, $regionKey, true);
        return hash_hmac('sha256', 'request', $serviceKey, true);
    }

    private function createScope($shortDate, $region, $service): string
    {
        return "$shortDate/$region/$service/request";
    }

    private function createStringToSign($longDate, $credentialScope, $creq): string
    {
        $hash = hash('sha256', $creq);

        return "HMAC-SHA256\n{$longDate}\n{$credentialScope}\n{$hash}";
    }

    /**
     * @param  array  $parsedRequest
     * @param  string  $payload  Hash of the request payload
     * @return array Returns an array of context information
     */
    private function createContext(array $parsedRequest, string $payload): array
    {
        // Normalize the path as required by SigV4
        $canon = $parsedRequest['method']."\n"
            .$this->createCanonicalizedPath($parsedRequest['path'])."\n"
            .$this->getCanonicalizedQuery($parsedRequest['query'])."\n";

        $signedHeadersString = '';
        $canonHeaders = [];
        // Case-insensitively aggregate all of the headers.
        if (!isset($parsedRequest['query']['X-SignedQueries'])) {
            $aggregate = [];
            foreach ($parsedRequest['headers'] as $key => $values) {
                $key = strtolower($key);
                if (!isset(self::$headerBlacklist[$key])) {
                    foreach ($values as $v) {
                        $aggregate[$key][] = $v;
                    }
                }
            }

            ksort($aggregate);
            foreach ($aggregate as $k => $v) {
                if (count($v) > 0) {
                    sort($v);
                }
                $canonHeaders[] = $k.':'.preg_replace('/\s+/', ' ', implode(',', $v));
            }

            $signedHeadersString = implode(';', array_keys($aggregate));
        }
        $canon .= implode("\n", $canonHeaders)."\n\n"
            .$signedHeadersString."\n"
            .$payload;

        return ['creq' => $canon, 'headers' => $signedHeadersString];
    }

    /**
     * @throws Exception
     */
    protected function getPayload(RequestInterface $request): string
    {
        // Calculate the request signature payload
        if ($request->hasHeader('X-Content-Sha256')) {
            // Handle streaming operations (e.g. Glacier.UploadArchive)
            return $request->getHeaderLine('X-Content-Sha256');
        }

        if (!$request->getBody()->isSeekable()) {
            throw new Exception(
                'A sha256 checksum could not be calculated for the provided upload body, because it was not '
                .'seekable. To prevent this error you can either 1) include the ContentSHA256 parameter with '
                .'your request, 2) use a seekable stream for the body, or 3) wrap the non-seekable stream in '
                .'a guzzle\\Psr7\\CachingStream object.'
            );
        }

        try {
            return Utils::hash($request->getBody(), 'sha256');
        } catch (Exception $e) {
            throw new Exception('A sha256 checksum could not be calculated. Verify that the hash extension is installed.', 0, $e);
        }
    }

    /**
     * 解析请求
     * @param  RequestInterface  $request
     * @return array
     */
    private function parseRequest(RequestInterface $request): array
    {
        // Clean up any previously set headers.
        /** @var RequestInterface $request */
        $request = $request
            ->withoutHeader('X-Date')
            ->withoutHeader('Date')
            ->withoutHeader('Authorization');
        $uri = $request->getUri();

        return [
            'method' => $request->getMethod(),
            'path' => $uri->getPath(),
            'query' => Query::parse($uri->getQuery()),
            'uri' => $uri,
            'headers' => $request->getHeaders(),
            'body' => $request->getBody(),
            'version' => $request->getProtocolVersion()
        ];
    }

    /**
     * 根据提供的参数构建一个新的请求
     * @param  array  $req
     * @return RequestInterface
     */
    private function buildRequest(array $req): RequestInterface
    {
        if ($req['query']) {
            $req['uri'] = $req['uri']->withQuery(Query::build($req['query']));
        }

        return new Request($req['method'], $req['uri'], $req['headers'], $req['body'], $req['version']);
    }

    private function getCanonicalizedQuery(array $query): string
    {
        unset($query['X-Signature']);

        if (!$query) {
            return '';
        }

        $qs = '';
        if (isset($query['X-SignedQueries'])) {
            foreach (explode(';', $query['X-SignedQueries']) as $k) {
                $v = $query[$k];
                if (!is_array($v)) {
                    $qs .= rawurlencode($k).'='.rawurlencode($v).'&';
                } else {
                    sort($v);
                    foreach ($v as $value) {
                        $qs .= rawurlencode($k).'='.rawurlencode($value).'&';
                    }
                }
            }
        } else {
            ksort($query);
            foreach ($query as $k => $v) {
                if (!is_array($v)) {
                    $qs .= rawurlencode($k).'='.rawurlencode($v).'&';
                } else {
                    sort($v);
                    foreach ($v as $value) {
                        $qs .= rawurlencode($k).'='.rawurlencode($value).'&';
                    }
                }
            }
        }

        return substr($qs, 0, -1);
    }

    protected function createCanonicalizedPath($path): string
    {
        $doubleEncoded = rawurlencode(ltrim($path, '/'));

        return '/'.str_replace('%2F', '/', $doubleEncoded);
    }
}
