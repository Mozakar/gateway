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
use Mozakar\Gateway\SadadBnpl\SadadBnpl;
use Mozakar\Gateway\Asanpardakht\Asanpardakht;
use Mozakar\Gateway\Exceptions\RetryException;
use Mozakar\Gateway\Exceptions\PortNotFoundException;
use Mozakar\Gateway\Exceptions\InvalidRequestException;
use Mozakar\Gateway\Exceptions\NotFoundTransactionException;
use Mozakar\Gateway\Miligold\Miligold;
// use Mozakar\Gateway\Setareaval\Setareaval;
use Mozakar\Gateway\Zibal\Zibal;

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
	 * Create new object from port class
	 *
	 * @param int $port
	 * @throws PortNotFoundException
	 */
	function make($port)
    {
       $portsMap = [
			Mellat::class        => Enum::MELLAT,
			Parsian::class       => Enum::PARSIAN,
			Saman::class         => Enum::SAMAN,
			SamanOld::class      => Enum::SAMANOLD,
			Zarinpal::class      => Enum::ZARINPAL,
			Sadad::class         => Enum::SADAD,
			Asanpardakht::class  => Enum::ASANPARDAKHT,
			Payir::class         => Enum::PAYIR,
			Pasargad::class      => Enum::PASARGAD,
			Nikan::class         => Enum::NIKAN,
			Payping::class       => Enum::PAYPING,
			Irankish::class      => Enum::IRANKISH,
			Apsan::class         => Enum::APSAN,
			Vandar::class        => Enum::VANDAR,
			SadadBnpl::class     => Enum::SADAD_BNPL,
			Digipay::class       => Enum::DIGIPAY,
			Zibal::class         => Enum::ZIBAL,
			// Setareaval::class    => Enum::SETAREAVAL,
			Miligold::class	=> Enum::MILIGOLD,
		];

		foreach ($portsMap as $class => $enumName) {
			if ($port instanceof $class) {
				$name = $enumName;
				break;
			}
		}

		if (!isset($name)) {
			if (in_array(strtoupper($port), $this->getSupportedPorts())) {
				$port = ucfirst(strtolower($port));
				$name = strtoupper($port);
				$class = __NAMESPACE__ . '\\' . $port . '\\' . $port;
				$port = new $class;
			} else {
				throw new PortNotFoundException;
			}
		}

            
        $this->port = $port;
        $this->port->setConfig($this->config); // injects config
        $this->port->setPortName($name); // injects config
        $this->port->boot();

        return $this;
    }
}
