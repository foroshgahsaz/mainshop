<?php

namespace App\Services\Payment;

use SoapClient;
use SoapFault;

/**
 * SOAP client for Parsian Electronic Commerce (PEC / Shaparak IPG).
 *
 * @see docs/PHP.Samples.zip — PecRequestClass.php
 */
class PecIpgClient
{
    public const SALE_WSDL = 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?WSDL';

    public const CONFIRM_WSDL = 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?WSDL';

    public const REVERSAL_WSDL = 'https://pec.shaparak.ir/NewIPGServices/Reverse/ReversalService.asmx?WSDL';

    public const PAYMENT_PAGE = 'https://pec.shaparak.ir/NewIPG/';

    public function __construct()
    {
        if (ini_get('soap.wsdl_cache_enabled') !== '0') {
            ini_set('soap.wsdl_cache_enabled', '0');
        }
    }

    /**
     * @return object{SalePaymentRequestResult: object{Token?: string, Status?: int|string, Message?: string}}
     *
     * @throws SoapFault
     */
    public function salePaymentRequest(
        string $loginAccount,
        int $orderId,
        int $amount,
        string $callbackUrl,
        string $additionalData = '',
        string $originator = '',
    ): object {
        $client = new SoapClient(self::SALE_WSDL);

        return $client->SalePaymentRequest([
            'requestData' => [
                'LoginAccount' => $loginAccount,
                'Amount' => $amount,
                'OrderId' => $orderId,
                'CallBackUrl' => $callbackUrl,
                'AdditionalData' => $additionalData,
                'Originator' => $originator,
            ],
        ]);
    }

    /**
     * @return object{ConfirmPaymentResult: object{Status?: int|string, RRN?: string|int, CardNumberMasked?: string}}
     *
     * @throws SoapFault
     */
    public function confirmPayment(string $loginAccount, string $token): object
    {
        $client = new SoapClient(self::CONFIRM_WSDL);

        return $client->ConfirmPayment([
            'requestData' => [
                'LoginAccount' => $loginAccount,
                'Token' => $token,
            ],
        ]);
    }

    /**
     * @return object{ReversalRequestResult: object{Status?: int|string, Message?: string}}
     *
     * @throws SoapFault
     */
    public function reversalRequest(string $loginAccount, string $token): object
    {
        $client = new SoapClient(self::REVERSAL_WSDL);

        return $client->ReversalRequest([
            'requestData' => [
                'LoginAccount' => $loginAccount,
                'Token' => $token,
            ],
        ]);
    }
}
