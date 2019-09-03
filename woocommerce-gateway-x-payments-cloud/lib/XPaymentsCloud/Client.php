<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * Copyright (c) 2011-present Qualiteam software Ltd. All rights reserved.
 * See https://www.x-cart.com/license-agreement.html for license details.
 */

namespace XPaymentsCloud;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Request.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Response.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'ApiException.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Signature.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . 'Payment.php';

class Client
{
    const SDK_VERSION = '0.1.0';

    private $account;
    private $secretKey;
    private $apiKey;

    /**
     * @param $token
     * @param $refId
     * @param $customerId
     * @param $cart
     * @param $returnUrl
     * @param $callbackUrl
     *
     * @param null $forceSaveCard (optional)
     * @param null $forceTransactionType (optional)
     * @param int $forceConfId (optional)
     *
     * @return Response
     * @throws ApiException
     */
    public function doPay($token, $refId, $customerId, $cart, $returnUrl, $callbackUrl, $forceSaveCard = null, $forceTransactionType = null, $forceConfId = 0)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'token'       => $token,
            'refId'       => $refId,
            'customerId'  => $customerId,
            'cart'        => $cart,
            'returnUrl'   => $returnUrl,
            'callbackUrl' => $callbackUrl,
        );

        if (!is_null($forceSaveCard)) {
            $params['forceSaveCard'] = ($forceSaveCard) ? 'Y' : 'N';
        }
        if (!is_null($forceTransactionType)) {
            $params['forceTransactionType'] = $forceTransactionType;
        }
        if ($forceConfId) {
            $params['confId'] = $forceConfId;
        }

        $response = $request->send(
            'pay',
            $params
        );

        if (empty($response->getPayment())) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * @param $xpid
     * @param int $amount
     * @return Response
     * @throws ApiException
     */
    public function doCapture($xpid, $amount = 0)
    {
        return $this->doAction('capture', $xpid, $amount);
    }

    /**
     * @param $xpid
     * @param int $amount
     * @return Response
     * @throws ApiException
     */
    public function doRefund($xpid, $amount = 0)
    {
        return $this->doAction('refund', $xpid, $amount);
    }

    /**
     * @param $xpid
     * @param int $amount
     * @return Response
     * @throws ApiException
     */
    public function doVoid($xpid, $amount = 0)
    {
        return $this->doAction('void', $xpid, $amount);
    }

    /**
     * @param $xpid
     * @return Response
     * @throws ApiException
     */
    public function doGetInfo($xpid)
    {
        return $this->doAction('get_info', $xpid);
    }

    /**
     * @param $xpid
     * @return Response
     * @throws ApiException
     */
    public function doContinue($xpid)
    {
        return $this->doAction('continue', $xpid);
    }

    /**
     * @param $xpid
     * @return Response
     * @throws ApiException
     */
    public function doAccept($xpid)
    {
        return $this->doAction('accept', $xpid);
    }

    /**
     * @param $xpid
     * @return Response
     * @throws ApiException
     */
    public function doDecline($xpid)
    {
        return $this->doAction('decline', $xpid);
    }

    /**
     * @param $action
     * @param $xpid
     * @param int $amount
     * @return Response
     * @throws ApiException
     */
    private function doAction($action, $xpid, $amount = 0)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'xpid' => $xpid,
        );

        if (0 < $amount) {
            $params['amount'] = $amount;
        }

        $response = $request->send(
            $action,
            $params
        );

        if (is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Get all customer's valid cards. Note: this doesn't include expired cards
     * and cards saved by switched off payment configuration
     *
     * @param string $customerId Public Customer ID
     * @param string $status Cards status 
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function doGetCustomerCards($customerId, $status = 'any')
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'customerId' => $customerId,
            'status'     => $status,
        );

        $response = $request->send(
            'get_cards',
            $params,
            'customer'
        );

        if (is_null($response->customer_cards) || is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Set default customer's card
     *
     * @param string $customerId Public Customer ID
     * @param string $cardId Card ID
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function doSetDefaultCustomerCard($customerId, $cardId)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'customerId' => $customerId,
            'cardId'     => $cardId,
        );

        $response = $request->send(
            'set_default_card',
            $params,
            'customer'
        );

        if (is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Delete customer card
     *
     * @param string $customerId Public Customer ID
     * @param string $cardId Card ID
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function doDeleteCustomerCard($customerId, $cardId)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'customerId' => $customerId,
            'cardId'     => $cardId,
        );

        $response = $request->send(
            'delete_card',
            $params,
            'customer'
        );

        if (is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * @param null $inputData
     * @param null $signature
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function parseCallback($inputData = null, $signature = null)
    {
        if (is_null($inputData)) {
            $inputData = file_get_contents('php://input');
        }
        if (is_null($signature) && !empty($_SERVER)) {
            $header = 'HTTP_' . strtoupper(str_replace('-', '_', Signature::HEADER));
            $signature = (array_key_exists($header, $_SERVER)) ? $_SERVER[$header] : '';
        }

        $response = new Response('callback', $inputData, null, $signature, $this->secretKey);

        if (empty($response->getPayment())) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Get X-Payments admin URL
     *
     * @return string
     */
    public function getAdminUrl()
    {
        return sprintf(
            'https://%s.%s/admin.php',
            $this->account,
            Request::XP_DOMAIN
        );
    }

    /**
     * Client constructor.
     * @param string $account
     * @param string $apiKey
     * @param string $secretKey
     */
    public function __construct($account, $apiKey, $secretKey)
    {
        $this->account = $account;
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

}
