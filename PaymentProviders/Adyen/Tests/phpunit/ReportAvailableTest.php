<?php
namespace SmashPig\PaymentProviders\Adyen\Tests;

use PHPQueue\Interfaces\FifoQueueStore;
use SmashPig\Core\Configuration;
use SmashPig\Core\Context;
use SmashPig\Core\QueueConsumers\BaseQueueConsumer;
use SmashPig\Core\UtcDate;
use SmashPig\PaymentProviders\Adyen\ExpatriatedMessages\ReportAvailable;
use SmashPig\Tests\BaseSmashPigUnitTestCase;

class ReportAvailableTest extends BaseSmashPigUnitTestCase {
	/**
	 * @var Configuration
	 */
	protected $config;

	/**
	 * @var FifoQueueStore
	 */
	protected $jobQueue;

	public function setUp() {
		parent::setUp();
		$this->config = AdyenTestConfiguration::createWithSuccessfulApi();
		Context::initWithLogger( $this->config );
		$this->jobQueue = BaseQueueConsumer::getQueue( 'jobs-adyen' );
		$this->jobQueue->createTable( 'jobs-adyen' );
	}

	public function testReportAvailable() {
		$filename = 'settlement_detail_report_2016_10_13.csv';
		$account = 'WikimediaTest';
		$url = "https://example.com/reports/download/MerchantAccount/$account/$filename";
		$reportAvailable = new ReportAvailable();

		$reportAvailable->correlationId = 'adyen-' . mt_rand();
		$reportAvailable->merchantAccountCode = $account;
		$reportAvailable->merchantReference = mt_rand();
		$reportAvailable->pspReference = $filename;
		$reportAvailable->reason = $url;
		$reportAvailable->eventDate = '2016-10-14T12:06:20.496+02:00';

		$reportAvailable->runActionChain();

		$job = $this->jobQueue->pop();

		$now = UtcDate::getUtcTimestamp();
		$diff = abs( $job['source_enqueued_time'] ) - $now;
		$this->assertTrue( $diff < 60, 'Odd enqueued time' );
		$unsetFields = array(
			'source_enqueued_time', 'source_host', 'source_run_id',
			'source_version', 'propertiesExcludedFromExport',
			'propertiesExportedAsKeys',
		);
		foreach ( $unsetFields as $fieldName ) {
			unset( $job[$fieldName] );
		}
		$expected = array(
			'php-message-class' => 'SmashPig\PaymentProviders\Adyen\Jobs\DownloadReportJob',
			'reportUrl' => $url,
			'account' => $account,
			'source_name' => 'SmashPig',
			'source_type' => 'listener',
			'correlationId' => '',
			'gateway' => 'adyen',
		);
		$this->assertEquals( $expected, $job );
	}

}
