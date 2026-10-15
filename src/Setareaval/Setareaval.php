<?php

namespace Mozakar\Gateway\Setareaval;

use Exception;
use Mozakar\Gateway\Http\HttpResponse;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Setareaval extends PortAbstract implements PortInterface
{
	protected ?string $callbackConfig = 'gateway.setare_aval.callback-url';

	private const BASE_URL = 'https://starsellers-api.setareyek.ir/api/v1/SetareAvalIPG/';
	private const TOKEN_PATH = 'GetToken?username=%s&password=%s';
	private const PURCHASE_PATH = 'Purchase';
	private const VERIFY_PATH = 'Verify';

	private const INQUIRY_PATH = 'Inquiry';
	private const REVERSE_PAYMENT_PATH = 'ReversPayment';
	private const REFUND_PAYMENT_PATH = 'RefundPayment';

	private array $orders = [];
	/**
	 * Set orders function
	 *
	 * @return $this
	 */
	public function setOrders(array $orders): self
	{
			$this->orders = $orders;
			return $this;
	}

	/**
	 * Get orders function
	 */
	public function getOrders(): array
	{
			return $this->orders;
	}
	/**
	 * {@inheritdoc}
	 */
	public function ready()
	{
		$this->tokenRequest();

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function redirect()
	{
		$paymentUrl = "";
		return redirect($paymentUrl);
	}

	/**
	 * {@inheritdoc}
	 */
	public function verify($transaction)
	{
		parent::verify($transaction);

		$this->verifyPayment();
		return $this;
	}

	/**
	 * Send token request to server
	 *
	 * @return void
	 *
	 * @throws SetareAvalException
	 */
	protected function tokenRequest(): void
	{
		$this->newTransaction();
		try {
            $username =  $this->config->get('gateway.setare_aval.username');
            $password =  $this->config->get('gateway.setare_aval.password');
            $path = sprintf(self::TOKEN_PATH, $username, $password);
			$result = json_decode($this->curl_get($path));

			if (isset($result->ResponseCode) && $result->ResponseCode != 0) {
				throw new SetareAvalException($result->ResponseCode, $result->Message ?? null);
			}

			if (! isset($result->BnplKey)) {
                throw new SetareAvalException(-2,json_encode($result));
			}

			$this->token = $result->BnplKey;
			$this->refId = $result->BnplKey;
			$this->transactionSetRefId();

		} catch (Exception $e) {
			$this->transactionFailed();
			$this->newLog('Exception', $e->getMessage());
			throw $e;
		}
	}

    /**
	 * Send pay request to server
	 *
	 * @return void
	 *
	 * @throws SetareAvalException
	 */
	protected function purchaseRequest()
	{
		try {
			$data = [
					'Merchant' 		=> $this->config->get('gateway.setare_aval.merchant'),
					'Amount' 			=> $this->amount,
					'CallbackUrl' => $this->getCallback(),
					'Mobile' 			=> $this->getMobile(),
					'Description' => $this->getCustomDesc(),
					'Orders'    	=> $this->getOrders()
			];
			$result = json_decode($this->curl_post(self::PURCHASE_PATH, $data));

			if (isset($result->ResponseCode) && $result->ResponseCode != 0) {
				throw new SetareAvalException($result->ResponseCode, $result->Message ?? null);
			}

			if (! isset($result->BnplKey)) {
                throw new SetareAvalException(-2,json_encode($result));
			}

			$this->token = $result->BnplKey;
			$this->refId = $result->trackId;
			$this->transactionSetRefId();

		} catch (Exception $e) {
			$this->transactionFailed();
			$this->newLog('Exception', $e->getMessage());
			throw $e;
		}
	}

	/**
	 * Verify user payment from bank server
	 *
	 * @throws SetareAvalException
	 */
	protected function verifyPayment()
	{
		$trackId =  $this->request->get('trackId', '');
		$data = [
				'Merchant' 	=> $this->config->get('gateway.setare_aval.merchant'),
				'trackId' 	=> $trackId,
		];

		try {
				$result = json_decode($this->curl_post($this->getUrl(self::VERIFY_PATH, false), $data));
				if (isset($result->ResCode) && $result->ResCode != 0) {
					$this->transactionFailed();
					throw new SetareAvalException($result->ResCode);
				}
				if (
                    !isset(
                            $result->Amount,
                            $result->SystemTraceNo,
                            $result->RetrivalRefNo,
                            $result->ResCode
                    )
				) {
						$this->transactionFailed();
						throw new SetareAvalException(-3);
				}

				if ($result->ResCode != 0) {
					$this->transactionFailed();
					throw new SetareAvalException($result->ResCode);
				}

				$data = array_merge($data, ['verify' => $result]);
				$this->transactionSetData($data);
				$this->trackingCode = $result->SystemTraceNo;
				$this->transactionSucceed();
			} catch (Exception $ex) {
				$this->newLog('Exception', $ex->getMessage());
				$this->transactionFailed();
				throw $ex;
			}
	}

   	
	/**
	 * inquiry
	 *
	 * @param  string $trackId
     * @throws Exception
	 * @return array
	 */
	public function inquiry(string $trackId): array
	{
		$data = [
				'Merchant' => $this->config->get('gateway.setare_aval.merchant'),
				'TrackId' => $trackId,
		];
		try {
			return json_decode($this->curl_post($this->getUrl(self::INQUIRY_PATH, false), $data));
        } catch (Exception $ex) {
            throw $ex;
        }
	}

    /**
	 * reverse payment
	 *
	 * @param  string $trackId
     * @throws Exception
	 * @return array
	 */
	public function reversePayment(string $trackId): array
	{
		$data = [
            'Merchant' => $this->config->get('gateway.setare_aval.merchant'),
            'TrackId' => $trackId,
		];
		try {
		    return json_decode($this->curl_post($this->getUrl(self::REVERSE_PAYMENT_PATH, false), $data));
        } catch (Exception $ex) {
            throw $ex;
        }
	}

    /**
	 * refund payment
	 *
	 * @param  string $trackId
     * @throws Exception
	 * @return array
	 */
	public function refund(string $trackId, int $amount): array
	{
		$data = [
            'Merchant' => $this->config->get('gateway.setare_aval.merchant'),
            'TrackId' => $trackId,
            'Amount' => $amount
		];
		try {
		    return json_decode($this->curl_post($this->getUrl(self::REFUND_PAYMENT_PATH, false), $data));
        } catch (Exception $ex) {
            throw $ex;
        }
	}


    /**
     * curl_get
     *
     * @param  string $path
     * @return string
     * @throws SetareAvalException
     */
    function curl_get(string $path): string
    {
        return $this->setareResponse($this->http()->get(self::BASE_URL . $path, $this->setareHeaders()));
    }

	/**
     * curl_post
     *
     * @param  string $path
     * @param  array $params
     * @return string
     * @throws SetareAvalException
     */
    function curl_post(string $path, array $params = []): string
    {
        return $this->setareResponse($this->http()->postJson(self::BASE_URL . $path, $params, $this->setareHeaders()));
    }

	/**
	 * @return string[]
	 */
	private function setareHeaders(): array
	{
		return [
			'Content-Type: application/json; charset=UTF-8',
		];
	}

	private function setareResponse(HttpResponse $response): string
	{
		if ($response->successful()) {
			return $response->body();
		}

		$this->transactionFailed();
		$decoded = $response->json();
		if (isset($decoded->ResponseCode)) {
			$message = isset($decoded->Message) ? $decoded->Message : '';
			throw new SetareAvalException($decoded->ResponseCode, $message);
		}
		if (isset($decoded->ResCode)) {
			$message = isset($decoded->Description) ? $decoded->Description : '';
			throw new SetareAvalException($decoded->ResCode, $message);
		}
		$this->newLog($response->status(), $response->body());
		throw new SetareAvalException(-1);
	}

}