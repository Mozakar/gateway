<?php

namespace Mozakar\Gateway\Http;

class HttpClient
{
	/**
	 * @param array<int|string, mixed>|string|null $body
	 * @param array<int|string, string> $headers
	 * @param array<string, mixed> $options verify_peer, verify_host, userpwd, verbose
	 */
	public function request(string $method, string $url, $body = null, array $headers = [], array $options = []): HttpResponse
	{
		$handle = curl_init();
		$method = strtoupper($method);

		curl_setopt($handle, CURLOPT_URL, $url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, $options['verify_peer'] ?? false);

		if (array_key_exists('verify_host', $options)) {
			if ($options['verify_host'] !== null) {
				curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, $options['verify_host']);
			}
		} else {
			curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
		}

		if ($headers !== []) {
			curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
		}

		if (!empty($options['userpwd'])) {
			curl_setopt($handle, CURLOPT_USERPWD, $options['userpwd']);
		}

		if (!empty($options['verbose'])) {
			curl_setopt($handle, CURLOPT_VERBOSE, true);
		}

		if ($method === 'GET') {
			curl_setopt($handle, CURLOPT_HTTPGET, true);
		} else {
			curl_setopt($handle, CURLOPT_POST, true);
			if ($body !== null) {
				curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
			}
		}

		$result = curl_exec($handle);
		$status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
		$error = curl_error($handle);
		curl_close($handle);

		return new HttpResponse($result === false ? '' : (string) $result, $status, $error);
	}

	/**
	 * @param array<int|string, mixed>|string|null $body
	 * @param array<int|string, string> $headers
	 * @param array<string, mixed> $options
	 */
	public function post(string $url, $body = null, array $headers = [], array $options = []): HttpResponse
	{
		return $this->request('POST', $url, $body, $headers, $options);
	}

	/**
	 * @param array<int|string, string> $headers
	 * @param array<string, mixed> $options
	 */
	public function get(string $url, array $headers = [], array $options = []): HttpResponse
	{
		return $this->request('GET', $url, null, $headers, $options);
	}

	/**
	 * @param array<string, mixed> $params
	 * @param array<int|string, string> $headers
	 * @param array<string, mixed> $options
	 */
	public function postJson(string $url, array $params = [], array $headers = [], array $options = []): HttpResponse
	{
		$json = json_encode($params);

		return $this->post($url, $json === false ? '' : $json, $headers, $options);
	}
}
