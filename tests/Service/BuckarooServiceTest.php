<?php

namespace SeBuDesign\Buckaroo\Tests;

use SeBuDesign\Buckaroo\BuckarooTransaction;

class BuckarooTransactionTest extends TestCase
{
    /**
     * @var BuckarooTransaction
     */
    protected $oBuckarooTransaction;

    /**
     * Set up the testing
     */
    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->oBuckarooTransaction = new BuckarooTransaction('CHANGEME', __DIR__ . '/../test.pem');
    }

    /**
     * @test
     */
    public function it_should_be_in_test_mode()
    {
        $this->oBuckarooTransaction->putInTestMode();
        
        $this->assertTrue($this->oBuckarooTransaction->isInTestMode());
    }

    /**
     * @test
     * 
     * @expectedException \SeBuDesign\Buckaroo\Exceptions\BuckarooTransactionRequestException
     * @expectedExceptionMessage At least one of the AmountDebit or AmountCredit should be set
     */
    public function it_should_throw_an_exception_when_no_amount_selected()
    {
        $this->oBuckarooTransaction->perform();
    }

    /**
     * @test
     * 
     * @expectedException \SeBuDesign\Buckaroo\Exceptions\BuckarooTransactionRequestException
     * @expectedExceptionMessage Amount should be greater than 0
     */
    public function it_should_throw_an_exception_when_amount_credit_is_set_to_0()
    {
        $this->oBuckarooTransaction
            ->setAmountCredit(0)
            ->perform();
    }

    /**
     * @test
     * 
     * @expectedException \SeBuDesign\Buckaroo\Exceptions\BuckarooTransactionRequestException
     * @expectedExceptionMessage Amount should be greater than 0
     */
    public function it_should_throw_an_exception_when_amount_debit_is_set_to_0()
    {
        $this->oBuckarooTransaction
            ->setAmountDebit(0)
            ->perform();
    }

    /**
     * @test
     * 
     * @expectedException \SeBuDesign\Buckaroo\Exceptions\BuckarooTransactionRequestException
     * @expectedExceptionMessage You should choose a payment service
     */
    public function it_should_throw_an_exception_when_no_payment_service_is_set()
    {
        $this->oBuckarooTransaction
            ->setAmountCredit(1.23)
            ->perform();
    }

    /**
     * @test
     *
     * @expectedException \SeBuDesign\Buckaroo\Exceptions\BuckarooTransactionRequestException
     * @expectedExceptionMessage You should choose an iDeal bank
     */
    public function it_should_throw_an_exception_when_no_iDeal_bank_is_set()
    {
        $this->oBuckarooTransaction
            ->setAmountCredit(1.23)
            ->setService(BuckarooTransaction::SERVICE_IDEAL)
            ->perform();
    }

    /**
     * @test
     * 
     * @expectedException \SeBuDesign\Buckaroo\Exceptions\BuckarooTransactionRequestException
     * @expectedExceptionMessage You should provide an invoice number
     */
    public function it_should_throw_an_exception_when_no_invioce_number_is_set()
    {
        $this->oBuckarooTransaction
            ->setAmountCredit(1.23)
            ->setService('test')
            ->perform();
    }
}