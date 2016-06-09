<?php

namespace SeBuDesign\Buckaroo\Soap;

use SeBuDesign\Buckaroo\Exceptions\BuckarooArgumentException;
use LinkORB\Buckaroo\SoapClientWSSEC as BuckarooSoapClient;
use SeBuDesign\Buckaroo\SOAP\Types\Requests as BuckarooSoapTypes;
use SoapHeader;

class BuckarooBaseSoap
{
    /**
     * The path to the PEM file
     *
     * @var string
     */
    protected $sPemPath;

    /**
     * The SOAP end point
     *
     * @var string
     */
    protected $sSoapEndPoint = 'https://checkout.buckaroo.nl/soap/soap.svc';

    /**
     * The Buckaroo channel
     *
     * @var string
     */
    protected $sChannel = 'Web';

    /**
     * The Buckaroo locale
     *
     * @var string
     */
    protected $sLocale = 'nl-NL';

    /**
     * Is the service in testing mode?
     *
     * @var boolean
     */
    protected $bIsTesting = false;

    /**
     * The Buckaroo SOAP client
     *
     * @var BuckarooSoapClient
     */
    protected $oSoapClient;

    /**
     * The Buckaroo website key
     *
     * @var string
     */
    protected $sWebsiteKey;

    protected $oRequestBody;

    /**
     * The SOAP options for the Buckaroo SOAP client
     *
     * @var array
     */
    protected $aSoapOptions = [
        'trace'    => 1,
        'classmap' => [
            'Body'                 => 'LinkORB\\Buckaroo\\SOAP\\Type\\Body',
            'Status'               => 'LinkORB\\Buckaroo\\SOAP\\Type\\Status',
            'RequiredAction'       => 'LinkORB\\Buckaroo\\SOAP\\Type\\RequiredAction',
            'ParameterError'       => 'LinkORB\\Buckaroo\\SOAP\\Type\\ParameterError',
            'CustomParameterError' => 'LinkORB\\Buckaroo\\SOAP\\Type\\CustomParameterError',
            'ServiceError'         => 'LinkORB\\Buckaroo\\SOAP\\Type\\ServiceError',
            'ActionError'          => 'LinkORB\\Buckaroo\\SOAP\\Type\\ActionError',
            'ChannelError'         => 'LinkORB\\Buckaroo\\SOAP\\Type\\ChannelError',
            'RequestErrors'        => 'LinkORB\\Buckaroo\\SOAP\\Type\\RequestErrors',
            'StatusCode'           => 'LinkORB\\Buckaroo\\SOAP\\Type\\StatusCode',
            'StatusSubCode'        => 'LinkORB\\Buckaroo\\SOAP\\Type\\StatusCode',
        ],
    ];

    /**
     * Put the SOAP Client in test mode
     *
     * @return $this
     */
    public function putInTestMode()
    {
        $this->bIsTesting = true;
        $this->sSoapEndPoint = 'https://testcheckout.buckaroo.nl/soap/soap.svc';

        return $this;
    }

    /**
     * Check if we are in testing mode
     *
     * @return bool
     */
    public function isInTestMode()
    {
        return $this->bIsTesting;
    }

    /**
     * Set the PEM file path
     *
     * @param string $sPathToPem The path to the PEM file
     *
     * @return $this
     *
     * @throws \SeBuDesign\Buckaroo\Exceptions\BuckarooArgumentException
     */
    public function setPemPath($sPathToPem)
    {
        if (!file_exists($sPathToPem)) {
            throw new BuckarooArgumentException("The PEM ({$sPathToPem}) does not exists");
        }

        $this->sPemPath = $sPathToPem;

        return $this;
    }

    /**
     * Set the Buckaroo channel
     *
     * @param string $sChannel The Buckaroo channel
     *
     * @return $this
     */
    public function setBuckarooChannel($sChannel)
    {
        $this->sChannel = $sChannel;

        return $this;
    }

    /**
     * Set the Buckaroo website key
     *
     * @param string $sWebsiteKey The Buckaroo website key
     *
     * @return $this
     */
    public function setWebsiteKey($sWebsiteKey)
    {
        $this->sWebsiteKey = $sWebsiteKey;

        return $this;
    }

    /**
     * Set the Buckaroo locale
     *
     * @param string $sLocale The Buckaroo locale
     *
     * @return $this
     */
    public function setLocale($sLocale)
    {
        $this->sLocale = $sLocale;

        return $this;
    }

    /**
     * Add or override SOAP options
     *
     * @param string $sOption The name of the option
     * @param mixed  $mValue  The value of the option
     *
     * @return $this
     */
    public function addSoapOption($sOption, $mValue)
    {
        $this->aSoapOptions[ $sOption ] = $mValue;

        return $this;
    }

    public function call($sCall)
    {
        if (!$this->sPemPath) {
            throw new BuckarooArgumentException("Missing PEM file");
        }

        if (!$this->sWebsiteKey) {
            throw new BuckarooArgumentException("Missing Buckaroo website key");
        }

        $this->oSoapClient = new BuckarooSoapClient("{$this->sSoapEndPoint}?wsdl", $this->aSoapOptions);
        $this->oSoapClient->loadPem($this->sPemPath);

        $this->addControlBlockHeaders();

        $return = [];

        switch($sCall) {
//            case 'invoiceinfo':
//                $return['result'] = $this->soapClient->InvoiceInfo($TransactionRequest);
//                break;
            case 'transaction':
                $return['result'] = $this->oSoapClient->TransactionRequest($this->oRequestBody);
                break;
//            case 'transactionstatus':
//                $return['result'] = $this->soapClient->TransactionStatus($TransactionRequest);
//                break;
//            case 'refundinfo':
//                $return['result'] = $this->soapClient->RefundInfo($TransactionRequest);
//                break;
        }

        $return['response'] = $this->oSoapClient->__getLastResponse();
        $return['request']  = $this->oSoapClient->__getLastRequest();

        return $return;
    }

    /**
     * This method creates the SOAP client headers which are needed at every request for security purposes
     */
    protected function addControlBlockHeaders()
    {
        // Envelope and wrapper stuff
        $oHeader = new BuckarooSoapTypes\Common\Header();

        // Build MessageControlBlock
        $oHeader->MessageControlBlock = new BuckarooSoapTypes\Common\MessageControlBlock();
        $oHeader->MessageControlBlock->Id = '_control';
        $oHeader->MessageControlBlock->WebsiteKey = $this->sWebsiteKey;
        $oHeader->MessageControlBlock->Culture = $this->sLocale;
        $oHeader->MessageControlBlock->TimeStamp = time();
        $oHeader->MessageControlBlock->Channel = $this->sChannel;

        // Setup the security
        $oHeader->Security = new BuckarooSoapTypes\Common\SecurityType();
        $oHeader->Security->Signature = new BuckarooSoapTypes\Common\SignatureType();
        $oHeader->Security->Signature->SignedInfo = new BuckarooSoapTypes\Common\SignedInfoType();

        // Create a reference type
        $oReference = new BuckarooSoapTypes\Common\ReferenceType();
        $oReference->URI = '#_body';

        // Define the algorithm for the Transform option of the reference
        $oReferenceTransform = new BuckarooSoapTypes\Common\TransformType();
        $oReferenceTransform->Algorithm = 'http://www.w3.org/2001/10/xml-exc-c14n#';
        $oReference->Transforms = [
            $oReferenceTransform
        ];

        // Define the digest method, in our case sha1
        $oReference->DigestMethod = new BuckarooSoapTypes\Common\DigestMethodType();
        $oReference->DigestMethod->Algorithm = 'http://www.w3.org/2000/09/xmldsig#sha1';
        $oReference->DigestValue = '';

        // Define the reference control transformer
        $oReferenceControlTransform = new BuckarooSoapTypes\Common\TransformType();
        $oReferenceControlTransform->Algorithm = 'http://www.w3.org/2001/10/xml-exc-c14n#';

        // Setup the reference control
        $oReferenceControl = new BuckarooSoapTypes\Common\ReferenceType();
        $oReferenceControl->URI = '#_control';

        // Define the digest method, in our case sha1
        $oReferenceControl->DigestMethod = new BuckarooSoapTypes\Common\DigestMethodType();
        $oReferenceControl->DigestMethod->Algorithm = 'http://www.w3.org/2000/09/xmldsig#sha1';
        $oReferenceControl->DigestValue = '';

        $oReferenceControl->Transforms = [
            $oReferenceControlTransform
        ];

        // Setup the signed info reference
        $oHeader->Security->Signature->SignatureValue = '';
        $oHeader->Security->Signature->SignedInfo->Reference = [
            $oReference,
            $oReferenceControl
        ];

        // Add the headers to the SOAP client
        $soapHeaders[] = new SOAPHeader('https://checkout.buckaroo.nl/PaymentEngine/', 'MessageControlBlock', $oHeader->MessageControlBlock);
        $soapHeaders[] = new SOAPHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security', $oHeader->Security);

        $this->oSoapClient->__setSoapHeaders($soapHeaders);
    }
}