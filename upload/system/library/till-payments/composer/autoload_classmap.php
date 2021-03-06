<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname(dirname(dirname(dirname($vendorDir))));

return array(
    'TillPayments\\Client\\Callback\\ChargebackData' => $vendorDir . '/client/Callback/ChargebackData.php',
    'TillPayments\\Client\\Callback\\ChargebackReversalData' => $vendorDir . '/client/Callback/ChargebackReversalData.php',
    'TillPayments\\Client\\Callback\\Result' => $vendorDir . '/client/Callback/Result.php',
    'TillPayments\\Client\\Client' => $vendorDir . '/client/Client.php',
    'TillPayments\\Client\\CustomerProfile\\CustomerData' => $vendorDir . '/client/CustomerProfile/CustomerData.php',
    'TillPayments\\Client\\CustomerProfile\\DeleteProfileResponse' => $vendorDir . '/client/CustomerProfile/DeleteProfileResponse.php',
    'TillPayments\\Client\\CustomerProfile\\GetProfileResponse' => $vendorDir . '/client/CustomerProfile/GetProfileResponse.php',
    'TillPayments\\Client\\CustomerProfile\\PaymentData\\CardData' => $vendorDir . '/client/CustomerProfile/PaymentData/CardData.php',
    'TillPayments\\Client\\CustomerProfile\\PaymentData\\IbanData' => $vendorDir . '/client/CustomerProfile/PaymentData/IbanData.php',
    'TillPayments\\Client\\CustomerProfile\\PaymentData\\PaymentData' => $vendorDir . '/client/CustomerProfile/PaymentData/PaymentData.php',
    'TillPayments\\Client\\CustomerProfile\\PaymentData\\WalletData' => $vendorDir . '/client/CustomerProfile/PaymentData/WalletData.php',
    'TillPayments\\Client\\CustomerProfile\\PaymentInstrument' => $vendorDir . '/client/CustomerProfile/PaymentInstrument.php',
    'TillPayments\\Client\\CustomerProfile\\UpdateProfileResponse' => $vendorDir . '/client/CustomerProfile/UpdateProfileResponse.php',
    'TillPayments\\Client\\Data\\CreditCardCustomer' => $vendorDir . '/client/Data/CreditCardCustomer.php',
    'TillPayments\\Client\\Data\\Customer' => $vendorDir . '/client/Data/Customer.php',
    'TillPayments\\Client\\Data\\Data' => $vendorDir . '/client/Data/Data.php',
    'TillPayments\\Client\\Data\\IbanCustomer' => $vendorDir . '/client/Data/IbanCustomer.php',
    'TillPayments\\Client\\Data\\Item' => $vendorDir . '/client/Data/Item.php',
    'TillPayments\\Client\\Data\\Request' => $vendorDir . '/client/Data/Request.php',
    'TillPayments\\Client\\Data\\Result\\CreditcardData' => $vendorDir . '/client/Data/Result/CreditcardData.php',
    'TillPayments\\Client\\Data\\Result\\IbanData' => $vendorDir . '/client/Data/Result/IbanData.php',
    'TillPayments\\Client\\Data\\Result\\PhoneData' => $vendorDir . '/client/Data/Result/PhoneData.php',
    'TillPayments\\Client\\Data\\Result\\ResultData' => $vendorDir . '/client/Data/Result/ResultData.php',
    'TillPayments\\Client\\Data\\Result\\WalletData' => $vendorDir . '/client/Data/Result/WalletData.php',
    'TillPayments\\Client\\Exception\\ClientException' => $vendorDir . '/client/Exception/ClientException.php',
    'TillPayments\\Client\\Exception\\InvalidValueException' => $vendorDir . '/client/Exception/InvalidValueException.php',
    'TillPayments\\Client\\Exception\\RateLimitException' => $vendorDir . '/client/Exception/RateLimitException.php',
    'TillPayments\\Client\\Exception\\TimeoutException' => $vendorDir . '/client/Exception/TimeoutException.php',
    'TillPayments\\Client\\Exception\\TypeException' => $vendorDir . '/client/Exception/TypeException.php',
    'TillPayments\\Client\\Http\\ClientInterface' => $vendorDir . '/client/Http/ClientInterface.php',
    'TillPayments\\Client\\Http\\CurlClient' => $vendorDir . '/client/Http/CurlClient.php',
    'TillPayments\\Client\\Http\\CurlExec' => $vendorDir . '/client/Http/CurlExec.php',
    'TillPayments\\Client\\Http\\Exception\\ClientException' => $vendorDir . '/client/Http/Exception/ClientException.php',
    'TillPayments\\Client\\Http\\Exception\\ResponseException' => $vendorDir . '/client/Http/Exception/ResponseException.php',
    'TillPayments\\Client\\Http\\Response' => $vendorDir . '/client/Http/Response.php',
    'TillPayments\\Client\\Http\\ResponseInterface' => $vendorDir . '/client/Http/ResponseInterface.php',
    'TillPayments\\Client\\Json\\DataObject' => $vendorDir . '/client/Json/DataObject.php',
    'TillPayments\\Client\\Json\\ErrorResponse' => $vendorDir . '/client/Json/ErrorResponse.php',
    'TillPayments\\Client\\Json\\ResponseObject' => $vendorDir . '/client/Json/ResponseObject.php',
    'TillPayments\\Client\\Schedule\\ScheduleData' => $vendorDir . '/client/Schedule/ScheduleData.php',
    'TillPayments\\Client\\Schedule\\ScheduleError' => $vendorDir . '/client/Schedule/ScheduleError.php',
    'TillPayments\\Client\\Schedule\\ScheduleResult' => $vendorDir . '/client/Schedule/ScheduleResult.php',
    'TillPayments\\Client\\StatusApi\\StatusRequestData' => $vendorDir . '/client/StatusApi/StatusRequestData.php',
    'TillPayments\\Client\\StatusApi\\StatusResult' => $vendorDir . '/client/StatusApi/StatusResult.php',
    'TillPayments\\Client\\Transaction\\Base\\AbstractTransaction' => $vendorDir . '/client/Transaction/Base/AbstractTransaction.php',
    'TillPayments\\Client\\Transaction\\Base\\AbstractTransactionWithReference' => $vendorDir . '/client/Transaction/Base/AbstractTransactionWithReference.php',
    'TillPayments\\Client\\Transaction\\Base\\AddToCustomerProfileInterface' => $vendorDir . '/client/Transaction/Base/AddToCustomerProfileInterface.php',
    'TillPayments\\Client\\Transaction\\Base\\AddToCustomerProfileTrait' => $vendorDir . '/client/Transaction/Base/AddToCustomerProfileTrait.php',
    'TillPayments\\Client\\Transaction\\Base\\AmountableInterface' => $vendorDir . '/client/Transaction/Base/AmountableInterface.php',
    'TillPayments\\Client\\Transaction\\Base\\AmountableTrait' => $vendorDir . '/client/Transaction/Base/AmountableTrait.php',
    'TillPayments\\Client\\Transaction\\Base\\ItemsInterface' => $vendorDir . '/client/Transaction/Base/ItemsInterface.php',
    'TillPayments\\Client\\Transaction\\Base\\ItemsTrait' => $vendorDir . '/client/Transaction/Base/ItemsTrait.php',
    'TillPayments\\Client\\Transaction\\Base\\OffsiteInterface' => $vendorDir . '/client/Transaction/Base/OffsiteInterface.php',
    'TillPayments\\Client\\Transaction\\Base\\OffsiteTrait' => $vendorDir . '/client/Transaction/Base/OffsiteTrait.php',
    'TillPayments\\Client\\Transaction\\Base\\ScheduleInterface' => $vendorDir . '/client/Transaction/Base/ScheduleInterface.php',
    'TillPayments\\Client\\Transaction\\Base\\ScheduleTrait' => $vendorDir . '/client/Transaction/Base/ScheduleTrait.php',
    'TillPayments\\Client\\Transaction\\Capture' => $vendorDir . '/client/Transaction/Capture.php',
    'TillPayments\\Client\\Transaction\\Debit' => $vendorDir . '/client/Transaction/Debit.php',
    'TillPayments\\Client\\Transaction\\Deregister' => $vendorDir . '/client/Transaction/Deregister.php',
    'TillPayments\\Client\\Transaction\\Error' => $vendorDir . '/client/Transaction/Error.php',
    'TillPayments\\Client\\Transaction\\Payout' => $vendorDir . '/client/Transaction/Payout.php',
    'TillPayments\\Client\\Transaction\\Preauthorize' => $vendorDir . '/client/Transaction/Preauthorize.php',
    'TillPayments\\Client\\Transaction\\Refund' => $vendorDir . '/client/Transaction/Refund.php',
    'TillPayments\\Client\\Transaction\\Register' => $vendorDir . '/client/Transaction/Register.php',
    'TillPayments\\Client\\Transaction\\Result' => $vendorDir . '/client/Transaction/Result.php',
    'TillPayments\\Client\\Transaction\\VoidTransaction' => $vendorDir . '/client/Transaction/VoidTransaction.php',
    'TillPayments\\Client\\Xml\\Generator' => $vendorDir . '/client/Xml/Generator.php',
    'TillPayments\\Client\\Xml\\Parser' => $vendorDir . '/client/Xml/Parser.php',
    'TillPayments\\TillPaymentsGateway' => $vendorDir . '/plugin/gateway.php',
    'TillPayments\\TillPaymentsPlugin' => $vendorDir . '/plugin/plugin.php',
    'Psr\\Log\\AbstractLogger' => $vendorDir . '/psr/log/Psr/Log/AbstractLogger.php',
    'Psr\\Log\\InvalidArgumentException' => $vendorDir . '/psr/log/Psr/Log/InvalidArgumentException.php',
    'Psr\\Log\\LogLevel' => $vendorDir . '/psr/log/Psr/Log/LogLevel.php',
    'Psr\\Log\\LoggerAwareInterface' => $vendorDir . '/psr/log/Psr/Log/LoggerAwareInterface.php',
    'Psr\\Log\\LoggerAwareTrait' => $vendorDir . '/psr/log/Psr/Log/LoggerAwareTrait.php',
    'Psr\\Log\\LoggerInterface' => $vendorDir . '/psr/log/Psr/Log/LoggerInterface.php',
    'Psr\\Log\\LoggerTrait' => $vendorDir . '/psr/log/Psr/Log/LoggerTrait.php',
    'Psr\\Log\\NullLogger' => $vendorDir . '/psr/log/Psr/Log/NullLogger.php',
    'Psr\\Log\\Test\\DummyTest' => $vendorDir . '/psr/log/Psr/Log/Test/LoggerInterfaceTest.php',
    'Psr\\Log\\Test\\LoggerInterfaceTest' => $vendorDir . '/psr/log/Psr/Log/Test/LoggerInterfaceTest.php',
    'Psr\\Log\\Test\\TestLogger' => $vendorDir . '/psr/log/Psr/Log/Test/TestLogger.php',
);
