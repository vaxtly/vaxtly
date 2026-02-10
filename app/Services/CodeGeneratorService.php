<?php

namespace App\Services;

class CodeGeneratorService
{
    private VariableSubstitutionService $substitutionService;

    private ?string $collectionId;

    /** @var array{method: string, url: string, headers: array, queryParams: array, body: string, bodyType: string, formData: array, authType: string, authToken: string, authUsername: string, authPassword: string, apiKeyName: string, apiKeyValue: string} */
    private array $requestData;

    public function __construct(
        VariableSubstitutionService $substitutionService,
        ?string $collectionId = null,
    ) {
        $this->substitutionService = $substitutionService;
        $this->collectionId = $collectionId;
    }

    /** @param array{method: string, url: string, headers: array, queryParams: array, body: string, bodyType: string, formData: array, authType: string, authToken: string, authUsername: string, authPassword: string, apiKeyName: string, apiKeyValue: string} $requestData */
    public function generate(string $language, array $requestData): string
    {
        $this->requestData = $requestData;

        return match ($language) {
            'curl' => $this->generateCurl(),
            'python' => $this->generatePython(),
            'php' => $this->generatePhp(),
            'javascript' => $this->generateJavascript(),
            'node' => $this->generateNode(),
            default => $this->generateCurl(),
        };
    }

    private function sub(mixed $text): string
    {
        if (! is_string($text)) {
            $text = is_array($text) || is_object($text) ? json_encode($text) : (string) $text;
        }

        return $this->substitutionService->substitute($text, $this->collectionId);
    }

    private function buildResolvedHeaders(): array
    {
        $headers = [];
        foreach ($this->requestData['headers'] as $header) {
            if (! empty($header['key']) && ($header['enabled'] ?? true)) {
                $headers[$this->sub($header['key'])] = $this->sub($header['value']);
            }
        }

        switch ($this->requestData['authType']) {
            case 'bearer':
                if (! empty($this->requestData['authToken'])) {
                    $headers['Authorization'] = 'Bearer '.$this->sub($this->requestData['authToken']);
                }
                break;
            case 'basic':
                if (! empty($this->requestData['authUsername'])) {
                    $username = $this->sub($this->requestData['authUsername']);
                    $password = $this->sub($this->requestData['authPassword']);
                    $headers['Authorization'] = 'Basic '.base64_encode($username.':'.$password);
                }
                break;
            case 'api-key':
                if (! empty($this->requestData['apiKeyName']) && ! empty($this->requestData['apiKeyValue'])) {
                    $headers[$this->sub($this->requestData['apiKeyName'])] = $this->sub($this->requestData['apiKeyValue']);
                }
                break;
        }

        return $headers;
    }

    private function buildResolvedUrl(): string
    {
        $url = $this->sub($this->requestData['url']);
        $params = [];
        foreach ($this->requestData['queryParams'] as $param) {
            if (! empty($param['key']) && ($param['enabled'] ?? true)) {
                $params[$this->sub($param['key'])] = $this->sub($param['value']);
            }
        }

        if (! empty($params)) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($params);
        }

        return $url;
    }

    private function getResolvedBody(): ?string
    {
        $bodyType = $this->requestData['bodyType'];
        $body = $this->requestData['body'];
        $formData = $this->requestData['formData'];

        return match ($bodyType) {
            'json' => ! empty($body) ? $this->sub($body) : null,
            'form-data', 'urlencoded' => ! empty(array_filter($formData, fn ($f) => ! empty($f['key'])))
                ? json_encode(collect($formData)->filter(fn ($f) => ! empty($f['key']))->mapWithKeys(fn ($f) => [$this->sub($f['key']) => $this->sub($f['value'])])->toArray())
                : null,
            'raw' => ! empty($body) ? $this->sub($body) : null,
            default => null,
        };
    }

    private function generateCurl(): string
    {
        $method = strtoupper($this->requestData['method']);
        $url = $this->buildResolvedUrl();
        $headers = $this->buildResolvedHeaders();
        $body = $this->getResolvedBody();
        $bodyType = $this->requestData['bodyType'];

        $parts = ['curl'];

        if ($method !== 'GET') {
            $parts[] = '-X '.$method;
        }

        $parts[] = "'".addcslashes($url, "'")."'";

        foreach ($headers as $key => $value) {
            $parts[] = "-H '".addcslashes($key.': '.$value, "'")."'";
        }

        if ($body !== null) {
            if ($bodyType === 'json') {
                $parts[] = "-H 'Content-Type: application/json'";
                $parts[] = "-d '".addcslashes($body, "'")."'";
            } elseif ($bodyType === 'urlencoded') {
                $decoded = json_decode($body, true) ?? [];
                $parts[] = '--data-urlencode '.implode(' --data-urlencode ', array_map(
                    fn ($k, $v) => "'".addcslashes($k.'='.$v, "'")."'",
                    array_keys($decoded),
                    $decoded
                ));
            } elseif ($bodyType === 'form-data') {
                $decoded = json_decode($body, true) ?? [];
                foreach ($decoded as $k => $v) {
                    $parts[] = "-F '".addcslashes($k.'='.$v, "'")."'";
                }
            } else {
                $parts[] = "-d '".addcslashes($body, "'")."'";
            }
        }

        return implode(" \\\n  ", $parts);
    }

    private function generatePython(): string
    {
        $method = strtolower($this->requestData['method']);
        $url = $this->buildResolvedUrl();
        $headers = $this->buildResolvedHeaders();
        $body = $this->getResolvedBody();
        $bodyType = $this->requestData['bodyType'];

        $lines = ['import requests', ''];

        if (! empty($headers)) {
            $lines[] = 'headers = '.$this->toPythonDict($headers);
            $lines[] = '';
        }

        $args = ["'".addcslashes($url, "'")."'"];
        if (! empty($headers)) {
            $args[] = 'headers=headers';
        }

        if ($body !== null && in_array($method, ['post', 'put', 'patch'])) {
            if ($bodyType === 'json') {
                $lines[] = 'payload = '.str_replace(['":"', '","', '{"', '"}'], ['": "', '", "', '{"', '"}'], $body);
                $args[] = 'json=payload';
            } else {
                $lines[] = "data = '".addcslashes($body, "'")."'";
                $args[] = 'data=data';
            }
            $lines[] = '';
        }

        $lines[] = 'response = requests.'.$method.'('.implode(', ', $args).')';
        $lines[] = 'print(response.status_code)';
        $lines[] = 'print(response.json())';

        return implode("\n", $lines);
    }

    private function generatePhp(): string
    {
        $method = strtoupper($this->requestData['method']);
        $url = $this->buildResolvedUrl();
        $headers = $this->buildResolvedHeaders();
        $body = $this->getResolvedBody();
        $bodyType = $this->requestData['bodyType'];

        $lines = ['use Illuminate\\Support\\Facades\\Http;', ''];

        $chain = ['Http'];

        if (! empty($headers)) {
            $headersParts = [];
            foreach ($headers as $k => $v) {
                $headersParts[] = "    '".addcslashes($k, "'")."' => '".addcslashes($v, "'")."'";
            }
            $chain[] = "::withHeaders([\n".implode(",\n", $headersParts).",\n])";
        }

        if ($bodyType === 'json' && $body !== null) {
            $chain[] = (count($chain) > 1 ? '' : '::').'asJson()';
        } elseif ($bodyType === 'urlencoded') {
            $chain[] = (count($chain) > 1 ? '' : '::').'asForm()';
        }

        $methodCall = strtolower($method);
        $bodyArg = '';
        if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            if ($bodyType === 'json') {
                $bodyArg = ', json_decode(\''.addcslashes($body, "'").'\', true)';
            } else {
                $bodyArg = ", '".addcslashes($body, "'")."'";
            }
        }

        $call = (count($chain) > 1 ? "\n    ->" : '::').$methodCall."('".addcslashes($url, "'")."'".$bodyArg.')';
        $chain[] = $call;

        $code = '$response = '.implode('', $chain).';';
        $lines[] = $code;
        $lines[] = '';
        $lines[] = '$response->status();';
        $lines[] = '$response->json();';

        return implode("\n", $lines);
    }

    private function generateJavascript(): string
    {
        $method = strtoupper($this->requestData['method']);
        $url = $this->buildResolvedUrl();
        $headers = $this->buildResolvedHeaders();
        $body = $this->getResolvedBody();
        $bodyType = $this->requestData['bodyType'];

        $options = ["  method: '{$method}'"];

        if (! empty($headers)) {
            $headerLines = [];
            foreach ($headers as $k => $v) {
                $headerLines[] = "    '".addcslashes($k, "'")."': '".addcslashes($v, "'")."'";
            }
            $options[] = "  headers: {\n".implode(",\n", $headerLines)."\n  }";
        }

        if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            if ($bodyType === 'json') {
                $options[] = '  body: JSON.stringify('.trim($body).')';
            } else {
                $options[] = "  body: '".addcslashes($body, "'")."'";
            }
        }

        $lines = [
            "const response = await fetch('".addcslashes($url, "'")."', {",
            implode(",\n", $options),
            '});',
            '',
            'const data = await response.json();',
            'console.log(response.status, data);',
        ];

        return implode("\n", $lines);
    }

    private function generateNode(): string
    {
        $method = strtolower($this->requestData['method']);
        $url = $this->buildResolvedUrl();
        $headers = $this->buildResolvedHeaders();
        $body = $this->getResolvedBody();
        $bodyType = $this->requestData['bodyType'];

        $lines = ["import axios from 'axios';", ''];

        $config = [];

        if (! empty($headers)) {
            $headerLines = [];
            foreach ($headers as $k => $v) {
                $headerLines[] = "    '".addcslashes($k, "'")."': '".addcslashes($v, "'")."'";
            }
            $config[] = "  headers: {\n".implode(",\n", $headerLines)."\n  }";
        }

        $args = ["'".addcslashes($url, "'")."'"];
        if ($body !== null && in_array($method, ['post', 'put', 'patch'])) {
            $args[] = $bodyType === 'json' ? trim($body) : "'".addcslashes($body, "'")."'";
        }
        if (! empty($config)) {
            $args[] = "{\n".implode(",\n", $config)."\n}";
        }

        $lines[] = 'const response = await axios.'.$method.'('.implode(', ', $args).');';
        $lines[] = '';
        $lines[] = 'console.log(response.status, response.data);';

        return implode("\n", $lines);
    }

    /** @param array<string, string> $data */
    private function toPythonDict(array $data): string
    {
        $items = [];
        foreach ($data as $k => $v) {
            $items[] = "    '".addcslashes($k, "'")."': '".addcslashes($v, "'")."'";
        }

        return "{\n".implode(",\n", $items)."\n}";
    }
}
