<?php

namespace Mozakar\Gateway\Http;

class HttpResponse
{
	public function __construct(
		private string $body,
		private int $status,
		private string $error = ''
	) {
	}

	public function body(): string
	{
		return $this->body;
	}

	public function status(): int
	{
		return $this->status;
	}

	public function error(): string
	{
		return $this->error;
	}

	public function successful(): bool
	{
		return $this->status === 200;
	}

	/**
	 * @return mixed
	 */
	public function json(bool $assoc = false)
	{
		if ($this->body === '') {
			return null;
		}

		return json_decode($this->body, $assoc);
	}
}
