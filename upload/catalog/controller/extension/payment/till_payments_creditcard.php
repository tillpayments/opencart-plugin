<?php

include_once(DIR_SYSTEM . 'library/till-payments/autoload.php');

use TillPayments\Client\Callback\Result;
use TillPayments\Client\Data\Customer;
use TillPayments\Client\Transaction\Debit;
use TillPayments\Client\Transaction\Preauthorize;
use TillPayments\Client\Transaction\Result as TransactionResult;
use TillPayments\TillPaymentsGateway;
use TillPayments\TillPaymentsPlugin;

final class ControllerExtensionPaymentTillPaymentsCreditCard extends Controller
{
    use TillPaymentsGateway;

    /**
     * Default OpenCart order status
     * TODO: read those from db by language 1 (en)
     */
    const PENDING = 1;
    const PROCESSING = 2;
    const SHIPPED = 3;
    const COMPLETE = 5;
    const CANCELED = 7;
    const DENIED = 8;
    const CANCELED_REVERSAL = 9;
    const FAILED = 10;
    const REFUNDED = 11;
    const REVERSED = 12;
    const CHARGEBACK = 13;
    const EXPIRED = 14;
    const PROCESSED = 15;
    const VOIDED = 16;

    private $type = 'creditcard';

    private $prefix = 'payment_till_payments_';

    /**
     * @var array
     */
    protected $order;

    /**
     * @var array|null
     */
    protected $customer;

    public function index($data = null)
    {
        $data['type'] = $this->type;
        $data['action'] = $this->url->link('extension/payment/till_payments_' . $this->type . '/confirm', '', true);

        $this->load->language('extension/payment/till_payments');
        $data['loading_text'] = $this->language->get('loading_text');
        $data['button_confirm'] = $this->language->get('button_confirm');

        $creditCards = $this->getCreditCardsPublic();
        $data['credit_cards'] = $creditCards;
        $data['credit_cards_json'] = json_encode($creditCards);

        $year = date('Y');
        $data['months'] = range(1, 12);
        $data['years'] = range($year, $year + 50);

        $orderId = $this->session->data['order_id'];
        $order = $this->model_checkout_order->getOrder($orderId);
        $data['email'] = $order['email'];

        $apiHost = rtrim($this->getConfig('api_host'), '/') . '/';
        $data['api_host'] = $apiHost;

        return $this->load->view('extension/payment/till_payments', $data);
    }

    public function confirm()
    {
        $this->load->language('extension/payment/till_payments');

        /**
         * order
         */
        $this->load->model('checkout/order');

        $orderId = $this->session->data['order_id'];
        $this->model_checkout_order->addOrderHistory($orderId, self::PENDING);

        $this->order = $this->model_checkout_order->getOrder($orderId);

        $this->load->model('account/customer');

        /**
         * customer
         */
        $this->customer = $this->model_account_customer->getCustomer($this->order['customer_id']);

        /**
         * switch card types
         */
        $cardType = $this->request->post['card_type'];

        try {
            /**
             * gateway client
             */
            $client = $this->client($cardType);

            /**
             * gateway customer
             */
            $customer = new Customer();
            $customer
                ->setBillingAddress1($this->order['payment_address_1'])
                ->setBillingAddress2($this->order['payment_address_2'])
                ->setBillingCity($this->order['payment_city'])
                ->setBillingCountry($this->order['payment_iso_code_2'])
                ->setBillingPhone($this->order['telephone'])
                ->setBillingPostcode($this->order['payment_postcode'])
                ->setBillingState($this->order['payment_zone'])
                ->setCompany($this->order['payment_company'])
                ->setEmail($this->order['email'])
                ->setFirstName($this->order['payment_firstname'])
                ->setIpAddress($this->order['ip'])
                ->setLastName($this->order['payment_lastname']);

            /**
             * add shipping data for non-digital goods
             */
            if (!empty($this->order['shipping_iso_code_2'])) {
                $customer
                    ->setShippingAddress1($this->order['shipping_address_1'])
                    ->setShippingAddress2($this->order['shipping_address_2'])
                    ->setShippingCity($this->order['shipping_city'])
                    ->setShippingCompany($this->order['shipping_company'])
                    ->setShippingCountry($this->order['shipping_iso_code_2'])
                    ->setShippingFirstName($this->order['shipping_firstname'])
                    ->setShippingLastName($this->order['shipping_lastname'])
                    ->setShippingPhone($this->order['telephone'])
                    ->setShippingPostcode($this->order['shipping_postcode'])
                    ->setShippingState($this->order['shipping_zone']);
            }

            /**
             * transaction
             */
            $transactionRequest = $this->getConfig('cc_method_' . $cardType);
            $transaction = null;
            switch ($transactionRequest) {
                case TillPaymentsPlugin::METHOD_PREAUTHORIZE:
                    $transaction = new Preauthorize();
                    break;
                case TillPaymentsPlugin::METHOD_DEBIT:
                default:
                    $transaction = new Debit();
                    break;
            }

            $transaction->setTransactionId($this->encodeOrderId($this->session->data['order_id']))
                ->setAmount(number_format(round($this->order['total'], 2), 2, '.', ''))
                ->setCurrency($this->order['currency_code'])
                ->setCustomer($customer)
                ->setExtraData($this->extraData3DS())
                ->setCallbackUrl(str_replace('&amp;', '&', $this->url->link('extension/payment/till_payments_' . $this->type . '/callback', ['orderId' => $orderId, 'cardType' => $cardType])))
                ->setCancelUrl(str_replace('&amp;', '&', $this->url->link('extension/payment/till_payments_' . $this->type . '/response', ['orderId' => $orderId, 'cancelled' => 1])))
                ->setErrorUrl(str_replace('&amp;', '&', $this->url->link('extension/payment/till_payments_' . $this->type . '/response', ['orderId' => $orderId, 'failed' => 1])))
                ->setSuccessUrl(str_replace('&amp;', '&', $this->url->link('extension/payment/till_payments_' . $this->type . '/response', ['orderId' => $orderId, 'success' => 1])));

            /**
             * token
             */
            if ($this->getConfig('cc_seamless_' . $cardType)) {
                $token = (string)$this->request->post['token'];
                if (empty($token)) {
                    die('empty token');
                }
                $transaction->setTransactionToken($token);
            }

            /**
             * transaction
             */
            switch ($transactionRequest) {
                case TillPaymentsPlugin::METHOD_PREAUTHORIZE:
                    $paymentResult = $client->preauthorize($transaction);
                    break;
                case TillPaymentsPlugin::METHOD_DEBIT:
                default:
                    $paymentResult = $client->debit($transaction);
                    break;
            }
        } catch (\Throwable $e) {
            $this->processFailure($this->order);
        }

        if ($paymentResult->hasErrors()) {
            $this->processFailure($this->order);
        }

        if ($paymentResult->isSuccess()) {
            // $gatewayReferenceId = $paymentResult->getReferenceId();
            switch ($paymentResult->getReturnType()) {
                case TransactionResult::RETURN_TYPE_ERROR:
                    $this->processFailure($this->order);
                    break;
                case TransactionResult::RETURN_TYPE_REDIRECT:
                    /**
                     * hosted payment page or seamless+3DS
                     */
                    $this->response->redirect($paymentResult->getRedirectUrl());
                    break;
                case TransactionResult::RETURN_TYPE_PENDING:
                    /**
                     * payment is pending, wait for callback to complete
                     */
                    break;
                case TransactionResult::RETURN_TYPE_FINISHED:
                    /**
                     * seamless will finish here ONLY FOR NON-3DS SEAMLESS
                     */
                    break;
            }
            $this->response->redirect($this->url->link('checkout/success'));
        }

        /**
         * something went wrong
         */
        $this->processFailure($this->order);
    }

    private function processFailure($order)
    {
        if ($order['order_status_id'] == self::PENDING) {
            $this->model_checkout_order->addOrderHistory($order['order_id'], self::FAILED);
            $this->session->data['error'] = $this->language->get('order_error');
            $this->response->redirect($this->url->link('checkout/checkout'));
        }
    }

    /**
     * @param string $cardType
     * @throws \TillPayments\Client\Exception\InvalidValueException
     * @return \TillPayments\Client\Client
     */
    private function client($cardType)
    {
        TillPayments\Client\Client::setApiUrl(rtrim($this->getConfig('api_host'), '/') . '/');
        return new TillPayments\Client\Client(
            $this->getConfig('cc_api_user_' . $cardType),
            htmlspecialchars_decode($this->getConfig('cc_api_password_' . $cardType)),
            $this->getConfig('cc_api_key_' . $cardType),
            $this->getConfig('cc_api_secret_' . $cardType)
        );
    }

    private function encodeOrderId($orderId)
    {
        return $orderId . '-' . date('YmdHis') . substr(sha1(uniqid()), 0, 10);
    }

    private function decodeOrderId($orderId)
    {
        if (strpos($orderId, '-') === false) {
            return $orderId;
        }

        $orderIdParts = explode('-', $orderId);

        if(count($orderIdParts) === 2) {
            $orderId = $orderIdParts[0];
        }

        /**
         * void/capture will prefix the transaction id
         */
        if(count($orderIdParts) === 3) {
            $orderId = $orderIdParts[1];
        }

        return $orderId;
    }

    public function response()
    {
        $this->load->model('checkout/order');
        $this->load->language('extension/payment/till_payments');

        $orderId = isset($_REQUEST['orderId']) ? (int)$_REQUEST['orderId'] : null;
        $order = $this->model_checkout_order->getOrder($orderId);

        if (!$order) {
            $this->session->data['error'] = $this->language->get('order_cancelled');
            $this->response->redirect($this->url->link('checkout/checkout'));
        }

        $cancelled = !empty($_REQUEST['cancelled']);
        if ($cancelled) {
            $this->session->data['error'] = $this->language->get('order_cancelled');
            $this->response->redirect($this->url->link('checkout/checkout'));
            return;
        }

        $success = !empty($_REQUEST['success']);
        if ($success) {
            $this->response->redirect($this->url->link('checkout/success'));
            return;
        }

        $this->session->data['error'] = $this->language->get('order_error');
        $this->response->redirect($this->url->link('checkout/checkout'));
    }

    public function callback()
    {
        $this->load->model('checkout/order');
        $this->load->language('extension/payment/till_payments');

        $cardType = $_REQUEST['cardType'];

        $client = $this->client($cardType);

        if (!$client->validateCallbackWithGlobals()) {
            if (!headers_sent()) {
                http_response_code(400);
            }
            die("OK");
        }

        $callbackResult = $client->readCallback(file_get_contents('php://input'));

        $orderId = $this->decodeOrderId($callbackResult->getTransactionId());

        /**
         * Map result's transaction type to internal order status
         */
        $orderStatus = null;
        $orderHistoryComments = [];
        $notifyCustomer = false;
        switch ($callbackResult->getTransactionType()) {
            case Result::TYPE_DEBIT:
            case Result::TYPE_CAPTURE:
                $orderStatus = self::PROCESSED;
                break;
            case Result::TYPE_VOID:
                $orderStatus = self::VOIDED;
                break;
            case Result::TYPE_PREAUTHORIZE:
                $orderStatus = self::PROCESSING;
                /**
                 * Add 'Awaiting capture/void' to order history comment
                 */
                $orderHistoryComments[] = $this->language->get('order_on_hold');
                break;
            // case Result::TYPE_CHARGEBACK:
            //     $orderStatus = self::CHARGEBACK;
            //     break;
            // case Result::TYPE_CHARGEBACK_REVERSAL:
            //     $orderStatus = self::REVERSED;
            //     break;
            // case Result::TYPE_REFUND:
            //     $orderStatus = self::REFUNDED;
            //     break;
        }

        /**
         * Override status if it's an error result, reset comments
         */
        if ($callbackResult->getResult() == Result::RESULT_ERROR) {
            $orderStatus = self::FAILED;
            $orderHistoryComments = [];
        }

        /**
         * Add additional metadata to order history comment
         */
        $orderHistoryComments[] = 'Callback Result: ' . $callbackResult->getResult();
        $orderHistoryComments[] = 'TX Type: ' . $callbackResult->getTransactionType();
        $orderHistoryComments[] = 'CC Type: ' . $callbackResult->getReturnData()->toArray()['type'] ?? 'unknown';
        $orderHistoryComments[] = 'CC Digits: ' . $callbackResult->getReturnData()->toArray()['lastFourDigits'] ?? 'unknown';

        $this->updateOrderStatus($orderId, $orderStatus, $orderHistoryComments, $notifyCustomer);

        die("OK");
    }

    private function updateOrderStatus($orderId, $orderStatus, $orderHistoryComments = [], $notifyCustomer = false)
    {
        $this->model_checkout_order->addOrderHistory($orderId, $orderStatus, implode("\n", $orderHistoryComments), $notifyCustomer);
    }

    /**
     * @throws Exception
     * @return array
     */
    private function extraData3DS()
    {
        $extraData = [
            /**
             * Browser 3ds data injected by payment.js
             */
            // 3ds:browserAcceptHeader
            // 3ds:browserIpAddress
            // 3ds:browserJavaEnabled
            // 3ds:browserLanguage
            // 3ds:browserColorDepth
            // 3ds:browserScreenHeight
            // 3ds:browserScreenWidth
            // 3ds:browserTimezone
            // 3ds:browserUserAgent

            /**
             * force 3ds flow
             */
            // '3dsecure' => 'mandatory',

            /**
             * Additional 3ds 2.0 data
             */
            '3ds:addCardAttemptsDay' => $this->addCardAttemptsDay(),
            '3ds:authenticationIndicator' => $this->authenticationIndicator(),
            '3ds:billingAddressLine3' => $this->billingAddressLine3(),
            '3ds:billingShippingAddressMatch' => $this->billingShippingAddressMatch(),
            '3ds:browserChallengeWindowSize' => $this->browserChallengeWindowSize(),
            '3ds:cardholderAccountAgeIndicator' => $this->cardholderAccountAgeIndicator(),
            '3ds:cardHolderAccountChangeIndicator' => $this->cardHolderAccountChangeIndicator(),
            '3ds:cardholderAccountDate' => $this->cardholderAccountDate(),
            '3ds:cardholderAccountLastChange' => $this->cardholderAccountLastChange(),
            '3ds:cardholderAccountLastPasswordChange' => $this->cardholderAccountLastPasswordChange(),
            '3ds:cardholderAccountPasswordChangeIndicator' => $this->cardholderAccountPasswordChangeIndicator(),
            '3ds:cardholderAccountType' => $this->cardholderAccountType(),
            '3ds:cardHolderAuthenticationData' => $this->cardHolderAuthenticationData(),
            '3ds:cardholderAuthenticationDateTime' => $this->cardholderAuthenticationDateTime(),
            '3ds:cardholderAuthenticationMethod' => $this->cardholderAuthenticationMethod(),
            '3ds:challengeIndicator' => $this->challengeIndicator(),
            '3ds:channel' => $this->channel(),
            '3ds:deliveryEmailAddress' => $this->deliveryEmailAddress(),
            '3ds:deliveryTimeframe' => $this->deliveryTimeframe(),
            '3ds:giftCardAmount' => $this->giftCardAmount(),
            '3ds:giftCardCount' => $this->giftCardCount(),
            '3ds:giftCardCurrency' => $this->giftCardCurrency(),
            '3ds:homePhoneCountryPrefix' => $this->homePhoneCountryPrefix(),
            '3ds:homePhoneNumber' => $this->homePhoneNumber(),
            '3ds:mobilePhoneCountryPrefix' => $this->mobilePhoneCountryPrefix(),
            '3ds:mobilePhoneNumber' => $this->mobilePhoneNumber(),
            '3ds:paymentAccountAgeDate' => $this->paymentAccountAgeDate(),
            '3ds:paymentAccountAgeIndicator' => $this->paymentAccountAgeIndicator(),
            '3ds:preOrderDate' => $this->preOrderDate(),
            '3ds:preOrderPurchaseIndicator' => $this->preOrderPurchaseIndicator(),
            '3ds:priorAuthenticationData' => $this->priorAuthenticationData(),
            '3ds:priorAuthenticationDateTime' => $this->priorAuthenticationDateTime(),
            '3ds:priorAuthenticationMethod' => $this->priorAuthenticationMethod(),
            '3ds:priorReference' => $this->priorReference(),
            '3ds:purchaseCountSixMonths' => $this->purchaseCountSixMonths(),
            '3ds:purchaseDate' => $this->purchaseDate(),
            '3ds:purchaseInstalData' => $this->purchaseInstalData(),
            '3ds:recurringExpiry' => $this->recurringExpiry(),
            '3ds:recurringFrequency' => $this->recurringFrequency(),
            '3ds:reorderItemsIndicator' => $this->reorderItemsIndicator(),
            '3ds:shipIndicator' => $this->shipIndicator(),
            '3ds:shippingAddressFirstUsage' => $this->shippingAddressFirstUsage(),
            '3ds:shippingAddressLine3' => $this->shippingAddressLine3(),
            '3ds:shippingAddressUsageIndicator' => $this->shippingAddressUsageIndicator(),
            '3ds:shippingNameEqualIndicator' => $this->shippingNameEqualIndicator(),
            '3ds:suspiciousAccountActivityIndicator' => $this->suspiciousAccountActivityIndicator(),
            '3ds:transactionActivityDay' => $this->transactionActivityDay(),
            '3ds:transactionActivityYear' => $this->transactionActivityYear(),
            '3ds:transType' => $this->transType(),
            '3ds:workPhoneCountryPrefix' => $this->workPhoneCountryPrefix(),
            '3ds:workPhoneNumber' => $this->workPhoneNumber(),
        ];

        return array_filter($extraData, function ($data) {
            return $data !== null;
        });
    }

    /**
     * 3ds:addCardAttemptsDay
     * Number of Add Card attempts in the last 24 hours.
     *
     * @return int|null
     */
    private function addCardAttemptsDay()
    {
        return null;
    }

    /**
     * 3ds:authenticationIndicator
     * Indicates the type of Authentication request. This data element provides additional information to the ACS to determine the best approach for handling an authentication request.
     * 01 -> Payment transaction
     * 02 -> Recurring transaction
     * 03 -> Installment transaction
     * 04 -> Add card
     * 05 -> Maintain card
     * 06 -> Cardholder verification as part of EMV token ID&V
     *
     * @return string|null
     */
    private function authenticationIndicator()
    {
        return null;
    }

    /**
     * 3ds:billingAddressLine3
     * Line 3 of customer's billing address
     *
     * @return string|null
     */
    private function billingAddressLine3()
    {
        return null;
    }

    /**
     * 3ds:billingShippingAddressMatch
     * Indicates whether the Cardholder Shipping Address and Cardholder Billing Address are the same.
     * Y -> Shipping Address matches Billing Address
     * N -> Shipping Address does not match Billing Address
     *
     * @return string|null
     */
    private function billingShippingAddressMatch()
    {
        return null;
    }

    /**
     * 3ds:browserChallengeWindowSize
     * Dimensions of the challenge window that has been displayed to the Cardholder. The ACS shall reply with content that is formatted to appropriately render in this window to provide the best possible user experience.
     * 01 -> 250 x 400
     * 02 -> 390 x 400
     * 03 -> 500 x 600
     * 04 -> 600 x 400
     * 05 -> Full screen
     *
     * @return string|null
     */
    private function browserChallengeWindowSize()
    {
        return '05';
    }

    /**
     * 3ds:cardholderAccountAgeIndicator
     * Length of time that the cardholder has had the account with the 3DS Requestor.
     * 01 -> No account (guest check-out)
     * 02 -> During this transaction
     * 03 -> Less than 30 days
     * 04 -> 30 - 60 days
     * 05 -> More than 60 days
     *
     * @return string|null
     */
    private function cardholderAccountAgeIndicator()
    {
        return null;
    }

    /**
     * 3ds:cardHolderAccountChangeIndicator
     * Length of time since the cardholder’s account information with the 3DS Requestor waslast changed. Includes Billing or Shipping address, new payment account, or new user(s) added.
     * 01 -> Changed during this transaction
     * 02 -> Less than 30 days
     * 03 -> 30 - 60 days
     * 04 -> More than 60 days
     *
     * @return string|null
     */
    private function cardHolderAccountChangeIndicator()
    {
        return null;
    }

    /**
     * Date that the cardholder opened the account with the 3DS Requestor. Format: YYYY-MM-DD
     * Example: 2019-05-12
     *
     * @throws Exception
     * @return string|null
     */
    private function cardholderAccountDate()
    {
        if (!$this->customer) {
            return null;
        }

        return !empty($this->customer['date_added']) ? (new DateTime($this->customer['date_added']))->format('Y-m-d') : null;
    }

    /**
     * 3ds:cardholderAccountLastChange
     * Date that the cardholder’s account with the 3DS Requestor was last changed. Including Billing or Shipping address, new payment account, or new user(s) added. Format: YYYY-MM-DD
     * Example: 2019-05-12
     *
     * @throws Exception
     * @return string|null
     */
    private function cardholderAccountLastChange()
    {
        return null;
    }

    /**
     * 3ds:cardholderAccountLastPasswordChange
     * Date that cardholder’s account with the 3DS Requestor had a password change or account reset. Format: YYYY-MM-DD
     * Example: 2019-05-12
     *
     * @return string|null
     */
    private function cardholderAccountLastPasswordChange()
    {
        return null;
    }

    /**
     * 3ds:cardholderAccountPasswordChangeIndicator
     * Length of time since the cardholder’s account with the 3DS Requestor had a password change or account reset.
     * 01 -> No change
     * 02 -> Changed during this transaction
     * 03 -> Less than 30 days
     * 04 -> 30 - 60 days
     * 05 -> More than 60 days
     *
     * @return string|null
     */
    private function cardholderAccountPasswordChangeIndicator()
    {
        return null;
    }

    /**
     * 3ds:cardholderAccountType
     * Indicates the type of account. For example, for a multi-account card product.
     * 01 -> Not applicable
     * 02 -> Credit
     * 03 -> Debit
     * 80 -> JCB specific value for Prepaid
     *
     * @return string|null
     */
    private function cardholderAccountType()
    {
        return null;
    }

    /**
     * 3ds:cardHolderAuthenticationData
     * Data that documents and supports a specific authentication process. In the current version of the specification, this data element is not defined in detail, however the intention is that for each 3DS Requestor Authentication Method, this field carry data that the ACS can use to verify the authentication process.
     *
     * @return string|null
     */
    private function cardHolderAuthenticationData()
    {
        return null;
    }

    /**
     * 3ds:cardholderAuthenticationDateTime
     * Date and time in UTC of the cardholder authentication. Format: YYYY-MM-DD HH:mm
     * Example: 2019-05-12 18:34
     *
     * @return string|null
     */
    private function cardholderAuthenticationDateTime()
    {
        return null;
    }

    /**
     * 3ds:cardholderAuthenticationMethod
     * Mechanism used by the Cardholder to authenticate to the 3DS Requestor.
     * 01 -> No 3DS Requestor authentication occurred (i.e. cardholder "logged in" as guest)
     * 02 -> Login to the cardholder account at the 3DS Requestor system using 3DS Requestor's own credentials
     * 03 -> Login to the cardholder account at the 3DS Requestor system using federated ID
     * 04 -> Login to the cardholder account at the 3DS Requestor system using issuer credentials
     * 05 -> Login to the cardholder account at the 3DS Requestor system using third-party authentication
     * 06 -> Login to the cardholder account at the 3DS Requestor system using FIDO Authenticator
     *
     * @return string|null
     */
    private function cardholderAuthenticationMethod()
    {
        return null;
    }

    /**
     * 3ds:challengeIndicator
     * Indicates whether a challenge is requested for this transaction. For example: For 01-PA, a 3DS Requestor may have concerns about the transaction, and request a challenge.
     * 01 -> No preference
     * 02 -> No challenge requested
     * 03 -> Challenge requested: 3DS Requestor Preference
     * 04 -> Challenge requested: Mandate
     *
     * @return string|null
     */
    private function challengeIndicator()
    {
        return null;
    }

    /**
     * 3ds:channel
     * Indicates the type of channel interface being used to initiate the transaction
     * 01 -> App-based
     * 02 -> Browser
     * 03 -> 3DS Requestor Initiated
     *
     * @return string|null
     */
    private function channel()
    {
        return null;
    }

    /**
     * 3ds:deliveryEmailAddress
     * For electronic delivery, the email address to which the merchandise was delivered.
     *
     * @return string|null
     */
    private function deliveryEmailAddress()
    {
        return null;
    }

    /**
     * 3ds:deliveryTimeframe
     * Indicates the merchandise delivery timeframe.
     * 01 -> Electronic Delivery
     * 02 -> Same day shipping
     * 03 -> Overnight shipping
     * 04 -> Two-day or more shipping
     *
     * @return string|null
     */
    private function deliveryTimeframe()
    {
        return null;
    }

    /**
     * 3ds:giftCardAmount
     * For prepaid or gift card purchase, the purchase amount total of prepaid or gift card(s) in major units (for example, USD 123.45 is 123).
     *
     * @return string|null
     */
    private function giftCardAmount()
    {
        return null;
    }

    /**
     * 3ds:giftCardCount
     * For prepaid or gift card purchase, total count of individual prepaid or gift cards/codes purchased. Field is limited to 2 characters.
     *
     * @return string|null
     */
    private function giftCardCount()
    {
        return null;
    }

    /**
     * 3ds:giftCardCurrency
     * For prepaid or gift card purchase, the currency code of the card
     *
     * @return string|null
     */
    private function giftCardCurrency()
    {
        return null;
    }

    /**
     * 3ds:homePhoneCountryPrefix
     * Country Code of the home phone, limited to 1-3 characters
     *
     * @return string|null
     */
    private function homePhoneCountryPrefix()
    {
        return null;
    }

    /**
     * 3ds:homePhoneNumber
     * subscriber section of the number, limited to maximum 15 characters.
     *
     * @return string|null
     */
    private function homePhoneNumber()
    {
        return null;
    }

    /**
     * 3ds:mobilePhoneCountryPrefix
     * Country Code of the mobile phone, limited to 1-3 characters
     *
     * @return string|null
     */
    private function mobilePhoneCountryPrefix()
    {
        return null;
    }

    /**
     * 3ds:mobilePhoneNumber
     * subscriber section of the number, limited to maximum 15 characters.
     *
     * @return string|null
     */
    private function mobilePhoneNumber()
    {
        return null;
    }

    /**
     * 3ds:paymentAccountAgeDate
     * Date that the payment account was enrolled in the cardholder’s account with the 3DS Requestor. Format: YYYY-MM-DD
     * Example: 2019-05-12
     *
     * @return string|null
     */
    private function paymentAccountAgeDate()
    {
        return null;
    }

    /**
     * 3ds:paymentAccountAgeIndicator
     * Indicates the length of time that the payment account was enrolled in the cardholder’s account with the 3DS Requestor.
     * 01 -> No account (guest check-out)
     * 02 -> During this transaction
     * 03 -> Less than 30 days
     * 04 -> 30 - 60 days
     * 05 -> More than 60 days
     *
     * @return string|null
     */
    private function paymentAccountAgeIndicator()
    {
        return null;
    }

    /**
     * 3ds:preOrderDate
     * For a pre-ordered purchase, the expected date that the merchandise will be available.
     * Format: YYYY-MM-DD
     *
     * @return string|null
     */
    private function preOrderDate()
    {
        return null;
    }

    /**
     * 3ds:preOrderPurchaseIndicator
     * Indicates whether Cardholder is placing an order for merchandise with a future availability or release date.
     * 01 -> Merchandise available
     * 02 -> Future availability
     *
     * @return string|null
     */
    private function preOrderPurchaseIndicator()
    {
        return null;
    }

    /**
     * 3ds:priorAuthenticationData
     * Data that documents and supports a specfic authentication porcess. In the current version of the specification this data element is not defined in detail, however the intention is that for each 3DS Requestor Authentication Method, this field carry data that the ACS can use to verify the authentication process. In future versionsof the application, these details are expected to be included. Field is limited to maximum 2048 characters.
     *
     * @return string|null
     */
    private function priorAuthenticationData()
    {
        return null;
    }

    /**
     * 3ds:priorAuthenticationDateTime
     * Date and time in UTC of the prior authentication. Format: YYYY-MM-DD HH:mm
     * Example: 2019-05-12 18:34
     *
     * @return string|null
     */
    private function priorAuthenticationDateTime()
    {
        return null;
    }

    /**
     * 3ds:priorAuthenticationMethod
     * Mechanism used by the Cardholder to previously authenticate to the 3DS Requestor.
     * 01 -> Frictionless authentication occurred by ACS
     * 02 -> Cardholder challenge occurred by ACS
     * 03 -> AVS verified
     * 04 -> Other issuer methods
     *
     * @return string|null
     */
    private function priorAuthenticationMethod()
    {
        return null;
    }

    /**
     * 3ds:priorReference
     * This data element provides additional information to the ACS to determine the best approach for handling a request. The field is limited to 36 characters containing ACS Transaction ID for a prior authenticated transaction (for example, the first recurring transaction that was authenticated with the cardholder).
     *
     * @return string|null
     */
    private function priorReference()
    {
        return null;
    }

    /**
     * 3ds:purchaseCountSixMonths
     * Number of purchases with this cardholder account during the previous six months.
     *
     * @return int
     */
    private function purchaseCountSixMonths()
    {
        return null;
    }

    /**
     * 3ds:purchaseDate
     * Date and time of the purchase, expressed in UTC. Format: YYYY-MM-DD
     **Note: if omitted we put in today's date
     *
     * @return string|null
     */
    private function purchaseDate()
    {
        return null;
    }

    /**
     * 3ds:purchaseInstalData
     * Indicates the maximum number of authorisations permitted for instalment payments. The field is limited to maximum 3 characters and value shall be greater than 1. The fields is required if the Merchant and Cardholder have agreed to installment payments, i.e. if 3DS Requestor Authentication Indicator = 03. Omitted if not an installment payment authentication.
     *
     * @return string|null
     */
    private function purchaseInstalData()
    {
        return null;
    }

    /**
     * 3ds:recurringExpiry
     * Date after which no further authorizations shall be performed. This field is required for 01-PA and for 02-NPA, if 3DS Requestor Authentication Indicator = 02 or 03.
     * Format: YYYY-MM-DD
     *
     * @return string|null
     */
    private function recurringExpiry()
    {
        return null;
    }

    /**
     * 3ds:recurringFrequency
     * Indicates the minimum number of days between authorizations. The field is limited to maximum 4 characters. This field is required if 3DS Requestor Authentication Indicator = 02 or 03.
     *
     * @return string|null
     */
    private function recurringFrequency()
    {
        return null;
    }

    /**
     * 3ds:reorderItemsIndicator
     * Indicates whether the cardholder is reoreding previously purchased merchandise.
     * 01 -> First time ordered
     * 02 -> Reordered
     *
     * @return string|null
     */
    private function reorderItemsIndicator()
    {
        return null;
    }

    /**
     * 3ds:shipIndicator
     * Indicates shipping method chosen for the transaction. Merchants must choose the Shipping Indicator code that most accurately describes the cardholder's specific transaction. If one or more items are included in the sale, use the Shipping Indicator code for the physical goods, or if all digital goods, use the code that describes the most expensive item.
     * 01 -> Ship to cardholder's billing address
     * 02 -> Ship to another verified address on file with merchant
     * 03 -> Ship to address that is different than the cardholder's billing address
     * 04 -> "Ship to Store" / Pick-up at local store (Store address shall be populated in shipping address fields)
     * 05 -> Digital goods (includes online services, electronic gift cards and redemption codes)
     * 06 -> Travel and Event tickets, not shipped
     * 07 -> Other (for example, Gaming, digital services not shipped, emedia subscriptions, etc.)
     *
     * @return string|null
     */
    private function shipIndicator()
    {
        return null;
    }

    /**
     * 3ds:shippingAddressFirstUsage
     * Date when the shipping address used for this transaction was first used with the 3DS Requestor. Format: YYYY-MM-DD
     * Example: 2019-05-12
     *
     * @throws Exception
     * @return string|null
     */
    private function shippingAddressFirstUsage()
    {
        return null;
    }

    /**
     * 3ds:shippingAddressLine3
     * Line 3 of customer's shipping address
     *
     * @return string|null
     */
    private function shippingAddressLine3()
    {
        return null;
    }

    /**
     * 3ds:shippingAddressUsageIndicator
     * Indicates when the shipping address used for this transaction was first used with the 3DS Requestor.
     * 01 -> This transaction
     * 02 -> Less than 30 days
     * 03 -> 30 - 60 days
     * 04 -> More than 60 days.
     *
     * @return string|null
     */
    private function shippingAddressUsageIndicator()
    {
        return null;
    }

    /**
     * 3ds:shippingNameEqualIndicator
     * Indicates if the Cardholder Name on the account is identical to the shipping Name used for this transaction.
     * 01 -> Account Name identical to shipping Name
     * 02 -> Account Name different than shipping Name
     *
     * @return string|null
     */
    private function shippingNameEqualIndicator()
    {
        return null;
    }

    /**
     * 3ds:suspiciousAccountActivityIndicator
     * Indicates whether the 3DS Requestor has experienced suspicious activity (including previous fraud) on the cardholder account.
     * 01 -> No suspicious activity has been observed
     * 02 -> Suspicious activity has been observed
     *
     * @return string|null
     */
    private function suspiciousAccountActivityIndicator()
    {
        return null;
    }

    /**
     * 3ds:transactionActivityDay
     * Number of transactions (successful and abandoned) for this cardholder account with the 3DS Requestor across all payment accounts in the previous 24 hours.
     *
     * @return string|null
     */
    private function transactionActivityDay()
    {
        return null;
    }

    /**
     * 3ds:transactionActivityYear
     * Number of transactions (successful and abandoned) for this cardholder account with the 3DS Requestor across all payment accounts in the previous year.
     *
     * @return string|null
     */
    private function transactionActivityYear()
    {
        return null;
    }

    /**
     * 3ds:transType
     * Identifies the type of transaction being authenticated. The values are derived from ISO 8583.
     * 01 -> Goods / Service purchase
     * 03 -> Check Acceptance
     * 10 -> Account Funding
     * 11 -> Quasi-Cash Transaction
     * 28 -> Prepaid activation and Loan
     *
     * @return string|null
     */
    private function transType()
    {
        return null;
    }

    /**
     * 3ds:workPhoneCountryPrefix
     * Country Code of the work phone, limited to 1-3 characters
     *
     * @return string|null
     */
    private function workPhoneCountryPrefix()
    {
        return null;
    }

    /**
     * 3ds:workPhoneNumber
     * subscriber section of the number, limited to maximum 15 characters.
     *
     * @return string|null
     */
    private function workPhoneNumber()
    {
        return null;
    }
}
