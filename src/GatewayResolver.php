<?php

namespace Mozakar\Gateway;

use Mozakar\Gateway\Apsan\Apsan;
use Mozakar\Gateway\Nikan\Nikan;
use Mozakar\Gateway\Payir\Payir;
use Mozakar\Gateway\Sadad\Sadad;
use Mozakar\Gateway\Saman\Saman;
use Illuminate\Support\Facades\DB;
use Mozakar\Gateway\Mellat\Mellat;
use Mozakar\Gateway\Vandar\Vandar;
use Mozakar\Gateway\Digipay\Digipay;
use Mozakar\Gateway\Parsian\Parsian;
use Mozakar\Gateway\Payping\Payping;
use Mozakar\Gateway\Irankish\Irankish;
use Mozakar\Gateway\Pasargad\Pasargad;
use Mozakar\Gateway\SamanOld\SamanOld;
use Mozakar\Gateway\Zarinpal\Zarinpal;
use Mozakar\Gateway\Sadadbnpl\Sadadbnpl;
use Mozakar\Gateway\Asanpardakht\Asanpardakht;
use Mozakar\Gateway\Exceptions\RetryException;
use Mozakar\Gateway\Exceptions\PortNotFoundException;
use Mozakar\Gateway\Exceptions\InvalidRequestException;
use Mozakar\Gateway\Exceptions\NotFoundTransactionException;
use Mozakar\Gateway\Miligold\Miligold;
use Mozakar\Gateway\Zibal\Zibal;
// use Mozakar\Gateway\JahanPay\JahanPay;
// use Mozakar\Gateway\Payline\Payline;
// use Mozakar\Gateway\Setareaval\Setareaval;

class GatewayResolver
{

	protected $request;

	/**
	 * @var Config
	 */
	public $config;

	/**
	 * Keep current port driver
	 *
	 * @var Mellat|Saman|Sadad|Zarinpal|Payir|Parsian
	 */
	protected $port;

	/**
	 * Gateway constructor.
	 * @param null $config
	 * @param null $port
	 */
	public function __construct($config = null, $port = null)
	{
		$this->config = app('config');
		$this->request = app('request');

		if ($this->config->has('gateway.timezone'))
			date_default_timezone_set($this->config->get('gateway.timezone'));

		if (!is_null($port)) $this->make($port);
	}

	/**
	 * Get supported ports
	 *
	 * @return array
	 */
	public function getSupportedPorts()
	{
		return (array) Enum::getIPGs();
	}

	/**
	 * Call methods of current driver
	 *
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{

		// calling by this way ( Gateway::mellat()->.. , Gateway::parsian()->.. )
		if(in_array(strtoupper($name),$this->getSupportedPorts())){
			return $this->make($name);
		}

		return call_user_func_array([$this->port, $name], $arguments);
	}

	/**
	 * Gets query builder from you transactions table
	 * @return mixed
	 */
	function getTable()
	{
		return DB::table($this->config->get('gateway.table'));
	}

	/**
	 * Callback
	 *
	 * @return $this->port
	 *
	 * @throws InvalidRequestException
	 * @throws NotFoundTransactionException
	 * @throws PortNotFoundException
	 * @throws RetryException
	 */
	public function verify()
	{
		if (!$this->request->has('transaction_id') && !$this->request->has('iN'))
			throw new InvalidRequestException;
		if ($this->request->has('transaction_id')) {
			$id = $this->request->get('transaction_id');
		}else {
			$id = $this->request->get('iN');
		}

		$transaction = $this->getTable()->whereId($id)->first();

		if (!$transaction)
			throw new NotFoundTransactionException;

		if (in_array($transaction->status, [Enum::TRANSACTION_SUCCEED, Enum::TRANSACTION_FAILED]))
			throw new RetryException;

		$this->make($transaction->port);

		return $this->port->verify($transaction);
	}


	/**
	 * Port name => driver class.
	 *
	 * @return array<string, class-string<PortInterface>>
	 */
	protected function portMap(): array
	{
		return [
			Enum::MELLAT => Mellat::class,
			Enum::PARSIAN => Parsian::class,
			Enum::SAMAN => Saman::class,
			Enum::SAMANOLD => SamanOld::class,
			Enum::ZARINPAL => Zarinpal::class,
			Enum::SADAD => Sadad::class,
			Enum::ASANPARDAKHT => Asanpardakht::class,
			Enum::PAYIR => Payir::class,
			Enum::PASARGAD => Pasargad::class,
			Enum::NIKAN => Nikan::class,
			Enum::PAYPING => Payping::class,
			Enum::IRANKISH => Irankish::class,
			Enum::APSAN => Apsan::class,
			Enum::VANDAR => Vandar::class,
			Enum::SADAD_BNPL => Sadadbnpl::class,
			Enum::DIGIPAY => Digipay::class,
			Enum::ZIBAL => Zibal::class,
			Enum::MILIGOLD => Miligold::class,
			// Enum::PAYLINE => Payline::class,
			// Enum::JAHANPAY => JahanPay::class,
			// Enum::SETAREAVAL => Setareaval::class,
		];
	}

	/**
	 * Create new object from port class
	 *
	 * @param PortInterface|string $port port instance or name, such as "mellat"
	 * @throws PortNotFoundException
	 * @return $this
	 */
	function make($port)
    {
		[$name, $driver] = $this->resolvePort($port);

        $this->port = $driver;
        $this->port->setConfig($this->config);
        $this->port->setPortName($name);
        $this->port->boot();

        return $this;
    }

	/**
	 * @param PortInterface|string $port
	 * @return array{0: string, 1: PortInterface}
	 * @throws PortNotFoundException
	 */
	protected function resolvePort($port): array
	{
		$map = $this->portMap();

		if ($port instanceof PortInterface) {
			foreach ($map as $name => $class) {
				if ($port instanceof $class) {
					return [$name, $port];
				}
			}

			throw new PortNotFoundException;
		}

		$name = strtoupper((string) $port);
		if (!isset($map[$name])) {
			throw new PortNotFoundException;
		}

		$class = $map[$name];

		return [$name, new $class];
	}
}
